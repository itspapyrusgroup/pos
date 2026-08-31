<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penerimaan_barang', function (Blueprint $table) {
            $table->string('nomor_surat_jalan', 50)->nullable()->after('nomor_penerimaan');
        });
    }

    public function down(): void
    {
        Schema::table('penerimaan_barang', function (Blueprint $table) {
            $table->dropColumn('nomor_surat_jalan');
        });
    }
};
