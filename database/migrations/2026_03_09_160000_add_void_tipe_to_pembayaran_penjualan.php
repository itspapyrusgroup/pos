<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("
            ALTER TABLE pembayaran_penjualan
            MODIFY tipe ENUM('DP','FINAL','ADDON','VOID')
            NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE pembayaran_penjualan
            MODIFY tipe ENUM('DP','FINAL','ADDON')
            NOT NULL
        ");
    }
};

