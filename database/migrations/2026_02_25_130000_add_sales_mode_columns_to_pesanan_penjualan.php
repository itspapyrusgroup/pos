<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pesanan_penjualan', function (Blueprint $table) {
            $table->foreignId('sales_mode_id')->nullable()->after('cabang_id')->constrained('sales_mode')->nullOnDelete();
            $table->foreignId('template_harga_id')->nullable()->after('sales_mode_id')->constrained('template_harga')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pesanan_penjualan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('template_harga_id');
            $table->dropConstrainedForeignId('sales_mode_id');
        });
    }
};
