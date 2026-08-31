<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class LaporanMenuController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $sections = [
            [
                'key' => 'pos',
                'title' => 'Laporan POS',
                'description' => 'Ringkasan transaksi, kasir, dan performa penjualan.',
                'items' => [
                    ['title' => 'Laporan Penjualan', 'route' => 'laporan-penjualan', 'permission' => 'laporan.penjualan.view', 'icon' => 'bi bi-journal-text', 'desc' => 'Ringkasan transaksi penjualan.'],
                    ['title' => 'Laporan Penjualan Paket', 'route' => 'laporan-penjualan-paket', 'permission' => 'laporan.penjualan_paket.view', 'icon' => 'bi bi-box2-heart', 'desc' => 'Analisis penjualan berdasarkan paket.'],
                    ['title' => 'Laporan Barang/Jasa', 'route' => 'laporan-penjualan-barang-jasa', 'permission' => 'laporan.penjualan_barang_jasa.view', 'icon' => 'bi bi-bag-check', 'desc' => 'Penjualan per barang dan jasa.'],
                    ['title' => 'Laporan Booking', 'route' => 'laporan-booking', 'permission' => 'laporan.booking.view', 'icon' => 'bi bi-calendar-check', 'desc' => 'Monitoring data booking pelanggan.'],
                    ['title' => 'Laporan Promosi', 'route' => 'laporan-promosi', 'permission' => 'laporan.promosi.view', 'icon' => 'bi bi-megaphone', 'desc' => 'Efektivitas promo yang berjalan.'],
                    ['title' => 'Laporan Pembayaran', 'route' => 'laporan-pembayaran', 'permission' => 'laporan.pembayaran.view', 'icon' => 'bi bi-wallet2', 'desc' => 'Rekap pembayaran transaksi.'],
                    ['title' => 'Laporan Pembayaran Detail', 'route' => 'laporan-pembayaran-detail', 'permission' => 'laporan.pembayaran.view', 'icon' => 'bi bi-receipt', 'desc' => 'Detail pembayaran per KO dan customer.'],
                    ['title' => 'Laporan Customer', 'route' => 'laporan-customer', 'permission' => 'laporan.customer.read', 'icon' => 'bi bi-people', 'desc' => 'Rekap transaksi dan spending per customer.'],
                    ['title' => 'Laporan Void', 'route' => 'laporan-void', 'permission' => 'laporan.void.view', 'icon' => 'bi bi-slash-circle', 'desc' => 'Daftar void full dan partial per transaksi.'],
                    ['title' => 'Laporan Kasir', 'route' => 'laporan-kasir', 'permission' => 'laporan.kasir.view', 'icon' => 'bi bi-person-badge', 'desc' => 'Aktivitas kasir per periode.'],
                    ['title' => 'Laporan Kasir Detail', 'route' => 'laporan-kasir-detail', 'permission' => 'laporan.kasir.view', 'icon' => 'bi bi-journal-richtext', 'desc' => 'Detail order kasir per tanggal laporan.'],
                    ['title' => 'Laporan Tutup Kasir', 'route' => 'laporan-tutup-kasir', 'permission' => 'laporan.tutup_kasir.view', 'icon' => 'bi bi-cash-coin', 'desc' => 'Ringkasan buka/tutup shift kasir.'],
                    ['title' => 'Laporan KPI Omset', 'route' => 'laporan-kpi.index', 'permission' => 'laporan.kpi.view', 'icon' => 'bi bi-pie-chart', 'desc' => 'Bagi hasil omset CS, Kasir, SPV, dan Fotografer.'],
                ],
            ],
            [
                'key' => 'pembelian',
                'title' => 'Laporan Pembelian',
                'description' => 'Dokumen dan histori proses pembelian.',
                'items' => [
                    ['title' => 'Laporan Pesanan Pembelian', 'route' => 'laporan-pembelian-pesanan', 'permission' => 'laporan.pembelian_pesanan.view', 'icon' => 'bi bi-cart-plus', 'desc' => 'Rekap PO dan status pembelian.'],
                    ['title' => 'Laporan Penerimaan Barang', 'route' => 'laporan-pembelian-penerimaan', 'permission' => 'laporan.pembelian_penerimaan.view', 'icon' => 'bi bi-box-arrow-in-down', 'desc' => 'Rekap penerimaan barang dari pemasok.'],
                    ['title' => 'Laporan Faktur Pembelian', 'route' => 'laporan-pembelian-faktur', 'permission' => 'laporan.pembelian_faktur.view', 'icon' => 'bi bi-receipt-cutoff', 'desc' => 'Rekap faktur pembelian dan status tagihan.'],
                    ['title' => 'Laporan Pembayaran Pembelian', 'route' => 'laporan-pembelian-pembayaran', 'permission' => 'laporan.pembelian_pembayaran.view', 'icon' => 'bi bi-credit-card-2-front', 'desc' => 'Rekap pembayaran ke pemasok.'],
                    ['title' => 'Laporan Retur Pembelian', 'route' => 'laporan-pembelian-retur', 'permission' => 'laporan.pembelian_retur.view', 'icon' => 'bi bi-arrow-counterclockwise', 'desc' => 'Rekap retur pembelian barang.'],
                ],
            ],
            [
                'key' => 'lainnya',
                'title' => 'Laporan Lainnya',
                'description' => 'Laporan tambahan lintas divisi.',
                'items' => [
                    ['title' => 'Performa Karyawan', 'route' => 'laporan-performa-karyawan', 'permission' => 'laporan.performa_karyawan.view', 'icon' => 'bi bi-person-check', 'desc' => 'Kinerja karyawan berdasarkan transaksi.'],
                ],
            ],
        ];

        $filteredSections = [];
        foreach ($sections as $section) {
            $allowedItems = [];
            foreach ($section['items'] as $item) {
                if (!Route::has($item['route'])) {
                    continue;
                }
                if (!$user || !$user->hasPermission($item['permission'])) {
                    continue;
                }

                $item['url'] = route($item['route']);
                $item['search_blob'] = strtolower(trim(implode(' ', [
                    $section['title'],
                    $item['title'],
                    $item['desc'],
                ])));
                $allowedItems[] = $item;
            }

            if (!empty($allowedItems)) {
                $section['items'] = $allowedItems;
                $filteredSections[] = $section;
            }
        }

        return view('pages.laporan.index', [
            'reportSections' => $filteredSections,
            'totalReportMenus' => collect($filteredSections)->sum(fn($section) => count($section['items'] ?? [])),
        ]);
    }
}
