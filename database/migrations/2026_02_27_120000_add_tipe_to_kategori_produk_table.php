<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kategori_produk', function (Blueprint $table) {
            $table->enum('tipe', ['BARANG', 'JASA'])->default('BARANG')->after('nama');
        });

        DB::table('kategori_produk')
            ->whereNull('tipe')
            ->update(['tipe' => 'BARANG']);
    }

    public function down(): void
    {
        Schema::table('kategori_produk', function (Blueprint $table) {
            $table->dropColumn('tipe');
        });
    }
};

