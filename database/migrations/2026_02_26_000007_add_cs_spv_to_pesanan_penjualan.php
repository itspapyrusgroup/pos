<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pesanan_penjualan', function (Blueprint $table) {
            $table->foreignId('cs1_user_id')->nullable()->after('kasir_user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('cs2_user_id')->nullable()->after('cs1_user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('spv_user_id')->nullable()->after('cs2_user_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pesanan_penjualan', function (Blueprint $table) {
            $table->dropForeign(['spv_user_id']);
            $table->dropForeign(['cs2_user_id']);
            $table->dropForeign(['cs1_user_id']);
            $table->dropColumn(['cs1_user_id', 'cs2_user_id', 'spv_user_id']);
        });
    }
};
