<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user && $user->hasPermission('dashboard.view')) {
            return redirect()->route('dashboard');
        }

        return view('pages.home.index', [
            'shortcutMenus' => $this->shortcutMenus($user),
        ]);
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
            ['permission' => 'studio.antrian.view', 'route' => 'antrian-studio', 'label' => 'Antrian Studio', 'icon' => 'bi bi-people'],
            ['permission' => 'studio.display_customer.view', 'route' => 'antrian-studio.display.customer', 'label' => 'Display Customer', 'icon' => 'bi bi-display'],
            ['permission' => 'produksi.pekerjaan_dg.view', 'route' => 'pekerjaan-dg', 'label' => 'Daftar Pekerjaan DG', 'icon' => 'bi bi-list-task'],
            ['permission' => 'cs.konfirmasi.view', 'route' => 'konfirmasi', 'label' => 'Daftar Konfirmasi', 'icon' => 'bi bi-chat-square-text'],
        ];

        return collect($items)
            ->filter(fn (array $item) => $user->hasPermission($item['permission']) && Route::has($item['route']))
            ->values()
            ->all();
    }
}
