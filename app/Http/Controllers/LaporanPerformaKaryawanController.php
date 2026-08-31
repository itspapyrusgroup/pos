<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\KoTrackingItemCheck;
use App\Services\XlsxExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LaporanPerformaKaryawanController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'cabang_id' => ['nullable', 'exists:cabang,id'],
            'karyawan_id' => ['nullable', 'exists:karyawan,id'],
        ]);

        $dateFrom = $validated['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $validated['date_to'] ?? now()->toDateString();
        if (Carbon::parse($dateFrom)->gt(Carbon::parse($dateTo))) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $cabangId = $this->resolveCabangFilter($request);
        $this->ensureCabangAccessible($cabangId);
        $karyawanId = isset($validated['karyawan_id']) ? (int) $validated['karyawan_id'] : null;

        $karyawanOptionsQuery = Karyawan::query()
            ->with('user:id,name,username')
            ->whereNotNull('user_id')
            ->where('status', true);
        $this->applyCabangScope($karyawanOptionsQuery);
        if ($cabangId) {
            $karyawanOptionsQuery->where('cabang_id', $cabangId);
        }

        $karyawanList = $karyawanOptionsQuery
            ->orderBy('nama')
            ->get(['id', 'user_id', 'nama', 'cabang_id']);

        $checkedByUserId = null;
        if ($karyawanId) {
            $selectedKaryawan = (clone $karyawanOptionsQuery)->where('id', $karyawanId)->first();
            if (!$selectedKaryawan) {
                throw ValidationException::withMessages([
                    'karyawan_id' => ['Karyawan tidak valid untuk filter cabang/akses Anda.'],
                ]);
            }
            $checkedByUserId = (int) $selectedKaryawan->user_id;
        }

        $baseQuery = KoTrackingItemCheck::query()
            ->from('ko_tracking_item_checks as kic')
            ->join('pesanan_penjualan_item as ppi', 'ppi.id', '=', 'kic.pesanan_penjualan_item_id')
            ->join('pesanan_penjualan as pp', 'pp.id', '=', 'ppi.pesanan_penjualan_id')
            ->leftJoin('paket as pk', 'pk.id', '=', 'ppi.paket_id')
            ->leftJoin('produk as pr', 'pr.id', '=', 'kic.produk_id')
            ->leftJoin('paket_item as pi', function ($join) {
                $join->on('pi.paket_id', '=', 'ppi.paket_id')
                    ->on('pi.produk_id', '=', 'kic.produk_id');
            })
            ->leftJoin('users as u', 'u.id', '=', 'kic.checked_by_user_id')
            ->leftJoin('karyawan as kr', 'kr.user_id', '=', 'u.id')
            ->leftJoin('cabang as cb', 'cb.id', '=', 'pp.cabang_id')
            ->leftJoin('kantong_order as ko', 'ko.pesanan_penjualan_id', '=', 'pp.id')
            ->where('kic.is_checked', true)
            ->whereNotNull('kic.checked_at')
            ->whereDate('kic.checked_at', '>=', $dateFrom)
            ->whereDate('kic.checked_at', '<=', $dateTo);

        $this->applyCabangScope($baseQuery, 'pp.cabang_id');
        if ($cabangId) {
            $baseQuery->where('pp.cabang_id', $cabangId);
        }
        if ($checkedByUserId) {
            $baseQuery->where('kic.checked_by_user_id', $checkedByUserId);
        }

        $summary = (clone $baseQuery)
            ->selectRaw('
                COUNT(*) as total_checklist,
                COALESCE(SUM(COALESCE(pi.qty, 1) * COALESCE(ppi.qty, 0)), 0) as total_qty_checked,
                COUNT(DISTINCT kic.checked_by_user_id) as total_karyawan_aktif,
                COUNT(DISTINCT ppi.pesanan_penjualan_id) as total_order,
                COUNT(DISTINCT pp.cabang_id) as total_cabang
            ')
            ->first();

        $paketRows = (clone $baseQuery)
            ->selectRaw('
                COALESCE(pk.nama, \'Tanpa Paket\') as paket_nama,
                COUNT(*) as total_checklist,
                COALESCE(SUM(COALESCE(pi.qty, 1) * COALESCE(ppi.qty, 0)), 0) as total_qty_checked
            ')
            ->groupBy('pk.nama')
            ->orderByDesc('total_checklist')
            ->orderBy('paket_nama')
            ->get();

        $itemRows = (clone $baseQuery)
            ->selectRaw('
                COALESCE(pr.nama, \'Produk Tidak Ditemukan\') as item_nama,
                COUNT(*) as total_checklist,
                COALESCE(SUM(COALESCE(pi.qty, 1) * COALESCE(ppi.qty, 0)), 0) as total_qty_checked
            ')
            ->groupBy('pr.nama')
            ->orderByDesc('total_checklist')
            ->orderBy('item_nama')
            ->get();

        $details = (clone $baseQuery)
            ->selectRaw('
                kic.id,
                kic.checked_at,
                u.name as checked_by_name,
                u.username as checked_by_username,
                kr.nama as karyawan_nama,
                cb.nama as cabang_nama,
                pp.nomor_so,
                ko.nomor_ko,
                COALESCE(pk.nama, \'Tanpa Paket\') as paket_nama,
                COALESCE(pr.nama, \'Produk Tidak Ditemukan\') as item_nama,
                COALESCE(ppi.qty, 0) as qty_order_item,
                COALESCE(pi.qty, 1) as qty_item_di_paket,
                (COALESCE(pi.qty, 1) * COALESCE(ppi.qty, 0)) as qty_checked
            ')
            ->orderByDesc('kic.checked_at')
            ->paginate(25)
            ->withQueryString();

        if ($request->boolean('export_xlsx')) {
            $exportRows = (clone $baseQuery)
                ->selectRaw('
                    kic.checked_at,
                    u.name as checked_by_name,
                    u.username as checked_by_username,
                    kr.nama as karyawan_nama,
                    cb.nama as cabang_nama,
                    pp.nomor_so,
                    ko.nomor_ko,
                    COALESCE(pk.nama, \'Tanpa Paket\') as paket_nama,
                    COALESCE(pr.nama, \'Produk Tidak Ditemukan\') as item_nama,
                    COALESCE(ppi.qty, 0) as qty_order_item,
                    COALESCE(pi.qty, 1) as qty_item_di_paket,
                    (COALESCE(pi.qty, 1) * COALESCE(ppi.qty, 0)) as qty_checked
                ')
                ->orderByDesc('kic.checked_at')
                ->get();

            $rowsXlsx = $exportRows->map(function ($row) {
                $karyawan = $row->karyawan_nama ?: ($row->checked_by_name ?? '-');
                if (!empty($row->checked_by_username)) {
                    $karyawan .= ' (@' . $row->checked_by_username . ')';
                }
                return [
                    Carbon::parse($row->checked_at)->format('Y-m-d H:i'),
                    $karyawan,
                    $row->cabang_nama ?? '-',
                    $row->nomor_so ?? '-',
                    $row->nomor_ko ?? '-',
                    $row->paket_nama,
                    $row->item_nama,
                    (float) $row->qty_item_di_paket,
                    (float) $row->qty_order_item,
                    (float) $row->qty_checked,
                ];
            })->all();

            return app(XlsxExportService::class)->download(
                'laporan-performa-karyawan-' . now()->format('Ymd-His') . '.xlsx',
                ['Waktu Checklist', 'Karyawan', 'Cabang', 'No SO', 'No KO', 'Paket', 'Item', 'Qty Item di Paket', 'Qty Order', 'Qty Checked'],
                $rowsXlsx,
                'Performa'
            );
        }

        return view('pages.pos.laporan-performa-karyawan', [
            'cabangs' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'karyawanList' => $karyawanList,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'cabang_id' => $cabangId,
                'karyawan_id' => $karyawanId,
            ],
            'summary' => [
                'total_checklist' => (int) ($summary->total_checklist ?? 0),
                'total_qty_checked' => (float) ($summary->total_qty_checked ?? 0),
                'total_karyawan_aktif' => (int) ($summary->total_karyawan_aktif ?? 0),
                'total_order' => (int) ($summary->total_order ?? 0),
                'total_cabang' => (int) ($summary->total_cabang ?? 0),
            ],
            'paketRows' => $paketRows,
            'itemRows' => $itemRows,
            'details' => $details,
        ]);
    }
}
