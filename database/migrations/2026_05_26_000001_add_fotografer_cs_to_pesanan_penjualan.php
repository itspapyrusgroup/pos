<?php

// use Illuminate\Database\Migrations\Migration;
// use Illuminate\Database\Schema\Blueprint;
// use Illuminate\Support\Facades\Schema;

// return new class extends Migration
// {
//     public function up(): void
//     {
//         Schema::table('pesanan_penjualan', function (Blueprint $table) {
//             // Tambahan: fotografer (studio)
//             $table->unsignedBigInteger('fotografer_user_id')->nullable()->after('spv_user_id');

//             // Ubah CS: CS, CS 1, CS 2 (3 kolom terpisah)
//             $table->unsignedBigInteger('cs_user_id')->nullable()->after('fotografer_user_id');
//             $table->unsignedBigInteger('cs1_user_id')->nullable()->after('cs_user_id');
//             $table->unsignedBigInteger('cs2_user_id')->nullable()->after('cs1_user_id');

//             // Add foreign keys
//             $table->foreign('fotografer_user_id')->references('id')->on('users')->onDelete('set null');
//             $table->foreign('cs_user_id')->references('id')->on('users')->onDelete('set null');
//             $table->foreign('cs1_user_id')->references('id')->on('users')->onDelete('set null');
//             $table->foreign('cs2_user_id')->references('id')->on('users')->onDelete('set null');
//         });
//     }

//     public function down(): void
//     {
//         Schema::table('pesanan_penjualan', function (Blueprint $table) {
//             $table->dropForeign(['fotografer_user_id']);
//             $table->dropForeign(['cs_user_id']);
//             $table->dropForeign(['cs1_user_id']);
//             $table->dropForeign(['cs2_user_id']);
//             $table->dropColumn(['fotografer_user_id', 'cs_user_id', 'cs1_user_id', 'cs2_user_id']);
//         });
//     }
// };

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesanan_penjualan', function (Blueprint $table) {
            // Tambah kolom cs_user_id jika belum ada (kolom lain sudah ada di production)
            if (!Schema::hasColumn('pesanan_penjualan', 'cs_user_id')) {
                $table->unsignedBigInteger('cs_user_id')->nullable()->after('fotografer_user_id');
            }

            // Add foreign key hanya jika kolom baru ditambahkan
            if (Schema::hasColumn('pesanan_penjualan', 'cs_user_id') && !$this->foreignKeyExists('pesanan_penjualan', 'cs_user_id')) {
                $table->foreign('cs_user_id')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pesanan_penjualan', function (Blueprint $table) {
            if (Schema::hasColumn('pesanan_penjualan', 'cs_user_id')) {
                $table->dropForeign(['cs_user_id']);
                $table->dropColumn('cs_user_id');
            }
        });
    }

    /**
     * Check if foreign key exists
     */
    private function foreignKeyExists(string $table, string $column): bool
    {
        $foreignKeys = DB::select("
            SELECT COUNT(*) as count FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND COLUMN_NAME = ?
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$table, $column]);

        return $foreignKeys[0]->count > 0;
    }
};