@extends('layouts.app')

@section('title', 'Documentation')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Customer Service</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active">Documentation</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Panduan Penggunaan Modul Papyrus POS</h5>
        <p class="text-muted mb-0">
            Dokumen ini menjelaskan alur operasional setiap modul utama di aplikasi.
            Ikuti urutan implementasi agar data konsisten dari setup master, transaksi, sampai laporan.
        </p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h6 class="mb-3">1. Urutan Setup Awal</h6>
        <ol class="mb-0">
            <li>Konfigurasi Perusahaan dan Cabang.</li>
            <li>Set Divisi, Jabatan, Tracking, Studio, Karyawan, dan Role Karyawan.</li>
            <li>Buat master Paket, Kategori Paket, Add On, Kategori Add On, Template Harga, Sales Mode, dan Promosi.</li>
            <li>Lengkapi master Persediaan: Barang/Jasa, Satuan, Golongan, lalu cek Stok.</li>
            <li>Isi master Pemasok sebelum proses pembelian.</li>
            <li>Set Metode Pembayaran (pastikan kode CASH aktif untuk tutup kasir).</li>
        </ol>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h6 class="mb-3">2. Modul POS</h6>
        <p class="mb-2"><strong>Input Antrian</strong></p>
        <ul>
            <li>Buka menu Input Antrian, cari KO, lalu simpan antrian.</li>
            <li>Gunakan fitur urutkan/hapus untuk menyesuaikan antrean lapangan.</li>
        </ul>

        <p class="mb-2"><strong>Transaksi Penjualan</strong></p>
        <ul>
            <li>Pilih cabang aktif dan sales mode.</li>
            <li>Input customer, item produk/paket, diskon, dan pembayaran.</li>
            <li>Simpan transaksi lalu cetak struk jika diperlukan.</li>
            <li>Jika ada shift lama OPEN (hari sebelumnya), tutup kasir dulu sebelum lanjut transaksi.</li>
        </ul>

        <p class="mb-2"><strong>Riwayat Penjualan</strong></p>
        <ul>
            <li>Gunakan filter tanggal, customer, KO, dan status pembayaran.</li>
            <li>Buka detail untuk review item, pembayaran, dan reprint struk.</li>
            <li>Lakukan void melalui fitur yang disediakan jika ada koreksi transaksi.</li>
        </ul>

        <p class="mb-2"><strong>Generate OTP Void/Remove</strong></p>
        <ul>
            <li>Cari order target, pilih tipe aksi (VOID/REMOVE), lalu generate OTP.</li>
            <li>OTP remove dipakai untuk hapus item/paket pada transaksi belum lunas.</li>
            <li>Simpan OTP hanya untuk kebutuhan validasi internal.</li>
        </ul>

        <p class="mb-2"><strong>Tutup Kasir</strong></p>
        <ul>
            <li>Buka shift dengan modal awal ketika mulai kerja.</li>
            <li>Saat selesai, input pecahan uang fisik dan tutup shift.</li>
            <li>Perhitungan kas expected dihitung per shift/user, bukan gabungan antar user.</li>
        </ul>

        <p class="mb-0"><strong>Tracking Order</strong>: gunakan untuk checklist progres KO dan item sesuai status kerja.</p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h6 class="mb-3">3. Modul Master</h6>
        <p class="mb-2"><strong>Konfigurasi</strong></p>
        <ul>
            <li><strong>Perusahaan & Cabang</strong>: buat entitas organisasi dan cabang operasional.</li>
            <li><strong>Divisi & Jabatan</strong>: susun struktur kerja dan mapping tracking KO.</li>
            <li><strong>Tracking</strong>: definisikan tahapan proses order.</li>
            <li><strong>Studio</strong>: daftarkan studio per cabang.</li>
            <li><strong>Karyawan & Role Karyawan</strong>: kelola user, hak akses, dan kewenangan menu.</li>
        </ul>

        <p class="mb-2"><strong>Paket</strong></p>
        <ul>
            <li>Kelola paket utama, kategori paket, add on, dan kategori add on.</li>
            <li>Atur Template Harga untuk skema harga per item/paket.</li>
        </ul>

        <p class="mb-2"><strong>Sales Mode</strong></p>
        <ul>
            <li>Buat mode penjualan (contoh: offline/event/marketplace).</li>
            <li>Hubungkan mode dengan template harga agar transaksi otomatis ambil harga sesuai channel.</li>
        </ul>

        <p class="mb-2"><strong>Promosi</strong></p>
        <ul>
            <li>Kelola voucher dan diskon otomatis.</li>
            <li>Pastikan periode, syarat, dan batas pemakaian sesuai kebijakan promo.</li>
        </ul>

        <p class="mb-0"><strong>Persediaan & Pembelian</strong>: setup barang/jasa, stok, permintaan, pemasok, pesanan pembelian, penerimaan, faktur, pembayaran, dan retur sebagai rantai proses pengadaan.</p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h6 class="mb-3">4. Modul Persediaan</h6>
        <ul class="mb-0">
            <li><strong>Barang & Jasa</strong>: tambah dan update master item.</li>
            <li><strong>Satuan</strong>: definisikan satuan barang (pcs, set, box, dll).</li>
            <li><strong>Golongan</strong>: kelompokkan item untuk pengelompokan laporan.</li>
            <li><strong>Stok Barang</strong>: monitor stok per cabang.</li>
            <li><strong>Penyesuaian Stok</strong>: koreksi stok fisik vs sistem (buat, edit, hapus penyesuaian sesuai otorisasi).</li>
            <li><strong>Permintaan Barang</strong>: ajukan kebutuhan barang antar proses internal.</li>
        </ul>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h6 class="mb-3">5. Modul Pembelian</h6>
        <ul class="mb-0">
            <li><strong>Pemasok</strong>: input data vendor aktif.</li>
            <li><strong>Pesanan Pembelian</strong>: buat PO, review, lalu close saat selesai.</li>
            <li><strong>Penerimaan Barang</strong>: catat barang datang berdasarkan PO.</li>
            <li><strong>Faktur Pembelian</strong>: catat tagihan dari pemasok.</li>
            <li><strong>Pembayaran Pembelian</strong>: input pembayaran faktur dan pantau outstanding.</li>
            <li><strong>Retur Pembelian</strong>: catat pengembalian barang ke pemasok.</li>
        </ul>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h6 class="mb-3">6. Modul Laporan</h6>
        <ul class="mb-0">
            <li><strong>Laporan Penjualan</strong>: ringkasan order, total, pembayaran, dan sisa.</li>
            <li><strong>Laporan Barang/Jasa</strong>: performa item non-paket.</li>
            <li><strong>Laporan Booking</strong>: monitoring transaksi booking.</li>
            <li><strong>Laporan Promosi</strong>: efektivitas voucher/diskon.</li>
            <li><strong>Laporan Penjualan Paket</strong>: performa paket.</li>
            <li><strong>Laporan Pembayaran</strong>: arus pembayaran per metode.</li>
            <li><strong>Laporan Kasir</strong>: rekap kasir dan setoran.</li>
            <li><strong>Laporan Tutup Kasir</strong>: detail buka/tutup shift, kas expected, kas fisik, selisih.</li>
            <li><strong>Laporan Performa Karyawan</strong>: kontribusi karyawan pada transaksi/proses.</li>
            <li>Gunakan filter tanggal/cabang/kasir lalu export XLSX jika diperlukan.</li>
        </ul>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h6 class="mb-3">7. Modul Studio</h6>
        <ul class="mb-0">
            <li><strong>Daftar Antrian Studio</strong>: panggil antrian, mulai sesi studio, selesaikan sesi.</li>
            <li><strong>Board & Display Customer</strong>: tampilkan antrean live untuk operator dan pelanggan.</li>
            <li><strong>Audio Announcer</strong>: aktifkan panggilan suara antrean.</li>
            <li><strong>Tracking Detail</strong>: cek checklist item tracking dari antrian.</li>
        </ul>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h6 class="mb-3">8. Modul Finance, Produksi, CS, dan System</h6>
        <ul class="mb-0">
            <li><strong>Finance</strong>: COA, Tax, dan Metode Pembayaran untuk kebutuhan kontrol keuangan.</li>
            <li><strong>Produksi</strong>: Daftar Pekerjaan DG dan Daftar QC untuk monitoring proses produksi.</li>
            <li><strong>Customer Service</strong>: Daftar Konfirmasi, Documentation, dan Support.</li>
            <li><strong>System Sync</strong>: gunakan Sinkronisasi Cloud untuk push/bootstrap manual saat dibutuhkan.</li>
        </ul>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h6 class="mb-3">9. Catatan Operasional Penting</h6>
        <ul class="mb-0">
            <li>Gunakan akun masing-masing; hindari berbagi akun agar audit trail akurat.</li>
            <li>Tutup shift kasir di hari yang sama untuk menghindari stale open shift.</li>
            <li>Pastikan metode pembayaran CASH tidak dinonaktifkan karena dipakai proses tutup kasir.</li>
            <li>Lakukan review laporan harian sebelum pergantian hari operasional.</li>
        </ul>
    </div>
</div>
@endsection
