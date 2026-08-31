<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('permintaan_barang', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_permintaan', 30)->unique();
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->date('tanggal_permintaan');
            $table->date('tanggal_dibutuhkan')->nullable();
            $table->enum('status', ['DRAFT', 'APPROVED', 'PROCESSED', 'CANCELLED'])->default('DRAFT');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('permintaan_barang_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permintaan_barang_id')->constrained('permintaan_barang')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('produk')->cascadeOnDelete();
            $table->decimal('qty', 14, 2);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('pesanan_pembelian', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_po', 30)->unique();
            $table->foreignId('permintaan_barang_id')->nullable()->constrained('permintaan_barang')->nullOnDelete();
            $table->foreignId('pemasok_id')->constrained('pemasok')->cascadeOnDelete();
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->date('tanggal_po');
            $table->date('tanggal_kirim')->nullable();
            $table->enum('status', ['DRAFT', 'ORDERED', 'PARTIAL_RECEIVED', 'RECEIVED', 'CLOSED'])->default('DRAFT');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('pesanan_pembelian_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_pembelian_id')->constrained('pesanan_pembelian')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('produk')->cascadeOnDelete();
            $table->decimal('qty', 14, 2);
            $table->decimal('harga', 18, 2)->default(0);
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('penerimaan_barang', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_penerimaan', 30)->unique();
            $table->foreignId('pesanan_pembelian_id')->constrained('pesanan_pembelian')->cascadeOnDelete();
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->date('tanggal_penerimaan');
            $table->enum('status', ['DRAFT', 'POSTED'])->default('POSTED');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('penerimaan_barang_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penerimaan_barang_id')->constrained('penerimaan_barang')->cascadeOnDelete();
            $table->foreignId('pesanan_pembelian_item_id')->constrained('pesanan_pembelian_item')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('produk')->cascadeOnDelete();
            $table->decimal('qty_terima', 14, 2);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('faktur_pembelian', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_faktur', 30)->unique();
            $table->foreignId('pesanan_pembelian_id')->nullable()->constrained('pesanan_pembelian')->nullOnDelete();
            $table->foreignId('pemasok_id')->constrained('pemasok')->cascadeOnDelete();
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->date('tanggal_faktur');
            $table->date('jatuh_tempo')->nullable();
            $table->decimal('total', 18, 2)->default(0);
            $table->decimal('dibayar', 18, 2)->default(0);
            $table->enum('status', ['DRAFT', 'PARTIAL', 'PAID'])->default('DRAFT');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('faktur_pembelian_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faktur_pembelian_id')->constrained('faktur_pembelian')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('produk')->cascadeOnDelete();
            $table->decimal('qty', 14, 2);
            $table->decimal('harga', 18, 2)->default(0);
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('pembayaran_pembelian', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pembayaran', 30)->unique();
            $table->foreignId('faktur_pembelian_id')->constrained('faktur_pembelian')->cascadeOnDelete();
            $table->foreignId('metode_pembayaran_id')->nullable()->constrained('metode_pembayaran')->nullOnDelete();
            $table->date('tanggal_bayar');
            $table->decimal('nominal', 18, 2);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('retur_pembelian', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_retur', 30)->unique();
            $table->foreignId('penerimaan_barang_id')->constrained('penerimaan_barang')->cascadeOnDelete();
            $table->foreignId('pesanan_pembelian_id')->constrained('pesanan_pembelian')->cascadeOnDelete();
            $table->foreignId('pemasok_id')->constrained('pemasok')->cascadeOnDelete();
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->date('tanggal_retur');
            $table->enum('status', ['DRAFT', 'POSTED'])->default('POSTED');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('retur_pembelian_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retur_pembelian_id')->constrained('retur_pembelian')->cascadeOnDelete();
            $table->foreignId('penerimaan_barang_item_id')->constrained('penerimaan_barang_item')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('produk')->cascadeOnDelete();
            $table->decimal('qty', 14, 2);
            $table->string('alasan_retur', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retur_pembelian_item');
        Schema::dropIfExists('retur_pembelian');
        Schema::dropIfExists('pembayaran_pembelian');
        Schema::dropIfExists('faktur_pembelian_item');
        Schema::dropIfExists('faktur_pembelian');
        Schema::dropIfExists('penerimaan_barang_item');
        Schema::dropIfExists('penerimaan_barang');
        Schema::dropIfExists('pesanan_pembelian_item');
        Schema::dropIfExists('pesanan_pembelian');
        Schema::dropIfExists('permintaan_barang_item');
        Schema::dropIfExists('permintaan_barang');
    }
};
