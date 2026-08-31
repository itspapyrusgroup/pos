<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('penjualan_void_otps')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE penjualan_void_otps MODIFY COLUMN tipe_void ENUM('FULL', 'PARTIAL', 'REMOVE') NOT NULL");
    }

    public function down(): void
    {
        if (!Schema::hasTable('penjualan_void_otps')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE penjualan_void_otps MODIFY COLUMN tipe_void ENUM('FULL', 'PARTIAL') NOT NULL");
    }
};
