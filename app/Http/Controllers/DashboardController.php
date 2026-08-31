<?php

namespace App\Http\Controllers;

use App\Models\PembayaranPenjualan;
use App\Models\PenjualanVoidLog;
use App\Models\PesananPenjualan;
use App\Models\PesananPenjualanItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->hasPermission('dashboard.view')) {
            return view('pages.dashboard.index', [
                'isShortcutMode' => true,
                'shortcutMenus' => $this->shortcutMenus($user),
            ]);
        }

        $validated = $request->validate([
            'cabang_id' => ['nullable', 'array'],
            'cabang_id.*' => ['nullable', 'exists:cabang,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $dateFrom = $validated['date_from'] ?? now()->toDateString();
        $dateTo = $validated['date_to'] ?? now()->toDateString();
        $selectedCabangIds = $this->resolveCabangFilters($request);

        if (Carbon::parse($dateFrom)->gt(Carbon::parse($dateTo))) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $baseQuery = PesananPenjualan::query()
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);

        $this->applyCabangScope($baseQuery);

        if (!empty($selectedCabangIds)) {
            $baseQuery->whereIn('cabang_id', $selectedCabangIds);
        }

        $jumlahTransaksi = (clone $baseQuery)->count();
        $totalPenjualan = (float) (clone $baseQuery)->sum('total');

        $paymentQuery = PembayaranPenjualan::query()
            ->whereDate('tanggal_bayar', '>=', $dateFrom)
            ->whereDate('tanggal_bayar', '<=', $dateTo);
        $paymentQuery->whereHas('pesananPenjualan', function ($q) use ($selectedCabangIds) {
            $this->applyCabangScope($q);
            if (!empty($selectedCabangIds)) {
                $q->whereIn('cabang_id', $selectedCabangIds);
            }
        });

        $voidQuery = PenjualanVoidLog::query()
            ->whereDate('void_effective_date', '>=', $dateFrom)
            ->whereDate('void_effective_date', '<=', $dateTo);
        $voidQuery->whereHas('order', function ($q) use ($selectedCabangIds) {
            $this->applyCabangScope($q);
            if (!empty($selectedCabangIds)) {
                $q->whereIn('cabang_id', $selectedCabangIds);
            }
        });

        $totalPembayaranKotor = (float) (clone $paymentQuery)
            ->selectRaw('COALESCE(SUM(CASE WHEN nominal > 0 THEN nominal ELSE 0 END), 0) as total')
            ->value('total');
        $totalPembayaranVoid = (float) (clone $paymentQuery)
            ->selectRaw('COALESCE(ABS(SUM(CASE WHEN nominal < 0 THEN nominal ELSE 0 END)), 0) as total')
            ->value('total');
        $totalPembayaran = (float) (clone $paymentQuery)->sum('nominal');
        $totalVoidOrder = (float) (clone $voidQuery)->sum('nominal_void');
        $jumlahHari = Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)) + 1;
        $rataHarian = $jumlahHari > 0 ? ($totalPenjualan / $jumlahHari) : 0;

        $agregatPenjualanHarian = (clone $baseQuery)
            ->selectRaw('DATE(created_at) as tanggal, COUNT(*) as jumlah_transaksi, SUM(total) as total_penjualan')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        $agregatPembayaranHarian = (clone $paymentQuery)
            ->selectRaw('
                DATE(tanggal_bayar) as tanggal,
                COALESCE(SUM(CASE WHEN nominal > 0 THEN nominal ELSE 0 END), 0) as total_pembayaran_kotor,
                COALESCE(ABS(SUM(CASE WHEN nominal < 0 THEN nominal ELSE 0 END)), 0) as total_pembayaran_void,
                COALESCE(SUM(nominal), 0) as total_pembayaran_bersih
            ')
            ->groupBy(DB::raw('DATE(tanggal_bayar)'))
            ->orderBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        $agregatVoidHarian = (clone $voidQuery)
            ->selectRaw('void_effective_date as tanggal, SUM(nominal_void) as total_void')
            ->groupBy('void_effective_date')
            ->orderBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        $harian = [];
        $walker = Carbon::parse($dateFrom);
        $endDate = Carbon::parse($dateTo);
        while ($walker->lte($endDate)) {
            $tanggalKey = $walker->toDateString();
            $penjualan = $agregatPenjualanHarian->get($tanggalKey);
            $pembayaran = $agregatPembayaranHarian->get($tanggalKey);
            $void = $agregatVoidHarian->get($tanggalKey);

            $totalPembayaranKotorHarian = (float) ($pembayaran->total_pembayaran_kotor ?? 0);
            $totalPembayaranVoidHarian = (float) ($pembayaran->total_pembayaran_void ?? 0);
            $totalPembayaranBersihHarian = (float) ($pembayaran->total_pembayaran_bersih ?? 0);
            $totalVoidHarian = (float) ($void->total_void ?? 0);

            $harian[] = [
                'tanggal' => $tanggalKey,
                'label' => $walker->translatedFormat('d M Y'),
                'weekday_label' => $walker->translatedFormat('l'),
                'weekday_index' => (int) $walker->dayOfWeekIso,
                'jumlah_transaksi' => (int) ($penjualan->jumlah_transaksi ?? 0),
                'total_penjualan' => (float) ($penjualan->total_penjualan ?? 0),
                'total_pembayaran_kotor' => $totalPembayaranKotorHarian,
                'total_pembayaran_void' => $totalPembayaranVoidHarian,
                'total_pembayaran' => $totalPembayaranBersihHarian,
                'total_void' => $totalVoidHarian,
            ];

            $walker->addDay();
        }

        $paketBaseQuery = PesananPenjualanItem::query()
            ->from('pesanan_penjualan_item as ppi')
            ->join('pesanan_penjualan as pz', 'pz.id', '=', 'ppi.pesanan_penjualan_id')
            ->join('paket as pk', 'pk.id', '=', 'ppi.paket_id')
            ->whereDate('pz.created_at', '>=', $dateFrom)
            ->whereDate('pz.created_at', '<=', $dateTo)
            ->where('pz.status_pembayaran', 'PAID')
            ->whereNotNull('ppi.paket_id')
            ->where(function ($query) {
                $query->whereNull('ppi.is_void')->orWhere('ppi.is_void', false);
            });
        $this->applyCabangScope($paketBaseQuery, 'pz.cabang_id');
        if (!empty($selectedCabangIds)) {
            $paketBaseQuery->whereIn('pz.cabang_id', $selectedCabangIds);
        }

        $itemBaseQuery = PesananPenjualanItem::query()
            ->from('pesanan_penjualan_item as ppi')
            ->join('pesanan_penjualan as pz', 'pz.id', '=', 'ppi.pesanan_penjualan_id')
            ->join('produk as pr', 'pr.id', '=', 'ppi.produk_id')
            ->whereDate('pz.created_at', '>=', $dateFrom)
            ->whereDate('pz.created_at', '<=', $dateTo)
            ->where('pz.status_pembayaran', 'PAID')
            ->whereNull('ppi.paket_id')
            ->whereNotNull('ppi.produk_id')
            ->where(function ($query) {
                $query->whereNull('ppi.is_void')->orWhere('ppi.is_void', false);
            });
        $this->applyCabangScope($itemBaseQuery, 'pz.cabang_id');
        if (!empty($selectedCabangIds)) {
            $itemBaseQuery->whereIn('pz.cabang_id', $selectedCabangIds);
        }

        $topWorstPaket = $this->buildRankingSets(
            $paketBaseQuery,
            'pk.id',
            'pk.nama',
            'paket'
        );

        $topWorstItem = $this->buildRankingSets(
            $itemBaseQuery,
            'pr.id',
            'pr.nama',
            'item'
        );

        return view('pages.dashboard.index', [
            'cabangs' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'filters' => [
                'cabang_id' => $selectedCabangIds,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'summary' => [
                'jumlah_transaksi' => $jumlahTransaksi,
                'total_penjualan' => $totalPenjualan,
                'total_void' => $totalVoidOrder,
                'total_pembayaran_kotor' => $totalPembayaranKotor,
                'total_pembayaran_void' => $totalPembayaranVoid,
                'total_pembayaran' => $totalPembayaran,
                'rata_harian' => $rataHarian,
            ],
            'harian' => $harian,
            'chart' => [
                'labels' => array_column($harian, 'label'),
                'total_penjualan' => array_column($harian, 'total_penjualan'),
                'jumlah_transaksi' => array_column($harian, 'jumlah_transaksi'),
                'net_sales' => array_column($harian, 'total_pembayaran'),
                'weekday_index' => array_column($harian, 'weekday_index'),
                'weekday_label' => array_column($harian, 'weekday_label'),
            ],
            'topWorstPaket' => $topWorstPaket,
            'topWorstItem' => $topWorstItem,
        ]);
    }

    private function buildRankingSets($baseQuery, string $idColumn, string $nameColumn, string $aliasPrefix): array
    {
        $rankingQuery = (clone $baseQuery)
            ->selectRaw("
                {$idColumn} as {$aliasPrefix}_id,
                {$nameColumn} as nama_item,
                COALESCE(SUM(ppi.qty), 0) as total_qty,
                COALESCE(SUM(ppi.subtotal), 0) as total_penjualan
            ")
            ->groupBy($idColumn, $nameColumn);

        return [
            'top' => (clone $rankingQuery)
                ->orderByDesc('total_qty')
                ->orderByDesc('total_penjualan')
                ->limit(20)
                ->get(),
            'worst' => (clone $rankingQuery)
                ->orderBy('total_qty')
                ->orderBy('total_penjualan')
                ->limit(20)
                ->get(),
        ];
    }

    private function shortcutMenus($user): array
    {
        if (!$user) {
            return [];
        }

        $items = [
            ['permission' => 'pos.antrian.view', 'route' => 'input-antrian', 'label' => 'Input Antrian', 'icon' => 'bi bi-clipboard-plus'],
            ['permission' => 'pos.transaksi.read', 'route' => 'transaksi-penjualan', 'label' => 'Transaksi Penjualan', 'icon' => 'bi bi-cash-stack'],
            ['permission' => 'pos.riwayat.read', 'route' => 'riwayat-penjualan', 'label' => 'Riwayat Penjualan', 'icon' => 'bi bi-receipt'],
            ['permission' => 'pos.koreksi_transaksi.read', 'route' => 'koreksi-transaksi-penjualan', 'label' => 'Koreksi Transaksi', 'icon' => 'bi bi-pencil-square'],
            ['permission' => 'pos.tutup_kasir.view', 'route' => 'pos.tutup-kasir', 'label' => 'Tutup Kasir', 'icon' => 'bi bi-cash-coin'],
            ['permission' => 'tracking_order.view', 'route' => 'tracking-order', 'label' => 'Tracking Order', 'icon' => 'bi bi-geo-alt'],
            ['permission' => 'laporan.penjualan.view', 'route' => 'laporan-penjualan', 'label' => 'Laporan Penjualan', 'icon' => 'bi bi-journal-text'],
            ['permission' => 'laporan.kasir.view', 'route' => 'laporan-kasir', 'label' => 'Laporan Kasir', 'icon' => 'bi bi-person-badge'],
            ['permission' => 'laporan.performa_karyawan.view', 'route' => 'laporan-performa-karyawan', 'label' => 'Performa Karyawan', 'icon' => 'bi bi-person-check'],
            ['permission' => 'konfigurasi.karyawan.read', 'route' => 'konfigurasi.karyawan', 'label' => 'Karyawan', 'icon' => 'bi bi-people'],
            ['permission' => 'persediaan.stok.read', 'route' => 'persediaan.stok', 'label' => 'Stok Barang', 'icon' => 'bi bi-boxes'],
            ['permission' => 'pembelian.pesanan.read', 'route' => 'pembelian.pesanan', 'label' => 'Pesanan Pembelian', 'icon' => 'bi bi-cart-plus'],
            ['permission' => 'finance.metode_pembayaran.view', 'route' => 'metode-pembayaran', 'label' => 'Metode Pembayaran', 'icon' => 'bi bi-credit-card'],
        ];

        return collect($items)
            ->filter(function (array $item) use ($user) {
                return $user->hasPermission($item['permission']) && Route::has($item['route']);
            })
            ->values()
            ->all();
    }
}
