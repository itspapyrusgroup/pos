<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('booking_studio', function (Blueprint $table) {
            $table->index(['cabang_id', 'tanggal_booking'], 'booking_studio_cabang_tanggal_booking_idx');
        });

        Schema::table('antrian_studio', function (Blueprint $table) {
            $table->index(['cabang_id', 'studio_id', 'booking_studio_id'], 'antrian_studio_cabang_studio_booking_idx');
        });

        Schema::table('antrian_studio_tugas', function (Blueprint $table) {
            $table->index(['antrian_studio_id', 'updated_at'], 'antrian_studio_tugas_antrian_updated_idx');
        });

        Schema::table('ko_tracking_item_checks', function (Blueprint $table) {
            $table->index(['pesanan_penjualan_item_id', 'updated_at'], 'ko_tracking_item_checks_item_updated_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ko_tracking_item_checks', function (Blueprint $table) {
            $table->dropIndex('ko_tracking_item_checks_item_updated_idx');
        });

        Schema::table('antrian_studio_tugas', function (Blueprint $table) {
            $table->dropIndex('antrian_studio_tugas_antrian_updated_idx');
        });

        Schema::table('antrian_studio', function (Blueprint $table) {
            $table->dropIndex('antrian_studio_cabang_studio_booking_idx');
        });

        Schema::table('booking_studio', function (Blueprint $table) {
            $table->dropIndex('booking_studio_cabang_tanggal_booking_idx');
        });
    }
};
