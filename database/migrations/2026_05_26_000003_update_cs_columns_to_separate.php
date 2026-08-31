<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesanan_penjualan', function (Blueprint $table) {
            if (Schema::hasColumn('pesanan_penjualan', 'cs_user_ids')) {
                $table->dropColumn('cs_user_ids');
            }

            if (!Schema::hasColumn('pesanan_penjualan', 'cs_user_id')) {
                $table->unsignedBigInteger('cs_user_id')->nullable()->after('fotografer_user_id');
                $table->foreign('cs_user_id')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pesanan_penjualan', function (Blueprint $table) {
            $table->dropForeign(['cs_user_id']);
            $table->dropColumn('cs_user_id');

            // Kembalikan cs_user_ids sebagai JSON
            $table->json('cs_user_ids')->nullable();
        });
    }
};