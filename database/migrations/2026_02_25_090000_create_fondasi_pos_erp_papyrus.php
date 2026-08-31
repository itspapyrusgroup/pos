<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pelanggan', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150);
            $table->string('no_hp', 20)->index();
            $table->string('email', 150)->nullable();
            $table->text('alamat')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('pemasok', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama', 150);
            $table->string('kontak', 100)->nullable();
            $table->string('telepon', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->string('npwp', 30)->nullable();
            $table->string('kategori', 50)->default('Default');
            $table->unsignedInteger('credit_terms_hari')->default(0);
            $table->boolean('status')->default(true);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('kategori_produk', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            $table->timestamps();
        });

        Schema::create('tema_studio', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            $table->text('deskripsi')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('studio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->foreignId('tema_studio_id')->nullable()->constrained('tema_studio')->nullOnDelete();
            $table->string('nama', 100);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('divisi', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('karyawan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->foreignId('divisi_id')->nullable()->constrained('divisi')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nama', 150);
            $table->string('no_hp', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('produk', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->unique();
            $table->string('nama', 150);
            $table->enum('tipe', ['BARANG', 'JASA']);
            $table->foreignId('kategori_produk_id')->nullable()->constrained('kategori_produk')->nullOnDelete();
            $table->boolean('track_stok')->default(false);
            $table->decimal('harga_default', 18, 2)->default(0);
            $table->string('satuan', 30)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('paket', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 150);
            $table->text('deskripsi')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('paket_cabang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paket_id')->constrained('paket')->cascadeOnDelete();
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['paket_id', 'cabang_id']);
        });

        Schema::create('paket_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paket_id')->constrained('paket')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('produk')->cascadeOnDelete();
            $table->decimal('qty', 14, 2);
            $table->timestamps();
            $table->unique(['paket_id', 'produk_id']);
        });

        Schema::create('daftar_harga', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->enum('channel', ['OFFLINE', 'MARKETPLACE']);
            $table->foreignId('produk_id')->nullable()->constrained('produk')->cascadeOnDelete();
            $table->foreignId('paket_id')->nullable()->constrained('paket')->cascadeOnDelete();
            $table->decimal('harga', 18, 2);
            $table->timestamp('aktif_mulai')->nullable();
            $table->timestamp('aktif_sampai')->nullable();
            $table->timestamps();
            $table->index(['cabang_id', 'channel']);
        });

        Schema::create('shift_kasir', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('modal_awal', 18, 2)->default(0);
            $table->decimal('kas_expected', 18, 2)->default(0);
            $table->decimal('kas_fisik', 18, 2)->nullable();
            $table->decimal('selisih', 18, 2)->nullable();
            $table->timestamp('dibuka_pada');
            $table->timestamp('ditutup_pada')->nullable();
            $table->enum('status', ['OPEN', 'CLOSED'])->default('OPEN');
            $table->timestamps();
        });

        Schema::create('pesanan_penjualan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_so', 30)->unique();
            $table->foreignId('pelanggan_id')->nullable()->constrained('pelanggan')->nullOnDelete();
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->foreignId('shift_kasir_id')->nullable()->constrained('shift_kasir')->nullOnDelete();
            $table->decimal('total', 18, 2)->default(0);
            $table->decimal('paid_total', 18, 2)->default(0);
            $table->decimal('balance', 18, 2)->default(0);
            $table->enum('status_pembayaran', ['DRAFT', 'PARTIALLY_PAID', 'PAID', 'CANCELLED'])->default('DRAFT');
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->index(['cabang_id', 'created_at']);
        });

        Schema::create('pesanan_penjualan_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_penjualan_id')->constrained('pesanan_penjualan')->cascadeOnDelete();
            $table->foreignId('produk_id')->nullable()->constrained('produk')->nullOnDelete();
            $table->foreignId('paket_id')->nullable()->constrained('paket')->nullOnDelete();
            $table->decimal('qty', 14, 2);
            $table->decimal('harga', 18, 2);
            $table->decimal('diskon', 18, 2)->default(0);
            $table->decimal('subtotal', 18, 2);
            $table->timestamps();
        });

        Schema::create('metode_pembayaran', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 100);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('pembayaran_penjualan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_penjualan_id')->constrained('pesanan_penjualan')->cascadeOnDelete();
            $table->foreignId('metode_pembayaran_id')->constrained('metode_pembayaran')->cascadeOnDelete();
            $table->foreignId('shift_kasir_id')->nullable()->constrained('shift_kasir')->nullOnDelete();
            $table->decimal('nominal', 18, 2);
            $table->enum('tipe', ['DP', 'FINAL', 'ADDON']);
            $table->timestamp('tanggal_bayar');
            $table->timestamps();
            $table->index(['created_at', 'metode_pembayaran_id']);
        });

        Schema::create('booking_studio', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_booking', 30)->unique();
            $table->foreignId('pesanan_penjualan_id')->nullable()->constrained('pesanan_penjualan')->nullOnDelete();
            $table->foreignId('pelanggan_id')->nullable()->constrained('pelanggan')->nullOnDelete();
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->foreignId('studio_id')->nullable()->constrained('studio')->nullOnDelete();
            $table->timestamp('tanggal_booking');
            $table->enum('status', ['BOOKED_UNPAID', 'BOOKED_DP', 'CHECKED_IN', 'CANCELLED', 'DONE'])->default('BOOKED_UNPAID');
            $table->timestamps();
        });

        Schema::create('aturan_antrian_cabang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->decimal('minimal_bayar', 18, 2)->nullable();
            $table->boolean('harus_lunas_paket_utama')->default(false);
            $table->timestamps();
            $table->unique('cabang_id');
        });

        Schema::create('antrian_studio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_studio_id')->nullable()->constrained('booking_studio')->nullOnDelete();
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->foreignId('studio_id')->nullable()->constrained('studio')->nullOnDelete();
            $table->unsignedInteger('nomor_antrian');
            $table->enum('status', ['WAITING', 'CALLED', 'IN_STUDIO', 'DONE'])->default('WAITING');
            $table->timestamp('waktu_panggil')->nullable();
            $table->timestamps();
            $table->index(['cabang_id', 'created_at']);
        });

        Schema::create('kantong_order', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_ko', 30)->unique();
            $table->foreignId('pesanan_penjualan_id')->constrained('pesanan_penjualan')->cascadeOnDelete();
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->foreignId('designer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', [
                'CREATED',
                'PHOTO_DONE',
                'SELECTION_DONE',
                'ASSIGNED',
                'EDITING',
                'DONE_EDIT',
                'WAITING_ACC',
                'REVISION_REQUESTED',
                'APPROVED',
                'PRINTING',
                'FRAMING',
                'QC',
                'READY',
                'PICKED_UP',
                'FILE_SENT',
                'CLOSED'
            ])->default('CREATED');
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->index('status');
        });

        Schema::create('riwayat_status_ko', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kantong_order_id')->constrained('kantong_order')->cascadeOnDelete();
            $table->enum('status', [
                'CREATED',
                'PHOTO_DONE',
                'SELECTION_DONE',
                'ASSIGNED',
                'EDITING',
                'DONE_EDIT',
                'WAITING_ACC',
                'REVISION_REQUESTED',
                'APPROVED',
                'PRINTING',
                'FRAMING',
                'QC',
                'READY',
                'PICKED_UP',
                'FILE_SENT',
                'CLOSED'
            ]);
            $table->foreignId('diubah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamp('waktu_status');
            $table->timestamps();
        });

        Schema::create('catatan_revisi_ko', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kantong_order_id')->constrained('kantong_order')->cascadeOnDelete();
            $table->text('catatan_revisi');
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tanggal_revisi');
            $table->timestamps();
        });

        Schema::create('qc_ko', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kantong_order_id')->constrained('kantong_order')->cascadeOnDelete();
            $table->boolean('lulus_qc');
            $table->text('catatan')->nullable();
            $table->foreignId('diperiksa_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tanggal_qc');
            $table->timestamps();
        });

        Schema::create('stok_cabang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('produk')->cascadeOnDelete();
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->decimal('qty', 18, 2)->default(0);
            $table->timestamps();
            $table->unique(['produk_id', 'cabang_id']);
        });

        Schema::create('kartu_stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('produk')->cascadeOnDelete();
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->enum('tipe_mutasi', ['PEMBELIAN', 'PENJUALAN', 'RETUR', 'ADJUSTMENT']);
            $table->string('referensi_tipe', 50)->nullable();
            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->decimal('qty_masuk', 18, 2)->default(0);
            $table->decimal('qty_keluar', 18, 2)->default(0);
            $table->decimal('saldo_akhir', 18, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->timestamp('tanggal_mutasi');
            $table->timestamps();
            $table->index(['produk_id', 'cabang_id']);
        });


        Schema::create('voucher_promosi', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 100);
            $table->enum('tipe_diskon', ['PERSEN', 'NOMINAL']);
            $table->decimal('nilai_diskon', 18, 2);
            $table->decimal('minimum_pembelian', 18, 2)->default(0);
            $table->foreignId('cabang_id')->nullable()->constrained('cabang')->nullOnDelete();
            $table->date('aktif_mulai');
            $table->date('aktif_sampai');
            $table->json('hari_aktif')->nullable();
            $table->unsignedInteger('kuota')->nullable();
            $table->unsignedInteger('terpakai')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('diskon_otomatis', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->enum('tipe_diskon', ['PERSEN', 'NOMINAL']);
            $table->decimal('nilai_diskon', 18, 2);
            $table->decimal('minimum_pembelian', 18, 2)->default(0);
            $table->foreignId('cabang_id')->nullable()->constrained('cabang')->nullOnDelete();
            $table->date('aktif_mulai');
            $table->date('aktif_sampai');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entitas', 100);
            $table->unsignedBigInteger('entitas_id')->nullable();
            $table->string('aksi', 50);
            $table->json('data_lama')->nullable();
            $table->json('data_baru')->nullable();
            $table->timestamps();
            $table->index(['entitas', 'entitas_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
        Schema::dropIfExists('diskon_otomatis');
        Schema::dropIfExists('voucher_promosi');
        Schema::dropIfExists('pembelian_item');
        Schema::dropIfExists('pembelian');
        Schema::dropIfExists('kartu_stok');
        Schema::dropIfExists('stok_cabang');
        Schema::dropIfExists('qc_ko');
        Schema::dropIfExists('catatan_revisi_ko');
        Schema::dropIfExists('riwayat_status_ko');
        Schema::dropIfExists('kantong_order');
        Schema::dropIfExists('antrian_studio');
        Schema::dropIfExists('aturan_antrian_cabang');
        Schema::dropIfExists('booking_studio');
        Schema::dropIfExists('pembayaran_penjualan');
        Schema::dropIfExists('metode_pembayaran');
        Schema::dropIfExists('pesanan_penjualan_item');
        Schema::dropIfExists('pesanan_penjualan');
        Schema::dropIfExists('shift_kasir');
        Schema::dropIfExists('daftar_harga');
        Schema::dropIfExists('paket_item');
        Schema::dropIfExists('paket_cabang');
        Schema::dropIfExists('paket');
        Schema::dropIfExists('produk');
        Schema::dropIfExists('karyawan');
        Schema::dropIfExists('divisi');
        Schema::dropIfExists('studio');
        Schema::dropIfExists('tema_studio');
        Schema::dropIfExists('kategori_produk');
        Schema::dropIfExists('pemasok');
        Schema::dropIfExists('pelanggan');
    }
};
