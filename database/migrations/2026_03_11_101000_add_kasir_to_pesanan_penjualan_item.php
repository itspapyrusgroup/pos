<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pesanan_penjualan_item', function (Blueprint $table) {
            $table->foreignId('shift_kasir_id')
                ->nullable()
                ->after('paket_id')
                ->constrained('shift_kasir')
                ->nullOnDelete();
            $table->foreignId('kasir_user_id')
                ->nullable()
                ->after('shift_kasir_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['pesanan_penjualan_id', 'kasir_user_id'], 'idx_pp_item_order_kasir');
        });
    }

    public function down(): void
    {
        Schema::table('pesanan_penjualan_item', function (Blueprint $table) {
            $table->dropIndex('idx_pp_item_order_kasir');
            $table->dropForeign(['kasir_user_id']);
            $table->dropForeign(['shift_kasir_id']);
            $table->dropColumn(['kasir_user_id', 'shift_kasir_id']);
        });
    }
};
