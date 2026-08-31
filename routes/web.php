<?php

use App\Http\Controllers\AddonController;
use App\Http\Controllers\ActiveCabangController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DaftarQcController;
use App\Http\Controllers\DivisiController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InputAntrianController;
use App\Http\Controllers\InputOrderController;
use App\Http\Controllers\JabatanController;
use App\Http\Controllers\KonfirmasiController;
use App\Http\Controllers\KoreksiTransaksiPenjualanController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\LaporanKasirController;
use App\Http\Controllers\LaporanKpiController;
use App\Http\Controllers\LaporanMenuController;
use App\Http\Controllers\LaporanPembelianController;
use App\Http\Controllers\LaporanPembayaranController;
use App\Http\Controllers\LaporanPerformaKaryawanController;
use App\Http\Controllers\LaporanPromosiController;
use App\Http\Controllers\LaporanVoidController;
use App\Http\Controllers\LaporanCustomerController;
use App\Http\Controllers\MetodePembayaranController;
use App\Http\Controllers\KategoriAddonController;
use App\Http\Controllers\KategoriPaketController;
use App\Http\Controllers\PaketMasterController;
use App\Http\Controllers\PenjualanPaymentMethodController;
use App\Http\Controllers\PenjualanVoidController;
use App\Http\Controllers\PemasokController;
use App\Http\Controllers\PekerjaanDgController;
use App\Http\Controllers\PerusahaanController;
use App\Http\Controllers\CabangController;
use App\Http\Controllers\Pembelian\FakturPembelianController;
use App\Http\Controllers\Pembelian\PembayaranPembelianController;
use App\Http\Controllers\Pembelian\PenerimaanBarangController;
use App\Http\Controllers\Pembelian\PesananPembelianController;
use App\Http\Controllers\Pembelian\ReturPembelianController;
use App\Http\Controllers\PermintaanBarangController;
use App\Http\Controllers\Persediaan\BarangJasaController;
use App\Http\Controllers\Persediaan\GolonganController;
use App\Http\Controllers\Persediaan\SatuanController;
use App\Http\Controllers\Persediaan\StokController;
use App\Http\Controllers\Persediaan\StokPenyesuaianController;
use App\Http\Controllers\PromosiController;
use App\Http\Controllers\RoleUserController;
use App\Http\Controllers\SalesModeController;
use App\Http\Controllers\ShiftKasirController;
use App\Http\Controllers\SyncControlController;
use App\Http\Controllers\StudioController;
use App\Http\Controllers\StudioAntrianController;
use App\Http\Controllers\TemplateHargaController;
use App\Http\Controllers\TrackingReferenceController;
use App\Http\Controllers\TrackingOrderController;
use App\Http\Controllers\TransaksiPenjualanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }

    $user = auth()->user();
    if ($user?->hasPermission('dashboard.view')) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('home');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::post('/active-cabang', [ActiveCabangController::class, 'update'])->name('active-cabang.update');

    Route::middleware(['permission.route', 'local.master.restrict'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/input-antrian', [InputAntrianController::class, 'index'])->name('input-antrian');
        Route::get('/input-antrian/ko-cari', [InputAntrianController::class, 'cariKo'])->name('input-antrian.ko-search');
        Route::post('/input-antrian/simpan', [InputAntrianController::class, 'simpan'])->name('input-antrian.enqueue');
        Route::post('/input-antrian/urutkan', [InputAntrianController::class, 'urutkan'])->name('input-antrian.reorder');
        Route::delete('/input-antrian/{antrianStudio}', [InputAntrianController::class, 'hapus'])->name('input-antrian.delete');

        Route::get('/input-order', [InputOrderController::class, 'index'])->name('input-order');
        Route::get('/input-order/produk-cari', [InputOrderController::class, 'cariProduk'])->name('input-order.produk-cari');
        Route::get('/input-order/cek-ko', [InputOrderController::class, 'cekKo'])->name('input-order.cek-ko');
        Route::get('/input-order/promosi-tersedia', [InputOrderController::class, 'promosiTersedia'])->name('input-order.promosi-tersedia');
        Route::post('/input-order/simpan', [InputOrderController::class, 'simpan'])->name('input-order.simpan');

        Route::get('/transaksi-penjualan', [TransaksiPenjualanController::class, 'index'])->name('transaksi-penjualan');
        Route::get('/transaksi-penjualan/produk-cari', [TransaksiPenjualanController::class, 'cariProduk'])->name('transaksi-penjualan.produk-cari');
        Route::get('/transaksi-penjualan/cek-ko', [TransaksiPenjualanController::class, 'cekKo'])->name('transaksi-penjualan.cek-ko');
        Route::get('/transaksi-penjualan/promosi-tersedia', [TransaksiPenjualanController::class, 'promosiTersedia'])->name('transaksi-penjualan.promosi-tersedia');
        Route::post('/transaksi-penjualan/simpan', [TransaksiPenjualanController::class, 'simpan'])->name('transaksi-penjualan.simpan');
        Route::post('/pos/authorize-price-override', [TransaksiPenjualanController::class, 'authorizePriceOverride'])->name('pos.authorize-price-override');
        Route::get('/transaksi-penjualan/struk/{pesananPenjualan}', [TransaksiPenjualanController::class, 'struk'])->name('transaksi-penjualan.struk');
        Route::get('/riwayat-penjualan', [TransaksiPenjualanController::class, 'riwayat'])->name('riwayat-penjualan');
        Route::get('/riwayat-penjualan/{pesananPenjualan}', [TransaksiPenjualanController::class, 'riwayatDetail'])->name('riwayat-penjualan.detail');
        Route::get('/riwayat-penjualan/{pesananPenjualan}/reprint-struk', [TransaksiPenjualanController::class, 'reprintStruk'])->name('riwayat-penjualan.reprint');
        Route::post('/riwayat-penjualan/{pesananPenjualan}/void', [PenjualanVoidController::class, 'voidRiwayat'])->name('riwayat-penjualan.void');
        Route::post('/riwayat-penjualan/{pesananPenjualan}/pembayaran/{pembayaranPenjualan}/metode', [PenjualanPaymentMethodController::class, 'update'])->name('riwayat-penjualan.payment-method.update');
        Route::get('/koreksi-transaksi-penjualan', [KoreksiTransaksiPenjualanController::class, 'index'])->name('koreksi-transaksi-penjualan');
        Route::put('/koreksi-transaksi-penjualan/{pesananPenjualan}', [KoreksiTransaksiPenjualanController::class, 'update'])->name('koreksi-transaksi-penjualan.update');
        Route::get('/laporan-penjualan', [TransaksiPenjualanController::class, 'laporan'])->name('laporan-penjualan');
        Route::get('/laporan-penjualan-paket', [TransaksiPenjualanController::class, 'laporanPaket'])->name('laporan-penjualan-paket');
        Route::get('/laporan-penjualan-barang-jasa', [TransaksiPenjualanController::class, 'laporanBarangJasa'])->name('laporan-penjualan-barang-jasa');
        Route::get('/pos/generate-otp-void', [PenjualanVoidController::class, 'index'])->name('pos.void-otp');
        Route::get('/pos/generate-otp-void/cari-order', [PenjualanVoidController::class, 'cariOrder'])->name('pos.void-otp.cari-order');
        Route::post('/pos/generate-otp-void/generate', [PenjualanVoidController::class, 'generateOtp'])->name('pos.void-otp.generate');
        Route::get('/laporan-booking', [TransaksiPenjualanController::class, 'laporanBooking'])->name('laporan-booking');
        Route::get('/laporan-promosi', [LaporanPromosiController::class, 'index'])->name('laporan-promosi');
        Route::get('/laporan-customer', [LaporanCustomerController::class, 'index'])->name('laporan-customer');
        Route::get('/laporan-pembayaran', [LaporanPembayaranController::class, 'index'])->name('laporan-pembayaran');
        Route::get('/laporan-pembayaran-detail', [LaporanPembayaranController::class, 'detail'])->name('laporan-pembayaran-detail');
        Route::get('/laporan-void', [LaporanVoidController::class, 'index'])->name('laporan-void');
        Route::get('/laporan-kasir', [LaporanKasirController::class, 'index'])->name('laporan-kasir');
        Route::get('/laporan-kasir-detail', [LaporanKasirController::class, 'detail'])->name('laporan-kasir-detail');
        Route::get('/laporan-performa-karyawan', [LaporanPerformaKaryawanController::class, 'index'])->name('laporan-performa-karyawan');
        Route::get('/laporan-kpi', [LaporanKpiController::class, 'index'])->name('laporan-kpi.index');
        Route::get('/laporan-kpi/export', [LaporanKpiController::class, 'export'])->name('laporan-kpi.export');
        Route::get('/laporan-kpi/konfigurasi', [LaporanKpiController::class, 'konfigurasi'])->name('laporan-kpi.konfigurasi');
        Route::post('/laporan-kpi/konfigurasi/simpan', [LaporanKpiController::class, 'simpanKonfigurasi'])->name('laporan-kpi.konfigurasi.simpan');
        Route::get('/laporan-tutup-kasir', [ShiftKasirController::class, 'laporan'])->name('laporan-tutup-kasir');
        Route::post('/laporan-tutup-kasir/{shiftKasir}/resend-email', [ShiftKasirController::class, 'kirimUlangEmail'])->name('laporan-tutup-kasir.resend-email');
        Route::get('/laporan-pembelian-pesanan', [LaporanPembelianController::class, 'pesanan'])->name('laporan-pembelian-pesanan');
        Route::get('/laporan-pembelian-penerimaan', [LaporanPembelianController::class, 'penerimaan'])->name('laporan-pembelian-penerimaan');
        Route::get('/laporan-pembelian-faktur', [LaporanPembelianController::class, 'faktur'])->name('laporan-pembelian-faktur');
        Route::get('/laporan-pembelian-pembayaran', [LaporanPembelianController::class, 'pembayaran'])->name('laporan-pembelian-pembayaran');
        Route::get('/laporan-pembelian-retur', [LaporanPembelianController::class, 'retur'])->name('laporan-pembelian-retur');
        Route::get('/laporan', [LaporanMenuController::class, 'index'])->name('laporan.menu');
        Route::get('/pos/tutup-kasir', [ShiftKasirController::class, 'index'])->name('pos.tutup-kasir');
        Route::post('/pos/tutup-kasir/open', [ShiftKasirController::class, 'buka'])->name('pos.tutup-kasir.open');
        Route::post('/pos/tutup-kasir/close', [ShiftKasirController::class, 'tutup'])->name('pos.tutup-kasir.close');

        Route::prefix('paket')->group(function () {
            Route::get('/list', [PaketMasterController::class, 'index'])->name('paket.list');
            Route::get('/produk-cari', [PaketMasterController::class, 'cariProdukSelect'])->name('paket.produk-cari');
            Route::post('/list', [PaketMasterController::class, 'store'])->name('paket.store');
            Route::post('/list/batch-status', [PaketMasterController::class, 'batchUpdateStatus'])->name('paket.batch-status');
            Route::put('/list/{paket}', [PaketMasterController::class, 'update'])->name('paket.update');
            Route::delete('/list/{paket}', [PaketMasterController::class, 'destroy'])->name('paket.destroy');

            Route::get('/kategori', [KategoriPaketController::class, 'index'])->name('paket.kategori');
            Route::post('/kategori', [KategoriPaketController::class, 'store'])->name('paket.kategori.store');
            Route::put('/kategori/{kategoriPaket}', [KategoriPaketController::class, 'update'])->name('paket.kategori.update');
            Route::delete('/kategori/{kategoriPaket}', [KategoriPaketController::class, 'destroy'])->name('paket.kategori.destroy');

            Route::get('/addon', [AddonController::class, 'index'])->name('paket.addon');
            Route::post('/addon', [AddonController::class, 'store'])->name('paket.addon.store');
            Route::put('/addon/{addon}', [AddonController::class, 'update'])->name('paket.addon.update');
            Route::delete('/addon/{addon}', [AddonController::class, 'destroy'])->name('paket.addon.destroy');

            Route::get('/kategori-addon', [KategoriAddonController::class, 'index'])->name('paket.kategori-addon');
            Route::post('/kategori-addon', [KategoriAddonController::class, 'store'])->name('paket.kategori-addon.store');
            Route::put('/kategori-addon/{kategoriAddon}', [KategoriAddonController::class, 'update'])->name('paket.kategori-addon.update');
            Route::delete('/kategori-addon/{kategoriAddon}', [KategoriAddonController::class, 'destroy'])->name('paket.kategori-addon.destroy');

            Route::get('/template-harga', [TemplateHargaController::class, 'index'])->name('template.harga');
            Route::post('/template-harga', [TemplateHargaController::class, 'store'])->name('template.harga.store');
            Route::put('/template-harga/{templateHarga}', [TemplateHargaController::class, 'update'])->name('template.harga.update');
            Route::delete('/template-harga/{templateHarga}', [TemplateHargaController::class, 'destroy'])->name('template.harga.destroy');
            Route::get('/template-harga/{templateHarga}/detail', [TemplateHargaController::class, 'detail'])->name('template.harga.detail');
            Route::post('/template-harga/{templateHarga}/detail', [TemplateHargaController::class, 'simpanDetail'])->name('template.harga.detail.simpan');
            Route::get('/template-harga/{templateHarga}/copy-source', [TemplateHargaController::class, 'copySource'])->name('template.harga.copy-source');
        });

        Route::get('/sales-mode', [SalesModeController::class, 'index'])->name('sales-mode');
        Route::post('/sales-mode', [SalesModeController::class, 'store'])->name('sales-mode.store');
        Route::put('/sales-mode/{salesMode}', [SalesModeController::class, 'update'])->name('sales-mode.update');
        Route::delete('/sales-mode/{salesMode}', [SalesModeController::class, 'destroy'])->name('sales-mode.destroy');

        Route::get('/promosi', [PromosiController::class, 'index'])->name('promosi');
        Route::post('/promosi/voucher', [PromosiController::class, 'storeVoucher'])->name('promosi.voucher.store');
        Route::put('/promosi/voucher/{voucherPromosi}', [PromosiController::class, 'updateVoucher'])->name('promosi.voucher.update');
        Route::delete('/promosi/voucher/{voucherPromosi}', [PromosiController::class, 'destroyVoucher'])->name('promosi.voucher.destroy');
        Route::post('/promosi/diskon-otomatis', [PromosiController::class, 'storeDiskonOtomatis'])->name('promosi.diskon.store');
        Route::put('/promosi/diskon-otomatis/{diskonOtomati}', [PromosiController::class, 'updateDiskonOtomatis'])->name('promosi.diskon.update');
        Route::delete('/promosi/diskon-otomatis/{diskonOtomati}', [PromosiController::class, 'destroyDiskonOtomatis'])->name('promosi.diskon.destroy');

        Route::get('/tracking-order', [TrackingOrderController::class, 'index'])->name('tracking-order');
        Route::put('/tracking-order/ko-check', [TrackingOrderController::class, 'updateKoCheck'])->name('tracking-order.ko-check.update');
        Route::put('/tracking-order/item-check', [TrackingOrderController::class, 'updateItemCheck'])->name('tracking-order.item-check.update');

        Route::prefix('konfigurasi')->group(function () {
            Route::get('/perusahaan', function () {
                return view('pages.master.perusahaan.index');
            })->name('perusahaan.index');

            Route::prefix('perusahaan/data')->group(function () {
                Route::get('/generate-kode', [PerusahaanController::class, 'generateKode'])->name('konfigurasi.perusahaan.data.generate-kode');
                Route::get('/', [PerusahaanController::class, 'index'])->name('konfigurasi.perusahaan.data.index');
                Route::post('/', [PerusahaanController::class, 'store'])->name('konfigurasi.perusahaan.data.store');
                Route::get('/{id}', [PerusahaanController::class, 'show'])->name('konfigurasi.perusahaan.data.show');
                Route::put('/{id}', [PerusahaanController::class, 'update'])->name('konfigurasi.perusahaan.data.update');
                Route::delete('/{id}', [PerusahaanController::class, 'destroy'])->name('konfigurasi.perusahaan.data.destroy');
            });

            Route::get('/cabang', function () {
                return view('pages.master.cabang.index');
            })->name('cabang.index');

            Route::prefix('cabang/data')->group(function () {
                Route::get('/', [CabangController::class, 'index'])->name('konfigurasi.cabang.data.index');
                Route::get('/perusahaan/list', [CabangController::class, 'getPerusahaan'])->name('konfigurasi.cabang.data.perusahaan-list');
                Route::get('/sales-mode-template/list', [CabangController::class, 'getSalesModeTemplate'])->name('konfigurasi.cabang.data.sales-mode-template-list');
                Route::get('/generate-kode', [CabangController::class, 'generateKode'])->name('konfigurasi.cabang.data.generate-kode');
                Route::post('/', [CabangController::class, 'store'])->name('konfigurasi.cabang.data.store');
                Route::get('/{id}', [CabangController::class, 'show'])->name('konfigurasi.cabang.data.show');
                Route::put('/{id}', [CabangController::class, 'update'])->name('konfigurasi.cabang.data.update');
                Route::delete('/{id}', [CabangController::class, 'destroy'])->name('konfigurasi.cabang.data.destroy');
            });

            Route::get('/divisi', [DivisiController::class, 'index'])->name('konfigurasi.divisi');
            Route::post('/divisi', [DivisiController::class, 'store'])->name('konfigurasi.divisi.store');
            Route::put('/divisi/{divisi}', [DivisiController::class, 'update'])->name('konfigurasi.divisi.update');
            Route::delete('/divisi/{divisi}', [DivisiController::class, 'destroy'])->name('konfigurasi.divisi.destroy');

            Route::get('/jabatan', [JabatanController::class, 'index'])->name('konfigurasi.jabatan');
            Route::post('/jabatan', [JabatanController::class, 'store'])->name('konfigurasi.jabatan.store');
            Route::put('/jabatan/{jabatan}', [JabatanController::class, 'update'])->name('konfigurasi.jabatan.update');
            Route::delete('/jabatan/{jabatan}', [JabatanController::class, 'destroy'])->name('konfigurasi.jabatan.destroy');
            Route::get('/jabatan/{jabatan}/tracking-ko', [JabatanController::class, 'trackingKo'])->name('konfigurasi.jabatan.tracking-ko');
            Route::put('/jabatan/{jabatan}/tracking-ko', [JabatanController::class, 'updateTrackingKo'])->name('konfigurasi.jabatan.tracking-ko.update');

            Route::get('/tracking', [TrackingReferenceController::class, 'index'])->name('konfigurasi.tracking');
            Route::post('/tracking', [TrackingReferenceController::class, 'store'])->name('konfigurasi.tracking.store');
            Route::put('/tracking/{tracking}', [TrackingReferenceController::class, 'update'])->name('konfigurasi.tracking.update');
            Route::delete('/tracking/{tracking}', [TrackingReferenceController::class, 'destroy'])->name('konfigurasi.tracking.destroy');

            Route::get('/studio', function () {
                return view('pages.master.studio.index');
            })->name('konfigurasi.studio');

            Route::prefix('studio/data')->group(function () {
                Route::get('/', [StudioController::class, 'index'])->name('konfigurasi.studio.data.index');
                Route::get('/cabang/list', [StudioController::class, 'getCabang'])->name('konfigurasi.studio.data.cabang-list');
                Route::get('/tema-studio/list', [StudioController::class, 'getTemaStudio'])->name('konfigurasi.studio.data.tema-studio-list');
                Route::post('/', [StudioController::class, 'store'])->name('konfigurasi.studio.data.store');
                Route::get('/{id}', [StudioController::class, 'show'])->name('konfigurasi.studio.data.show');
                Route::put('/{id}', [StudioController::class, 'update'])->name('konfigurasi.studio.data.update');
                Route::delete('/{id}', [StudioController::class, 'destroy'])->name('konfigurasi.studio.data.destroy');
            });

            Route::get('/karyawan', [KaryawanController::class, 'index'])->name('konfigurasi.karyawan');
            Route::get('/karyawan/create', [KaryawanController::class, 'create'])->name('konfigurasi.karyawan.create');
            Route::post('/karyawan', [KaryawanController::class, 'store'])->name('konfigurasi.karyawan.store');
            Route::get('/karyawan/{karyawan}/edit', [KaryawanController::class, 'edit'])->name('konfigurasi.karyawan.edit');
            Route::put('/karyawan/{karyawan}', [KaryawanController::class, 'update'])->name('konfigurasi.karyawan.update');
            Route::delete('/karyawan/{karyawan}', [KaryawanController::class, 'destroy'])->name('konfigurasi.karyawan.destroy');

            Route::get('/role-karyawan', [RoleUserController::class, 'index'])->name('konfigurasi.role-karyawan');
            Route::get('/role-karyawan/create', [RoleUserController::class, 'create'])->name('konfigurasi.role-karyawan.create');
            Route::post('/role-karyawan', [RoleUserController::class, 'store'])->name('konfigurasi.role-karyawan.store');
            Route::get('/role-karyawan/{role}/edit', [RoleUserController::class, 'edit'])->name('konfigurasi.role-karyawan.edit');
            Route::put('/role-karyawan/{role}', [RoleUserController::class, 'update'])->name('konfigurasi.role-karyawan.update');
            Route::delete('/role-karyawan/{role}', [RoleUserController::class, 'destroy'])->name('konfigurasi.role-karyawan.destroy');
        });

        Route::prefix('persediaan')->group(function () {
            Route::get('/barang-jasa', [BarangJasaController::class, 'index'])->name('persediaan.barang-jasa');
            Route::post('/barang-jasa', [BarangJasaController::class, 'store'])->name('persediaan.barang-jasa.store');
            Route::post('/barang-jasa/batch-update', [BarangJasaController::class, 'batchUpdate'])->name('persediaan.barang-jasa.batch-update');
            Route::put('/barang-jasa/{barangJasa}', [BarangJasaController::class, 'update'])->name('persediaan.barang-jasa.update');
            Route::delete('/barang-jasa/{barangJasa}', [BarangJasaController::class, 'destroy'])->name('persediaan.barang-jasa.destroy');

            Route::get('/satuan', [SatuanController::class, 'index'])->name('persediaan.satuan');
            Route::post('/satuan', [SatuanController::class, 'store'])->name('persediaan.satuan.store');
            Route::put('/satuan/{satuan}', [SatuanController::class, 'update'])->name('persediaan.satuan.update');
            Route::delete('/satuan/{satuan}', [SatuanController::class, 'destroy'])->name('persediaan.satuan.destroy');

            Route::get('/golongan', [GolonganController::class, 'index'])->name('persediaan.golongan');
            Route::post('/golongan', [GolonganController::class, 'store'])->name('persediaan.golongan.store');
            Route::put('/golongan/{golongan}', [GolonganController::class, 'update'])->name('persediaan.golongan.update');
            Route::delete('/golongan/{golongan}', [GolonganController::class, 'destroy'])->name('persediaan.golongan.destroy');

            Route::get('/stok', [StokController::class, 'index'])->name('persediaan.stok');
            Route::get('/stok/penyesuaian', [StokPenyesuaianController::class, 'index'])->name('persediaan.stok.penyesuaian');
            Route::get('/stok/penyesuaian/create', [StokPenyesuaianController::class, 'create'])->name('persediaan.stok.penyesuaian.create');
            Route::get('/stok/penyesuaian/produk-cari', [StokPenyesuaianController::class, 'searchProduk'])->name('persediaan.stok.penyesuaian.produk-cari');
            Route::get('/stok/penyesuaian/{penyesuaian}', [StokPenyesuaianController::class, 'show'])->name('persediaan.stok.penyesuaian.show');
            Route::get('/stok/penyesuaian/{penyesuaian}/edit', [StokPenyesuaianController::class, 'edit'])->name('persediaan.stok.penyesuaian.edit');
            Route::post('/stok/penyesuaian', [StokPenyesuaianController::class, 'store'])->name('persediaan.stok.penyesuaian.store');
            Route::put('/stok/penyesuaian/{penyesuaian}', [StokPenyesuaianController::class, 'update'])->name('persediaan.stok.penyesuaian.update');
            Route::delete('/stok/penyesuaian/{penyesuaian}', [StokPenyesuaianController::class, 'destroy'])->name('persediaan.stok.penyesuaian.destroy');
        });

        Route::prefix('permintaan-barang')->group(function () {
            Route::get('/', [PermintaanBarangController::class, 'index'])->name('permintaan-barang.index');
            Route::get('/create', [PermintaanBarangController::class, 'create'])->name('permintaan-barang.create');
            Route::post('/', [PermintaanBarangController::class, 'store'])->name('permintaan-barang.store');
            Route::get('/{permintaanBarang}', [PermintaanBarangController::class, 'show'])->name('permintaan-barang.show');
            Route::get('/{permintaanBarang}/pdf', [PermintaanBarangController::class, 'pdf'])->name('permintaan-barang.pdf');
            Route::get('/{permintaanBarang}/edit', [PermintaanBarangController::class, 'edit'])->name('permintaan-barang.edit');
            Route::put('/{permintaanBarang}', [PermintaanBarangController::class, 'update'])->name('permintaan-barang.update');
            Route::delete('/{permintaanBarang}', [PermintaanBarangController::class, 'destroy'])->name('permintaan-barang.destroy');
        });

        Route::prefix('pemasok')->group(function () {
            Route::get('/', [PemasokController::class, 'index'])->name('pemasok.index');
            Route::get('/create', [PemasokController::class, 'create'])->name('pemasok.create');
            Route::post('/', [PemasokController::class, 'store'])->name('pemasok.store');
            Route::get('/{id}/edit', [PemasokController::class, 'edit'])->name('pemasok.edit');
            Route::put('/{id}', [PemasokController::class, 'update'])->name('pemasok.update');
            Route::delete('/{id}', [PemasokController::class, 'destroy'])->name('pemasok.destroy');
        });

        Route::prefix('pembelian')->group(function () {
            Route::get('/pesanan', [PesananPembelianController::class, 'index'])->name('pembelian.pesanan');
            Route::get('/pesanan/create', [PesananPembelianController::class, 'create'])->name('pembelian.pesanan.create');
            Route::get('/pesanan/permintaan-options', [PesananPembelianController::class, 'permintaanOptions'])->name('pembelian.pesanan.permintaan-options');
            Route::get('/pesanan/permintaan/{permintaanBarang}', [PesananPembelianController::class, 'permintaanShow'])->name('pembelian.pesanan.permintaan-show');
            Route::post('/pesanan', [PesananPembelianController::class, 'store'])->name('pembelian.pesanan.store');
            Route::get('/pesanan/{pesananPembelian}', [PesananPembelianController::class, 'show'])->name('pembelian.pesanan.show');
            Route::get('/pesanan/{pesananPembelian}/pdf', [PesananPembelianController::class, 'pdf'])->name('pembelian.pesanan.pdf');
            Route::get('/pesanan/{pesananPembelian}/edit', [PesananPembelianController::class, 'edit'])->name('pembelian.pesanan.edit');
            Route::put('/pesanan/{pesananPembelian}', [PesananPembelianController::class, 'update'])->name('pembelian.pesanan.update');
            Route::delete('/pesanan/{pesananPembelian}', [PesananPembelianController::class, 'destroy'])->name('pembelian.pesanan.destroy');
            Route::post('/pesanan/{pesananPembelian}/close', [PesananPembelianController::class, 'close'])->name('pembelian.pesanan.close');

            Route::get('/penerimaan', [PenerimaanBarangController::class, 'index'])->name('pembelian.penerimaan');
            Route::get('/penerimaan/create', [PenerimaanBarangController::class, 'create'])->name('pembelian.penerimaan.create');
            Route::post('/penerimaan', [PenerimaanBarangController::class, 'store'])->name('pembelian.penerimaan.store');
            Route::get('/penerimaan/{penerimaanBarang}', [PenerimaanBarangController::class, 'show'])->name('pembelian.penerimaan.show');
            Route::get('/penerimaan/{penerimaanBarang}/pdf', [PenerimaanBarangController::class, 'pdf'])->name('pembelian.penerimaan.pdf');

            Route::get('/faktur', [FakturPembelianController::class, 'index'])->name('pembelian.faktur');
            Route::get('/faktur/create', [FakturPembelianController::class, 'create'])->name('pembelian.faktur.create');
            Route::post('/faktur', [FakturPembelianController::class, 'store'])->name('pembelian.faktur.store');
            Route::get('/faktur/{fakturPembelian}', [FakturPembelianController::class, 'show'])->name('pembelian.faktur.show');
            Route::get('/faktur/{fakturPembelian}/pdf', [FakturPembelianController::class, 'pdf'])->name('pembelian.faktur.pdf');

            Route::get('/pembayaran', [PembayaranPembelianController::class, 'index'])->name('pembelian.pembayaran');
            Route::post('/pembayaran', [PembayaranPembelianController::class, 'store'])->name('pembelian.pembayaran.store');
            Route::get('/pembayaran/{pembayaranPembelian}', [PembayaranPembelianController::class, 'show'])->name('pembelian.pembayaran.show');
            Route::get('/pembayaran/{pembayaranPembelian}/pdf', [PembayaranPembelianController::class, 'pdf'])->name('pembelian.pembayaran.pdf');

            Route::get('/retur', [ReturPembelianController::class, 'index'])->name('pembelian.retur');
            Route::get('/retur/create', [ReturPembelianController::class, 'create'])->name('pembelian.retur.create');
            Route::post('/retur', [ReturPembelianController::class, 'store'])->name('pembelian.retur.store');
            Route::get('/retur/{returPembelian}', [ReturPembelianController::class, 'show'])->name('pembelian.retur.show');
            Route::get('/retur/{returPembelian}/pdf', [ReturPembelianController::class, 'pdf'])->name('pembelian.retur.pdf');

            Route::get('/kategori-pemasok', function () {
                return view('pembelian.kategori-pemasok');
            })->name('pembelian.kategori-pemasok');
        });

        Route::get('/coa', function () {
            return view('coa');
        })->name('coa');

        Route::get('/tax', function () {
            return view('tax');
        })->name('tax');

        Route::get('/metode-pembayaran', [MetodePembayaranController::class, 'index'])->name('metode-pembayaran');
        Route::post('/metode-pembayaran', [MetodePembayaranController::class, 'store'])->name('metode-pembayaran.store');
        Route::put('/metode-pembayaran/{metodePembayaran}', [MetodePembayaranController::class, 'update'])->name('metode-pembayaran.update');
        Route::delete('/metode-pembayaran/{metodePembayaran}', [MetodePembayaranController::class, 'destroy'])->name('metode-pembayaran.destroy');

        Route::get('/pekerjaan-dg', [PekerjaanDgController::class, 'index'])->name('pekerjaan-dg');
        Route::post('/pekerjaan-dg/mark-done', [PekerjaanDgController::class, 'markDone'])->name('pekerjaan-dg.mark-done');

        Route::get('/daftar-qc', [DaftarQcController::class, 'index'])->name('daftar-qc');

        Route::get('/antrian-studio', [StudioAntrianController::class, 'index'])->name('antrian-studio');
        Route::get('/antrian-studio/board', [StudioAntrianController::class, 'board'])->name('antrian-studio.board');
        Route::get('/antrian-studio/stream', [StudioAntrianController::class, 'stream'])->name('antrian-studio.stream');
        Route::get('/antrian-studio/display/customer', [StudioAntrianController::class, 'customerDisplay'])->name('antrian-studio.display.customer');
        Route::get('/antrian-studio/display/customer/board', [StudioAntrianController::class, 'customerBoard'])->name('antrian-studio.display.customer.board');
        Route::get('/antrian-studio/display/customer/stream', [StudioAntrianController::class, 'customerStream'])->name('antrian-studio.display.customer.stream');
        Route::get('/antrian-studio/audio-announcer', [StudioAntrianController::class, 'audioAnnouncer'])->name('antrian-studio.audio-announcer');
        Route::get('/antrian-studio/audio-announcer/board', [StudioAntrianController::class, 'audioBoard'])->name('antrian-studio.audio-announcer.board');
        Route::get('/antrian-studio/audio-announcer/stream', [StudioAntrianController::class, 'audioStream'])->name('antrian-studio.audio-announcer.stream');
        Route::post('/antrian-studio/{antrianStudio}/call', [StudioAntrianController::class, 'panggil'])->name('antrian-studio.call');
        Route::post('/antrian-studio/{antrianStudio}/start', [StudioAntrianController::class, 'start'])->name('antrian-studio.start');
        Route::post('/antrian-studio/{antrianStudio}/end', [StudioAntrianController::class, 'end'])->name('antrian-studio.end');
        Route::post('/antrian-studio/tugas/{antrianStudioTugas}/toggle', [StudioAntrianController::class, 'toggleTugas'])->name('antrian-studio.tugas.toggle');
        Route::get('/antrian-studio/{antrianStudio}/tracking-detail', [StudioAntrianController::class, 'trackingDetail'])->name('antrian-studio.tracking-detail');
        Route::post('/antrian-studio/{antrianStudio}/tracking-item-check', [StudioAntrianController::class, 'updateTrackingItemCheck'])->name('antrian-studio.tracking-item-check');

        Route::get('/konfirmasi', [KonfirmasiController::class, 'index'])->name('konfirmasi');
        Route::put('/konfirmasi/ko-check', [KonfirmasiController::class, 'updateKoStep'])->name('konfirmasi.ko-check.update');

        Route::get('/documentation', function () {
            return view('documentation');
        })->name('documentation');

        Route::get('/support', function () {
            return view('support');
        })->name('support');

        Route::get('/sync-control', [SyncControlController::class, 'index'])->name('sync-control');
        Route::post('/sync-control/manual-push', [SyncControlController::class, 'manualPush'])->name('sync-control.manual-push');
        Route::post('/sync-control/manual-bootstrap', [SyncControlController::class, 'manualBootstrap'])->name('sync-control.manual-bootstrap');
    });
});
