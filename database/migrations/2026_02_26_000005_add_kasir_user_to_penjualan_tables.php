<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pesanan_penjualan', function (Blueprint $table) {
            $table->foreignId('kasir_user_id')->nullable()->after('shift_kasir_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('pembayaran_penjualan', function (Blueprint $table) {
            $table->foreignId('kasir_user_id')->nullable()->after('shift_kasir_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran_penjualan', function (Blueprint $table) {
            $table->dropForeign(['kasir_user_id']);
            $table->dropColumn('kasir_user_id');
        });

        Schema::table('pesanan_penjualan', function (Blueprint $table) {
            $table->dropForeign(['kasir_user_id']);
            $table->dropColumn('kasir_user_id');
        });
    }
};
