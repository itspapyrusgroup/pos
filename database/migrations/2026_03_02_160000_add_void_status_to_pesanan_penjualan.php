<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("
            ALTER TABLE pesanan_penjualan
            MODIFY status_pembayaran ENUM('DRAFT','PARTIALLY_PAID','PAID','VOID','CANCELLED')
            NOT NULL DEFAULT 'DRAFT'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE pesanan_penjualan
            MODIFY status_pembayaran ENUM('DRAFT','PARTIALLY_PAID','PAID','CANCELLED')
            NOT NULL DEFAULT 'DRAFT'
        ");
    }
};
