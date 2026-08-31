<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kantong_order', function (Blueprint $table) {
            $table->date('tanggal_selesai')->nullable()->after('status');
            $table->index('tanggal_selesai');
        });
    }

    public function down(): void
    {
        Schema::table('kantong_order', function (Blueprint $table) {
            $table->dropIndex(['tanggal_selesai']);
            $table->dropColumn('tanggal_selesai');
        });
    }
};
