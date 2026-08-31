<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->dropColumn(['tipe', 'satuan']);
        });
    }

    public function down(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->enum('tipe', ['BARANG', 'JASA'])->default('BARANG')->after('nama');
            $table->string('satuan', 30)->nullable()->after('harga_default');
        });
    }
};
