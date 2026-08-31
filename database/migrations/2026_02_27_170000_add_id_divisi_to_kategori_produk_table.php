<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kategori_produk', function (Blueprint $table) {
            $table->foreignId('id_divisi')->nullable()->after('kode')->constrained('divisi')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kategori_produk', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_divisi');
        });
    }
};
