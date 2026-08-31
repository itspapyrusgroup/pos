<?php

namespace App\Http\Controllers;

use App\Models\DiskonOtomatis;
use App\Models\PesananPenjualan;
use App\Models\VoucherPromosi;
use App\Services\XlsxExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LaporanPromosiController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'cabang_id' => ['nullable', 'exists:cabang,id'],
            'sumber' => ['nullable', 'in:SEMUA,VOUCHER,OTOMATIS'],
            'kode' => ['nullable', 'string', 'max:30'],
        ]);

        $dateFrom = $validated['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $validated['date_to'] ?? now()->toDateString();
        if (Carbon::parse($dateFrom)->gt(Carbon::parse($dateTo))) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $cabangId = $this->resolveCabangFilter($request);
        $sumber = $validated['sumber'] ?? 'SEMUA';
        $kodeFilter = strtoupper(trim((string) ($validated['kode'] ?? '')));

        $query = PesananPenjualan::query()
            ->with(['cabang:id,nama', 'kantongOrder:id,pesanan_penjualan_id,nomor_ko', 'kasir:id,name,username'])
            ->whereNotNull('catatan')
            ->where('catatan', 'like', '%Promo dipakai:%')
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->latest('created_at');
        $this->applyCabangScope($query);
        if ($cabangId) {
            $query->where('cabang_id', $cabangId);
        }

        $orders = $query->get([
            'id',
            'nomor_so',
            'cabang_id',
            'kasir_user_id',
            'catatan',
            'created_at',
        ]);

        $voucherCodes = VoucherPromosi::query()->pluck('kode')->map(fn ($x) => strtoupper((string) $x))->all();
        $diskonCodes = DiskonOtomatis::query()->pluck('nama')->map(fn ($x) => strtoupper((string) $x))->all();

        $detailRows = collect();
        foreach ($orders as $order) {
            $promos = $this->extractPromos((string) $order->catatan);
            foreach ($promos as $promo) {
                $kode = strtoupper((string) $promo['kode']);
                $resolvedSumber = str_starts_with($kode, 'AUTO-')
                    ? 'OTOMATIS'
                    : (in_array($kode, $voucherCodes, true)
                        ? 'VOUCHER'
                        : (in_array($kode, $diskonCodes, true) ? 'OTOMATIS' : 'VOUCHER'));

                if ($sumber !== 'SEMUA' && $resolvedSumber !== $sumber) {
                    continue;
                }
                if ($kodeFilter !== '' && !str_contains($kode, $kodeFilter)) {
                    continue;
                }

                $detailRows->push([
                    'tanggal' => $order->created_at,
                    'cabang_nama' => $order->cabang?->nama ?? '-',
                    'kasir_nama' => $order->kasir?->name ?? '-',
                    'nomor_so' => $order->nomor_so,
                    'nomor_ko' => $order->kantongOrder?->nomor_ko ?? '-',
                    'kode' => $kode,
                    'sumber' => $resolvedSumber,
                    'diskon' => (float) $promo['diskon'],
                    'order_id' => (int) $order->id,
                ]);
            }
        }

        $detailRows = $detailRows
            ->sortByDesc(fn ($row) => optional($row['tanggal'])->timestamp ?? 0)
            ->values();

        $rekapPromo = $detailRows
            ->groupBy(fn ($row) => $row['sumber'] . '|' . $row['kode'])
            ->map(function (Collection $rows, string $key) {
                [$sumber, $kode] = explode('|', $key, 2);
                return [
                    'sumber' => $sumber,
                    'kode' => $kode,
                    'jumlah_pemakaian' => $rows->count(),
                    'total_diskon' => (float) $rows->sum('diskon'),
                ];
            })
            ->sortByDesc('jumlah_pemakaian')
            ->values();

        if ($request->boolean('export_xlsx')) {
            $rowsXlsx = $detailRows->map(function (array $row) {
                return [
                    optional($row['tanggal'])->format('Y-m-d H:i'),
                    $row['cabang_nama'],
                    $row['kasir_nama'],
                    $row['nomor_so'],
                    $row['nomor_ko'],
                    $row['sumber'],
                    $row['kode'],
                    (float) $row['diskon'],
                ];
            })->all();

            return app(XlsxExportService::class)->download(
                'laporan-promosi-' . now()->format('Ymd-His') . '.xlsx',
                ['Tanggal', 'Cabang', 'Kasir', 'No SO', 'No KO', 'Sumber', 'Kode Promo', 'Diskon'],
                $rowsXlsx,
                'Promosi'
            );
        }

        return view('pages.pos.laporan-promosi', [
            'cabangs' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'cabang_id' => $cabangId,
                'sumber' => $sumber,
                'kode' => $kodeFilter,
            ],
            'summary' => [
                'jumlah_transaksi_promo' => (int) $detailRows->unique('order_id')->count(),
                'jumlah_pemakaian_promo' => (int) $detailRows->count(),
                'total_diskon' => (float) $detailRows->sum('diskon'),
            ],
            'rekapPromo' => $rekapPromo,
            'detailRows' => $detailRows,
        ]);
    }

    private function extractPromos(string $catatan): array
    {
        if ($catatan === '') {
            return [];
        }

        preg_match_all('/Promo dipakai:\s*([^\(\r\n]+)\s*\(diskon Rp\s*([0-9\.\,]+)\)/i', $catatan, $matches, PREG_SET_ORDER);
        if (empty($matches)) {
            return [];
        }

        $rows = [];
        foreach ($matches as $m) {
            $kode = strtoupper(trim((string) ($m[1] ?? '')));
            $nominalRaw = str_replace(['.', ','], ['', '.'], (string) ($m[2] ?? '0'));
            $diskon = is_numeric($nominalRaw) ? (float) $nominalRaw : 0.0;
            if ($kode === '') {
                continue;
            }

            $rows[] = [
                'kode' => $kode,
                'diskon' => $diskon,
            ];
        }

        return $rows;
    }
}
