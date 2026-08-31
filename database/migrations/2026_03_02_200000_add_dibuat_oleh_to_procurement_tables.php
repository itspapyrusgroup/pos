<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('permintaan_barang', function (Blueprint $table) {
            $table->foreignId('dibuat_oleh')->nullable()->after('status')->constrained('users')->nullOnDelete();
        });

        Schema::table('pesanan_pembelian', function (Blueprint $table) {
            $table->foreignId('dibuat_oleh')->nullable()->after('status')->constrained('users')->nullOnDelete();
        });

        Schema::table('penerimaan_barang', function (Blueprint $table) {
            $table->foreignId('dibuat_oleh')->nullable()->after('status')->constrained('users')->nullOnDelete();
        });

        Schema::table('faktur_pembelian', function (Blueprint $table) {
            $table->foreignId('dibuat_oleh')->nullable()->after('status')->constrained('users')->nullOnDelete();
        });

        Schema::table('pembayaran_pembelian', function (Blueprint $table) {
            $table->foreignId('dibuat_oleh')->nullable()->after('nominal')->constrained('users')->nullOnDelete();
        });

        Schema::table('retur_pembelian', function (Blueprint $table) {
            $table->foreignId('dibuat_oleh')->nullable()->after('status')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('retur_pembelian', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dibuat_oleh');
        });

        Schema::table('pembayaran_pembelian', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dibuat_oleh');
        });

        Schema::table('faktur_pembelian', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dibuat_oleh');
        });

        Schema::table('penerimaan_barang', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dibuat_oleh');
        });

        Schema::table('pesanan_pembelian', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dibuat_oleh');
        });

        Schema::table('permintaan_barang', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dibuat_oleh');
        });
    }
};
