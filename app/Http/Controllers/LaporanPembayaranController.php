<?php

namespace App\Http\Controllers;

use App\Models\MetodePembayaran;
use App\Models\PembayaranPenjualan;
use App\Models\PenjualanVoidLog;
use App\Models\PesananPenjualan;
use App\Models\User;
use App\Services\XlsxExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LaporanPembayaranController extends Controller
{
    public function detail(Request $request)
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'cabang_id' => ['nullable', 'exists:cabang,id'],
            'kasir_user_id' => ['nullable', 'exists:users,id'],
            'metode_pembayaran_id' => ['nullable', 'exists:metode_pembayaran,id'],
            'no_ko' => ['nullable', 'string', 'max:50'],
        ]);

        $dateFrom = $validated['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $validated['date_to'] ?? now()->toDateString();
        if (Carbon::parse($dateFrom)->gt(Carbon::parse($dateTo))) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $cabangId = $this->resolveCabangFilter($request);
        $kasirId = isset($validated['kasir_user_id']) ? (int) $validated['kasir_user_id'] : null;
        $metodeId = isset($validated['metode_pembayaran_id']) ? (int) $validated['metode_pembayaran_id'] : null;
        $noKo = trim((string) ($validated['no_ko'] ?? ''));

        $baseQuery = PembayaranPenjualan::query()
            ->from('pembayaran_penjualan as pp')
            ->join('pesanan_penjualan as pz', 'pz.id', '=', 'pp.pesanan_penjualan_id')
            ->leftJoin('kantong_order as ko', 'ko.pesanan_penjualan_id', '=', 'pz.id')
            ->leftJoin('users as u', 'u.id', '=', 'pp.kasir_user_id')
            ->join('metode_pembayaran as mp', 'mp.id', '=', 'pp.metode_pembayaran_id')
            ->whereDate('pp.tanggal_bayar', '>=', $dateFrom)
            ->whereDate('pp.tanggal_bayar', '<=', $dateTo);

        $this->applyCabangScope($baseQuery, 'pz.cabang_id');
        if ($cabangId) {
            $baseQuery->where('pz.cabang_id', $cabangId);
        }
        if ($kasirId) {
            $baseQuery->where('pp.kasir_user_id', $kasirId);
        }
        if ($metodeId) {
            $baseQuery->where('pp.metode_pembayaran_id', $metodeId);
        }
        if ($noKo !== '') {
            $baseQuery->where('ko.nomor_ko', 'like', '%' . $noKo . '%');
        }

        $rows = (clone $baseQuery)
            ->selectRaw('
                pz.id as pesanan_penjualan_id,
                DATE(pp.tanggal_bayar) as tanggal_group,
                MAX(pp.tanggal_bayar) as tanggal_bayar,
                COALESCE(ko.nomor_ko, \'-\') as nomor_ko,
                COALESCE(NULLIF(pz.customer_name, \'\'), \'-\') as customer_name,
                COALESCE(u.name, \'Tanpa Kasir\') as kasir_nama,
                mp.nama as metode_pembayaran_nama,
                pp.metode_pembayaran_id,
                COALESCE(SUM(CASE WHEN pp.nominal > 0 THEN pp.nominal ELSE 0 END), 0) as gross_nominal,
                COALESCE(ABS(SUM(CASE WHEN pp.nominal < 0 THEN pp.nominal ELSE 0 END)), 0) as void_nominal,
                COALESCE(SUM(pp.nominal), 0) as net_nominal
            ')
            ->groupBy(
                'pz.id',
                DB::raw('DATE(pp.tanggal_bayar)'),
                'ko.nomor_ko',
                'pz.customer_name',
                'u.name',
                'mp.nama',
                'pp.metode_pembayaran_id'
            )
            ->orderByDesc(DB::raw('MAX(pp.tanggal_bayar)'))
            ->orderByDesc('pz.id')
            ->paginate(25)
            ->withQueryString();

        $summary = [
            'jumlah_ko' => (int) (clone $baseQuery)
                ->distinct('pz.id')
                ->count('pz.id'),
            'total_gross' => (float) (clone $baseQuery)
                ->selectRaw('COALESCE(SUM(CASE WHEN pp.nominal > 0 THEN pp.nominal ELSE 0 END), 0) as total')
                ->value('total'),
            'total_void' => (float) (clone $baseQuery)
                ->selectRaw('COALESCE(ABS(SUM(CASE WHEN pp.nominal < 0 THEN pp.nominal ELSE 0 END)), 0) as total')
                ->value('total'),
        ];
        $summary['total_net'] = $summary['total_gross'] - $summary['total_void'];

        if ($request->boolean('export_xlsx')) {
            $exportRows = (clone $baseQuery)
                ->selectRaw('
                    pz.id as pesanan_penjualan_id,
                    MAX(pp.tanggal_bayar) as tanggal_bayar,
                    COALESCE(ko.nomor_ko, \'-\') as nomor_ko,
                    COALESCE(NULLIF(pz.customer_name, \'\'), \'-\') as customer_name,
                    COALESCE(u.name, \'Tanpa Kasir\') as kasir_nama,
                    mp.nama as metode_pembayaran_nama,
                    COALESCE(SUM(CASE WHEN pp.nominal > 0 THEN pp.nominal ELSE 0 END), 0) as gross_nominal,
                    COALESCE(ABS(SUM(CASE WHEN pp.nominal < 0 THEN pp.nominal ELSE 0 END)), 0) as void_nominal,
                    COALESCE(SUM(pp.nominal), 0) as net_nominal
                ')
                ->groupBy(
                    'pz.id',
                    DB::raw('DATE(pp.tanggal_bayar)'),
                    'ko.nomor_ko',
                    'pz.customer_name',
                    'u.name',
                    'mp.nama',
                    'pp.metode_pembayaran_id'
                )
                ->orderByDesc(DB::raw('MAX(pp.tanggal_bayar)'))
                ->orderByDesc('pz.id')
                ->get();

            $rowsXlsx = $exportRows->map(function ($row) {
                return [
                    $row->tanggal_bayar ? Carbon::parse($row->tanggal_bayar)->format('Y-m-d H:i') : '-',
                    $row->nomor_ko ?? '-',
                    $row->customer_name ?? '-',
                    $row->kasir_nama ?? '-',
                    $row->metode_pembayaran_nama ?? '-',
                    (float) $row->gross_nominal,
                    (float) $row->void_nominal,
                    (float) $row->net_nominal,
                ];
            })->all();

            return app(XlsxExportService::class)->download(
                'laporan-pembayaran-detail-' . now()->format('Ymd-His') . '.xlsx',
                ['Tanggal Bayar', 'No KO', 'Nama', 'Kasir', 'Metode Bayar', 'Gross', 'Void', 'Net'],
                $rowsXlsx,
                'Pembayaran Detail'
            );
        }

        return view('pages.pos.laporan-pembayaran-detail', [
            'cabangs' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'kasirList' => $this->resolveKasirList($dateFrom, $dateTo, $cabangId),
            'metodeList' => $this->resolveMetodeList($dateFrom, $dateTo, $cabangId, $metodeId),
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'cabang_id' => $cabangId,
                'kasir_user_id' => $kasirId,
                'metode_pembayaran_id' => $metodeId,
                'no_ko' => $noKo,
            ],
            'rows' => $rows,
            'summary' => $summary,
        ]);
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'cabang_id' => ['nullable', 'exists:cabang,id'],
            'kasir_user_id' => ['nullable', 'exists:users,id'],
            'metode_pembayaran_id' => ['nullable', 'exists:metode_pembayaran,id'],
            'no_ko' => ['nullable', 'string', 'max:50'],
            'mode' => ['nullable', 'in:rekap,harian,belum_lunas'],
        ]);

        $dateFrom = $validated['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $validated['date_to'] ?? now()->toDateString();
        if (Carbon::parse($dateFrom)->gt(Carbon::parse($dateTo))) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $mode = $validated['mode'] ?? 'rekap';
        $noKo = trim((string) ($validated['no_ko'] ?? ''));
        $cabangId = $this->resolveCabangFilter($request);
        $kasirId = isset($validated['kasir_user_id']) ? (int) $validated['kasir_user_id'] : null;
        $metodeId = isset($validated['metode_pembayaran_id']) ? (int) $validated['metode_pembayaran_id'] : null;

        if ($mode === 'belum_lunas') {
            $query = PesananPenjualan::query()
                ->from('pesanan_penjualan as pz')
                ->leftJoin('kantong_order as ko', 'ko.pesanan_penjualan_id', '=', 'pz.id')
                ->whereDate('pz.created_at', '>=', $dateFrom)
                ->whereDate('pz.created_at', '<=', $dateTo)
                ->where('pz.balance', '>', 0)
                ->whereNotIn('pz.status_pembayaran', ['PAID', 'VOID', 'CANCELLED'])
                ->select([
                    'pz.id',
                    'pz.created_at',
                    'pz.customer_name',
                    'pz.total',
                    'pz.paid_total',
                    'pz.balance',
                    'pz.status_pembayaran',
                    'ko.nomor_ko',
                ]);

            $this->applyCabangScope($query, 'pz.cabang_id');
            if ($cabangId) {
                $query->where('pz.cabang_id', $cabangId);
            }
            if ($noKo !== '') {
                $query->where('ko.nomor_ko', 'like', '%' . $noKo . '%');
            }

            $summaryQuery = clone $query;
            $rows = $query
                ->orderByDesc('pz.created_at')
                ->paginate(25)
                ->withQueryString();

            if ($request->boolean('export_xlsx')) {
                $exportRows = (clone $summaryQuery)
                    ->orderByDesc('pz.created_at')
                    ->get();

                $rowsXlsx = $exportRows->map(function ($row) {
                    return [
                        Carbon::parse($row->created_at)->format('Y-m-d H:i'),
                        $row->nomor_ko ?? '-',
                        $row->customer_name ?: '-',
                        (string) $row->status_pembayaran,
                        (float) $row->total,
                        (float) $row->paid_total,
                        (float) $row->balance,
                    ];
                })->all();

                return app(XlsxExportService::class)->download(
                    'laporan-pembayaran-belum-lunas-' . now()->format('Ymd-His') . '.xlsx',
                    ['Tanggal', 'No KO', 'Pelanggan', 'Status', 'Total', 'Terbayar', 'Sisa'],
                    $rowsXlsx,
                    'Belum Lunas'
                );
            }

            return view('pages.pos.laporan-pembayaran', [
                'cabangs' => $this->accessibleCabangQuery()->get(['id', 'nama']),
                'kasirList' => collect(),
                'metodeList' => collect(),
                'filters' => [
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'cabang_id' => $cabangId,
                    'kasir_user_id' => null,
                    'metode_pembayaran_id' => null,
                    'no_ko' => $noKo,
                    'mode' => $mode,
                ],
                'rows' => $rows,
                'totalsByMetode' => [],
                'grandTotal' => 0.0,
                'summary' => [
                    'jumlah_transaksi' => (int) (clone $summaryQuery)->count(),
                    'total_nominal' => (float) (clone $summaryQuery)->sum('pz.balance'),
                ],
            ]);
        }

        $baseQuery = PembayaranPenjualan::query()
            ->from('pembayaran_penjualan as pp')
            ->join('pesanan_penjualan as pz', 'pz.id', '=', 'pp.pesanan_penjualan_id')
            ->whereDate('pp.tanggal_bayar', '>=', $dateFrom)
            ->whereDate('pp.tanggal_bayar', '<=', $dateTo);

        $this->applyCabangScope($baseQuery, 'pz.cabang_id');
        if ($cabangId) {
            $baseQuery->where('pz.cabang_id', $cabangId);
        }
        if ($kasirId) {
            $baseQuery->where('pp.kasir_user_id', $kasirId);
        }
        if ($metodeId) {
            $baseQuery->where('pp.metode_pembayaran_id', $metodeId);
        }

        $kasirList = $this->resolveKasirList($dateFrom, $dateTo, $cabangId);
        $metodeList = $this->resolveMetodeList($dateFrom, $dateTo, $cabangId, $metodeId);
        $metodeIds = $metodeList->pluck('id')->map(fn ($id) => (int) $id)->all();

        $rows = collect();
        $totalsByMetode = [];
        $grandTotal = 0.0;
        $voidDetailsMap = [];

        if ($mode === 'harian') {
            [$rows, $totalsByMetode, $grandTotal] = $this->buildHarianRows(
                clone $baseQuery,
                $dateFrom,
                $dateTo,
                $metodeIds
            );
        } else {
            [$rows, $totalsByMetode, $grandTotal] = $this->buildRekapRows(
                clone $baseQuery,
                $metodeIds
            );
        }

        $voidDetailsMap = $this->buildVoidDetailsMap(
            $dateFrom,
            $dateTo,
            $cabangId,
            $kasirId,
            $metodeId,
            $mode
        );

        if ($request->boolean('export_xlsx')) {
            [$headers, $rowsXlsx] = $this->buildExportRows(
                clone $baseQuery,
                $metodeList,
                $mode,
                $dateFrom,
                $dateTo
            );

            return app(XlsxExportService::class)->download(
                'laporan-pembayaran-' . $mode . '-' . now()->format('Ymd-His') . '.xlsx',
                $headers,
                $rowsXlsx,
                'Pembayaran'
            );
        }

        $summaryGross = (float) (clone $baseQuery)
            ->selectRaw('COALESCE(SUM(CASE WHEN pp.nominal > 0 THEN pp.nominal ELSE 0 END), 0) as total')
            ->value('total');
        $summaryVoid = (float) (clone $baseQuery)
            ->selectRaw('COALESCE(ABS(SUM(CASE WHEN pp.nominal < 0 THEN pp.nominal ELSE 0 END)), 0) as total')
            ->value('total');

        return view('pages.pos.laporan-pembayaran', [
            'cabangs' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'kasirList' => $kasirList,
            'metodeList' => $metodeList,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'cabang_id' => $cabangId,
                'kasir_user_id' => $kasirId,
                'metode_pembayaran_id' => $metodeId,
                'no_ko' => $noKo,
                'mode' => $mode,
            ],
            'rows' => $rows,
            'totalsByMetode' => $totalsByMetode,
            'grandTotal' => $grandTotal,
            'voidDetailsMap' => $voidDetailsMap,
            'summary' => [
                'jumlah_transaksi' => (int) (clone $baseQuery)->where('pp.nominal', '>', 0)->count(),
                'total_nominal' => $summaryGross - $summaryVoid,
                'total_nominal_kotor' => $summaryGross,
                'total_nominal_void' => $summaryVoid,
            ],
        ]);
    }

    private function resolveKasirList(string $dateFrom, string $dateTo, ?int $cabangId): Collection
    {
        $query = PembayaranPenjualan::query()
            ->from('pembayaran_penjualan as pp')
            ->join('pesanan_penjualan as pz', 'pz.id', '=', 'pp.pesanan_penjualan_id')
            ->whereNotNull('pp.kasir_user_id')
            ->where('pp.nominal', '!=', 0)
            ->whereDate('pp.tanggal_bayar', '>=', $dateFrom)
            ->whereDate('pp.tanggal_bayar', '<=', $dateTo);

        $this->applyCabangScope($query, 'pz.cabang_id');
        if ($cabangId) {
            $query->where('pz.cabang_id', $cabangId);
        }

        $kasirIds = $query
            ->distinct()
            ->pluck('pp.kasir_user_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (empty($kasirIds)) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $kasirIds)
            ->orderBy('name')
            ->get(['id', 'name', 'username']);
    }

    private function resolveMetodeList(string $dateFrom, string $dateTo, ?int $cabangId, ?int $metodeId): Collection
    {
        $query = MetodePembayaran::query()
            ->select('metode_pembayaran.id', 'metode_pembayaran.nama', 'metode_pembayaran.kode')
            ->where('metode_pembayaran.status', true)
            ->orderBy('metode_pembayaran.nama');

        if ($metodeId) {
            $query->where('metode_pembayaran.id', $metodeId);
        } else {
            $query->whereIn('metode_pembayaran.id', function ($sub) use ($dateFrom, $dateTo, $cabangId) {
                $sub->from('pembayaran_penjualan as pp')
                    ->join('pesanan_penjualan as pz', 'pz.id', '=', 'pp.pesanan_penjualan_id')
                    ->select('pp.metode_pembayaran_id')
                    ->where('pp.nominal', '!=', 0)
                    ->whereDate('pp.tanggal_bayar', '>=', $dateFrom)
                    ->whereDate('pp.tanggal_bayar', '<=', $dateTo);

                if ($cabangId) {
                    $sub->where('pz.cabang_id', $cabangId);
                }

                $ids = $this->accessibleCabangIds();
                if (!empty($ids)) {
                    $sub->whereIn('pz.cabang_id', $ids);
                }
            });
        }

        return $query->get();
    }

    private function buildRekapRows($baseQuery, array $metodeIds): array
    {
        $grouped = $baseQuery
            ->leftJoin('users as u', 'u.id', '=', 'pp.kasir_user_id')
            ->selectRaw('
                pp.kasir_user_id,
                COALESCE(u.name, \'Tanpa Kasir\') as kasir_nama,
                pp.metode_pembayaran_id,
                COALESCE(SUM(CASE WHEN pp.nominal > 0 THEN pp.nominal ELSE 0 END), 0) as nominal_kotor,
                COALESCE(ABS(SUM(CASE WHEN pp.nominal < 0 THEN pp.nominal ELSE 0 END)), 0) as nominal_void,
                COALESCE(SUM(pp.nominal), 0) as nominal_bersih
            ')
            ->groupBy('pp.kasir_user_id', 'u.name', 'pp.metode_pembayaran_id')
            ->get();

        $matrix = [];
        foreach ($grouped as $row) {
            $kasirKey = (string) ($row->kasir_user_id ?? 0);
            $metodeKey = (int) $row->metode_pembayaran_id;

            if (!isset($matrix[$kasirKey])) {
                $matrix[$kasirKey] = [
                    'label' => $row->kasir_nama,
                    'amounts' => [],
                    'row_total' => 0.0,
                ];
            }

            $gross = (float) $row->nominal_kotor;
            $void = (float) $row->nominal_void;
            $net = (float) $row->nominal_bersih;
            $matrix[$kasirKey]['amounts'][$metodeKey] = [
                'gross' => $gross,
                'void' => $void,
                'net' => $net,
                'detail_key' => $this->makeVoidDetailKey('rekap', $kasirKey, $metodeKey),
            ];
            $matrix[$kasirKey]['row_total'] += $net;
        }

        $totalsByMetode = array_fill_keys($metodeIds, 0.0);
        $grandTotal = 0.0;

        $rows = collect($matrix)->map(function (array $row) use (&$totalsByMetode, &$grandTotal, $metodeIds) {
            foreach ($metodeIds as $metodeId) {
                $nominal = (float) ($row['amounts'][$metodeId]['net'] ?? 0);
                $totalsByMetode[$metodeId] += $nominal;
            }
            $grandTotal += (float) $row['row_total'];

            return [
                'label' => $row['label'],
                'amounts' => $row['amounts'],
                'row_total' => (float) $row['row_total'],
            ];
        })->sortByDesc('row_total')->values();

        return [$rows, $totalsByMetode, $grandTotal];
    }

    private function buildHarianRows($baseQuery, string $dateFrom, string $dateTo, array $metodeIds): array
    {
        $grouped = $baseQuery
            ->selectRaw('
                DATE(pp.tanggal_bayar) as tanggal,
                pp.metode_pembayaran_id,
                COALESCE(SUM(CASE WHEN pp.nominal > 0 THEN pp.nominal ELSE 0 END), 0) as nominal_kotor,
                COALESCE(ABS(SUM(CASE WHEN pp.nominal < 0 THEN pp.nominal ELSE 0 END)), 0) as nominal_void,
                COALESCE(SUM(pp.nominal), 0) as nominal_bersih
            ')
            ->groupBy(DB::raw('DATE(pp.tanggal_bayar)'), 'pp.metode_pembayaran_id')
            ->get();

        $byDate = [];
        foreach ($grouped as $row) {
            $dateKey = (string) $row->tanggal;
            $metodeKey = (int) $row->metode_pembayaran_id;
            if (!isset($byDate[$dateKey])) {
                $byDate[$dateKey] = [];
            }
            $byDate[$dateKey][$metodeKey] = [
                'gross' => (float) $row->nominal_kotor,
                'void' => (float) $row->nominal_void,
                'net' => (float) $row->nominal_bersih,
                'detail_key' => $this->makeVoidDetailKey('harian', $dateKey, $metodeKey),
            ];
        }

        $totalsByMetode = array_fill_keys($metodeIds, 0.0);
        $grandTotal = 0.0;
        $rows = collect();

        $cursor = Carbon::parse($dateFrom);
        $end = Carbon::parse($dateTo);
        while ($cursor->lte($end)) {
            $dateKey = $cursor->toDateString();
            $amounts = $byDate[$dateKey] ?? [];
            $rowTotal = 0.0;

            foreach ($metodeIds as $metodeId) {
                $nominal = (float) ($amounts[$metodeId]['net'] ?? 0);
                $totalsByMetode[$metodeId] += $nominal;
                $rowTotal += $nominal;
            }
            $grandTotal += $rowTotal;

            $rows->push([
                'label' => $cursor->format('d-m-Y'),
                'amounts' => $amounts,
                'row_total' => $rowTotal,
            ]);

            $cursor->addDay();
        }

        return [$rows, $totalsByMetode, $grandTotal];
    }

    private function buildExportRows($baseQuery, Collection $metodeList, string $mode, string $dateFrom, string $dateTo): array
    {
        $headers = [$mode === 'harian' ? 'Tanggal' : 'Nama Kasir'];
        foreach ($metodeList as $metode) {
            $headers[] = $metode->nama . ' (Kotor)';
            $headers[] = $metode->nama . ' (Void)';
            $headers[] = $metode->nama . ' (Bersih)';
        }
        $headers[] = 'Total Kotor';
        $headers[] = 'Total Void';
        $headers[] = 'Total Bersih';

        $metodeIds = $metodeList->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (empty($metodeIds)) {
            return [$headers, []];
        }

        if ($mode === 'harian') {
            $grouped = $baseQuery
            ->selectRaw('
                DATE(pp.tanggal_bayar) as tanggal,
                pp.metode_pembayaran_id,
                COALESCE(SUM(CASE WHEN pp.nominal > 0 THEN pp.nominal ELSE 0 END), 0) as nominal_kotor,
                COALESCE(ABS(SUM(CASE WHEN pp.nominal < 0 THEN pp.nominal ELSE 0 END)), 0) as nominal_void,
                COALESCE(SUM(pp.nominal), 0) as nominal_bersih
            ')
                ->groupBy(DB::raw('DATE(pp.tanggal_bayar)'), 'pp.metode_pembayaran_id')
                ->get();

            $byDate = [];
            foreach ($grouped as $row) {
                $dateKey = (string) $row->tanggal;
                $metodeKey = (int) $row->metode_pembayaran_id;
                if (!isset($byDate[$dateKey])) {
                    $byDate[$dateKey] = [];
                }
                $byDate[$dateKey][$metodeKey] = [
                    'kotor' => (float) $row->nominal_kotor,
                    'void' => (float) $row->nominal_void,
                    'bersih' => (float) $row->nominal_bersih,
                ];
            }

            $rowsXlsx = [];
            $cursor = Carbon::parse($dateFrom);
            $end = Carbon::parse($dateTo);
            while ($cursor->lte($end)) {
                $dateKey = $cursor->toDateString();
                $amounts = $byDate[$dateKey] ?? [];
                $line = [$cursor->format('d-m-Y')];
                $totalKotor = 0.0;
                $totalVoid = 0.0;
                $totalBersih = 0.0;

                foreach ($metodeIds as $metodeId) {
                    $kotor = (float) ($amounts[$metodeId]['kotor'] ?? 0);
                    $void = (float) ($amounts[$metodeId]['void'] ?? 0);
                    $bersih = (float) ($amounts[$metodeId]['bersih'] ?? 0);
                    $line[] = $kotor;
                    $line[] = $void;
                    $line[] = $bersih;
                    $totalKotor += $kotor;
                    $totalVoid += $void;
                    $totalBersih += $bersih;
                }

                $line[] = $totalKotor;
                $line[] = $totalVoid;
                $line[] = $totalBersih;
                $rowsXlsx[] = $line;
                $cursor->addDay();
            }

            return [$headers, $rowsXlsx];
        }

        $grouped = $baseQuery
            ->leftJoin('users as u', 'u.id', '=', 'pp.kasir_user_id')
            ->selectRaw('
                pp.kasir_user_id,
                COALESCE(u.name, \'Tanpa Kasir\') as kasir_nama,
                pp.metode_pembayaran_id,
                COALESCE(SUM(CASE WHEN pp.nominal > 0 THEN pp.nominal ELSE 0 END), 0) as nominal_kotor,
                COALESCE(ABS(SUM(CASE WHEN pp.nominal < 0 THEN pp.nominal ELSE 0 END)), 0) as nominal_void,
                COALESCE(SUM(pp.nominal), 0) as nominal_bersih
            ')
            ->groupBy('pp.kasir_user_id', 'u.name', 'pp.metode_pembayaran_id')
            ->get();

        $matrix = [];
        foreach ($grouped as $row) {
            $kasirKey = (string) ($row->kasir_user_id ?? 0);
            $metodeKey = (int) $row->metode_pembayaran_id;
            if (!isset($matrix[$kasirKey])) {
                $matrix[$kasirKey] = [
                    'label' => $row->kasir_nama,
                    'amounts' => [],
                    'total_kotor' => 0.0,
                    'total_void' => 0.0,
                    'total_bersih' => 0.0,
                ];
            }

            $kotor = (float) $row->nominal_kotor;
            $void = (float) $row->nominal_void;
            $bersih = (float) $row->nominal_bersih;
            $matrix[$kasirKey]['amounts'][$metodeKey] = [
                'kotor' => $kotor,
                'void' => $void,
                'bersih' => $bersih,
            ];
            $matrix[$kasirKey]['total_kotor'] += $kotor;
            $matrix[$kasirKey]['total_void'] += $void;
            $matrix[$kasirKey]['total_bersih'] += $bersih;
        }

        $rowsXlsx = collect($matrix)
            ->sortByDesc('total_bersih')
            ->values()
            ->map(function (array $row) use ($metodeIds) {
                $line = [$row['label']];
                foreach ($metodeIds as $metodeId) {
                    $line[] = (float) ($row['amounts'][$metodeId]['kotor'] ?? 0);
                    $line[] = (float) ($row['amounts'][$metodeId]['void'] ?? 0);
                    $line[] = (float) ($row['amounts'][$metodeId]['bersih'] ?? 0);
                }
                $line[] = (float) $row['total_kotor'];
                $line[] = (float) $row['total_void'];
                $line[] = (float) $row['total_bersih'];
                return $line;
            })
            ->all();

        return [$headers, $rowsXlsx];
    }

    private function buildVoidDetailsMap(
        string $dateFrom,
        string $dateTo,
        ?int $cabangId,
        ?int $kasirId,
        ?int $metodeId,
        string $mode
    ): array {
        $query = PembayaranPenjualan::query()
            ->from('pembayaran_penjualan as pp')
            ->join('pesanan_penjualan as pz', 'pz.id', '=', 'pp.pesanan_penjualan_id')
            ->leftJoin('kantong_order as ko', 'ko.pesanan_penjualan_id', '=', 'pz.id')
            ->where('pp.nominal', '<', 0)
            ->whereDate('pp.tanggal_bayar', '>=', $dateFrom)
            ->whereDate('pp.tanggal_bayar', '<=', $dateTo)
            ->selectRaw('
                pp.id,
                pp.pesanan_penjualan_id,
                pp.metode_pembayaran_id,
                pp.kasir_user_id,
                pp.tanggal_bayar,
                DATE(pp.tanggal_bayar) as tanggal_group,
                ABS(pp.nominal) as nominal_void_pembayaran,
                COALESCE(ko.nomor_ko, \'-\') as nomor_ko,
                COALESCE(NULLIF(pz.customer_name, \'\'), \'-\') as customer_name
            ')
            ->orderByDesc('pp.tanggal_bayar')
            ->orderByDesc('pp.id');

        $this->applyCabangScope($query, 'pz.cabang_id');
        if ($cabangId) {
            $query->where('pz.cabang_id', $cabangId);
        }
        if ($kasirId) {
            $query->where('pp.kasir_user_id', $kasirId);
        }
        if ($metodeId) {
            $query->where('pp.metode_pembayaran_id', $metodeId);
        }

        $payments = $query->get();
        if ($payments->isEmpty()) {
            return [];
        }

        $voidLogsByOrder = PenjualanVoidLog::query()
            ->whereIn('pesanan_penjualan_id', $payments->pluck('pesanan_penjualan_id')->unique()->all())
            ->orderByDesc('voided_at')
            ->get()
            ->groupBy('pesanan_penjualan_id');

        $detailsMap = [];
        foreach ($payments as $payment) {
            $groupValue = $mode === 'harian'
                ? (string) $payment->tanggal_group
                : (string) ($payment->kasir_user_id ?? 0);
            $detailKey = $this->makeVoidDetailKey($mode, $groupValue, (int) $payment->metode_pembayaran_id);
            $logs = collect($voidLogsByOrder->get((int) $payment->pesanan_penjualan_id, collect()));
            $matchedLogs = $logs->filter(function ($log) use ($payment) {
                return $log->voided_at
                    && $payment->tanggal_bayar
                    && $log->voided_at->format('Y-m-d H:i:s') === Carbon::parse($payment->tanggal_bayar)->format('Y-m-d H:i:s');
            })->values();

            if ($matchedLogs->isEmpty()) {
                $matchedLogs = $logs->filter(function ($log) use ($payment) {
                    return $log->voided_at
                        && $payment->tanggal_bayar
                        && $log->voided_at->toDateString() === Carbon::parse($payment->tanggal_bayar)->toDateString();
                })->values();
            }

            if ($matchedLogs->isEmpty()) {
                $matchedLogs = $logs->take(3)->values();
            }

            $detailsMap[$detailKey][] = [
                'no_ko' => $payment->nomor_ko ?? '-',
                'nama' => $payment->customer_name ?? '-',
                'nominal' => (float) $payment->nominal_void_pembayaran,
                'tanggal_void' => $payment->tanggal_bayar
                    ? Carbon::parse($payment->tanggal_bayar)->format('d-m-Y H:i')
                    : '-',
                'void_info' => $matchedLogs->map(function ($log) {
                    $parts = [
                        trim((string) $log->tipe_void),
                        trim((string) $log->tipe_transaksi),
                    ];
                    $info = implode(' / ', array_filter($parts));
                    $effectiveDate = $log->void_effective_date?->format('d-m-Y') ?? '-';
                    $reason = trim((string) $log->alasan) ?: '-';

                    return trim($info) !== ''
                        ? $info . ' | Efektif: ' . $effectiveDate . ' | Alasan: ' . $reason
                        : 'Efektif: ' . $effectiveDate . ' | Alasan: ' . $reason;
                })->all(),
            ];
        }

        return $detailsMap;
    }

    private function makeVoidDetailKey(string $mode, string $groupValue, int $metodeId): string
    {
        return $mode . '|' . $groupValue . '|' . $metodeId;
    }
}
