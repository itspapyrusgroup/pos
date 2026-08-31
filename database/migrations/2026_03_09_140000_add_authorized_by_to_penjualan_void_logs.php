<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('penjualan_void_logs')) {
            return;
        }

        Schema::table('penjualan_void_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('penjualan_void_logs', 'authorized_by_user_id')) {
                $table->foreignId('authorized_by_user_id')->nullable()->after('voided_by_user_id')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('penjualan_void_logs') || !Schema::hasColumn('penjualan_void_logs', 'authorized_by_user_id')) {
            return;
        }

        Schema::table('penjualan_void_logs', function (Blueprint $table) {
            $table->dropForeign(['authorized_by_user_id']);
            $table->dropColumn('authorized_by_user_id');
        });
    }
};
