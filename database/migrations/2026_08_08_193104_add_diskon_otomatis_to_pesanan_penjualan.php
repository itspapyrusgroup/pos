<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesanan_penjualan', function (Blueprint $table) {
            $table->decimal('diskon_otomatis', 14, 2)->default(0)->after('total');
        });
    }

    public function down(): void
    {
        Schema::table('pesanan_penjualan', function (Blueprint $table) {
            $table->dropColumn('diskon_otomatis');
        });
    }
};
