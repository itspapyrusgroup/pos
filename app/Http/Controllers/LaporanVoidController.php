<?php

namespace App\Http\Controllers;

use App\Models\PembayaranPenjualan;
use App\Models\PenjualanVoidLog;
use App\Models\PesananPenjualanItem;
use App\Models\User;
use App\Services\XlsxExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LaporanVoidController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'cabang_id' => ['nullable', 'exists:cabang,id'],
            'kasir_user_id' => ['nullable', 'exists:users,id'],
            'tipe_void' => ['nullable', 'in:FULL,PARTIAL'],
            'no_ko' => ['nullable', 'string', 'max:50'],
        ]);

        $dateFrom = $validated['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $validated['date_to'] ?? now()->toDateString();
        if (Carbon::parse($dateFrom)->gt(Carbon::parse($dateTo))) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $cabangId = $this->resolveCabangFilter($request);
        $kasirId = isset($validated['kasir_user_id']) ? (int) $validated['kasir_user_id'] : null;
        $tipeVoid = $validated['tipe_void'] ?? '';
        $noKo = trim((string) ($validated['no_ko'] ?? ''));

        $baseQuery = PenjualanVoidLog::query()
            ->from('penjualan_void_logs as pvl')
            ->join('pesanan_penjualan as pz', 'pz.id', '=', 'pvl.pesanan_penjualan_id')
            ->leftJoin('kantong_order as ko', 'ko.pesanan_penjualan_id', '=', 'pz.id')
            ->leftJoin('users as kasir', 'kasir.id', '=', 'pz.kasir_user_id')
            ->leftJoin('users as voider', 'voider.id', '=', 'pvl.voided_by_user_id')
            ->whereIn('pvl.tipe_void', ['FULL', 'PARTIAL'])
            ->whereDate('pvl.voided_at', '>=', $dateFrom)
            ->whereDate('pvl.voided_at', '<=', $dateTo)
            ->selectRaw('
                pvl.id,
                pvl.pesanan_penjualan_id,
                pvl.tipe_void,
                pvl.nominal_void,
                pvl.voided_at,
                pvl.void_effective_date,
                pvl.alasan,
                pz.created_at as tanggal_transaksi,
                COALESCE(ko.nomor_ko, \'-\') as nomor_ko,
                COALESCE(NULLIF(pz.customer_name, \'\'), \'-\') as customer_name,
                COALESCE(NULLIF(pz.customer_phone, \'\'), \'-\') as customer_phone,
                COALESCE(kasir.name, \'Tanpa Kasir\') as kasir_nama,
                COALESCE(voider.name, \'Unknown\') as voided_by_name
            ');

        $this->applyCabangScope($baseQuery, 'pz.cabang_id');
        if ($cabangId) {
            $baseQuery->where('pz.cabang_id', $cabangId);
        }
        if ($kasirId) {
            $baseQuery->where('pz.kasir_user_id', $kasirId);
        }
        if ($tipeVoid !== '') {
            $baseQuery->where('pvl.tipe_void', $tipeVoid);
        }
        if ($noKo !== '') {
            $baseQuery->where('ko.nomor_ko', 'like', '%' . $noKo . '%');
        }

        $summary = [
            'jumlah_void' => (int) (clone $baseQuery)->count(),
            'total_nominal_void' => (float) (clone $baseQuery)->sum('pvl.nominal_void'),
            'jumlah_full' => (int) (clone $baseQuery)->where('pvl.tipe_void', 'FULL')->count(),
            'jumlah_partial' => (int) (clone $baseQuery)->where('pvl.tipe_void', 'PARTIAL')->count(),
        ];

        $rows = (clone $baseQuery)
            ->orderByDesc('pvl.voided_at')
            ->paginate(25)
            ->withQueryString();

        $voidIds = $rows->getCollection()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $orderIds = $rows->getCollection()->pluck('pesanan_penjualan_id')->map(fn ($id) => (int) $id)->unique()->all();

        $itemsByVoidId = $this->resolveItemsByVoidId($voidIds);
        $paymentsByVoidId = $this->resolvePaymentMethodsByVoid($rows->getCollection(), $orderIds);

        $rows->setCollection(
            $rows->getCollection()->map(function ($row) use ($itemsByVoidId, $paymentsByVoidId) {
                $row->void_items = $itemsByVoidId[(int) $row->id] ?? '-';
                $row->payment_methods = $paymentsByVoidId[(int) $row->id] ?? '-';
                return $row;
            })
        );

        if ($request->boolean('export_xlsx')) {
            $exportRows = (clone $baseQuery)
                ->orderByDesc('pvl.voided_at')
                ->get();

            $exportVoidIds = $exportRows->pluck('id')->map(fn ($id) => (int) $id)->all();
            $exportOrderIds = $exportRows->pluck('pesanan_penjualan_id')->map(fn ($id) => (int) $id)->unique()->all();
            $exportItemsByVoidId = $this->resolveItemsByVoidId($exportVoidIds);
            $exportPaymentsByVoidId = $this->resolvePaymentMethodsByVoid($exportRows, $exportOrderIds);

            $rowsXlsx = $exportRows->map(function ($row) use ($exportItemsByVoidId, $exportPaymentsByVoidId) {
                return [
                    $row->tanggal_transaksi ? Carbon::parse($row->tanggal_transaksi)->format('Y-m-d H:i') : '-',
                    $row->voided_at ? Carbon::parse($row->voided_at)->format('Y-m-d H:i') : '-',
                    $row->tipe_void,
                    $row->nomor_ko ?? '-',
                    $row->customer_name ?? '-',
                    $row->customer_phone ?? '-',
                    $exportItemsByVoidId[(int) $row->id] ?? '-',
                    (float) $row->nominal_void,
                    $exportPaymentsByVoidId[(int) $row->id] ?? '-',
                    $row->kasir_nama ?? '-',
                    $row->voided_by_name ?? '-',
                    $row->alasan ?? '-',
                ];
            })->all();

            return app(XlsxExportService::class)->download(
                'laporan-void-' . now()->format('Ymd-His') . '.xlsx',
                ['Tanggal Transaksi', 'Waktu Void', 'Tipe Void', 'No KO', 'Nama Customer', 'No HP', 'Item/Paket Void', 'Nominal Void', 'Metode Pembayaran', 'Kasir', 'Di-void Oleh', 'Alasan'],
                $rowsXlsx,
                'Laporan Void'
            );
        }

        return view('pages.pos.laporan-void', [
            'cabangs' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'kasirList' => $this->resolveKasirList($dateFrom, $dateTo, $cabangId),
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'cabang_id' => $cabangId,
                'kasir_user_id' => $kasirId,
                'tipe_void' => $tipeVoid,
                'no_ko' => $noKo,
            ],
            'rows' => $rows,
            'summary' => $summary,
        ]);
    }

    private function resolveKasirList(string $dateFrom, string $dateTo, ?int $cabangId): Collection
    {
        $query = PenjualanVoidLog::query()
            ->from('penjualan_void_logs as pvl')
            ->join('pesanan_penjualan as pz', 'pz.id', '=', 'pvl.pesanan_penjualan_id')
            ->whereIn('pvl.tipe_void', ['FULL', 'PARTIAL'])
            ->whereDate('pvl.voided_at', '>=', $dateFrom)
            ->whereDate('pvl.voided_at', '<=', $dateTo)
            ->whereNotNull('pz.kasir_user_id');

        $this->applyCabangScope($query, 'pz.cabang_id');
        if ($cabangId) {
            $query->where('pz.cabang_id', $cabangId);
        }

        $kasirIds = $query->distinct()->pluck('pz.kasir_user_id')->map(fn ($id) => (int) $id)->all();
        if (empty($kasirIds)) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $kasirIds)
            ->orderBy('name')
            ->get(['id', 'name', 'username']);
    }

    private function resolveItemsByVoidId(array $voidIds): array
    {
        if (empty($voidIds)) {
            return [];
        }

        return PesananPenjualanItem::query()
            ->with(['produk:id,nama', 'paket:id,nama'])
            ->whereIn('void_log_id', $voidIds)
            ->get()
            ->groupBy('void_log_id')
            ->map(function ($items) {
                $names = $items->map(function ($item) {
                    return $item->paket?->nama ?? $item->produk?->nama ?? '-';
                })->filter()->unique()->values()->all();

                return empty($names) ? '-' : implode(', ', $names);
            })
            ->all();
    }

    private function resolvePaymentMethodsByVoid(Collection $rows, array $orderIds): array
    {
        if (empty($orderIds) || $rows->isEmpty()) {
            return [];
        }

        $payments = PembayaranPenjualan::query()
            ->from('pembayaran_penjualan as pp')
            ->join('metode_pembayaran as mp', 'mp.id', '=', 'pp.metode_pembayaran_id')
            ->whereIn('pp.pesanan_penjualan_id', $orderIds)
            ->where('pp.nominal', '<', 0)
            ->selectRaw('
                pp.pesanan_penjualan_id,
                pp.tanggal_bayar,
                mp.nama as metode_nama,
                ABS(pp.nominal) as nominal_void
            ')
            ->get();

        $paymentMap = $payments->groupBy(function ($payment) {
            return (int) $payment->pesanan_penjualan_id . '|' . Carbon::parse($payment->tanggal_bayar)->format('Y-m-d H:i:s');
        })->map(function ($group) {
            return $group->map(function ($payment) {
                return $payment->metode_nama . ' (' . number_format((float) $payment->nominal_void, 0, ',', '.') . ')';
            })->implode(', ');
        });

        $rowsByDateFallback = $payments->groupBy(function ($payment) {
            return (int) $payment->pesanan_penjualan_id . '|' . Carbon::parse($payment->tanggal_bayar)->toDateString();
        })->map(function ($group) {
            return $group->map(function ($payment) {
                return $payment->metode_nama . ' (' . number_format((float) $payment->nominal_void, 0, ',', '.') . ')';
            })->implode(', ');
        });

        $result = [];
        foreach ($rows as $row) {
            $exactKey = (int) $row->pesanan_penjualan_id . '|' . Carbon::parse($row->voided_at)->format('Y-m-d H:i:s');
            $dateKey = (int) $row->pesanan_penjualan_id . '|' . Carbon::parse($row->voided_at)->toDateString();
            $result[(int) $row->id] = $paymentMap->get($exactKey)
                ?? $rowsByDateFallback->get($dateKey)
                ?? '-';
        }

        return $result;
    }
}
