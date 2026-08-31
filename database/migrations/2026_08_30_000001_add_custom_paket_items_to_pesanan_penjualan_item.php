<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesanan_penjualan_item', function (Blueprint $table) {
            if (!Schema::hasColumn('pesanan_penjualan_item', 'custom_paket_items')) {
                $table->json('custom_paket_items')->nullable()->after('paket_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pesanan_penjualan_item', function (Blueprint $table) {
            if (Schema::hasColumn('pesanan_penjualan_item', 'custom_paket_items')) {
                $table->dropColumn('custom_paket_items');
            }
        });
    }
};
