<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('retur_pembelian')) {
            Schema::table('retur_pembelian', function (Blueprint $table) {
                if (!Schema::hasColumn('retur_pembelian', 'penerimaan_barang_id')) {
                    $table->foreignId('penerimaan_barang_id')->nullable()->after('nomor_retur')
                        ->constrained('penerimaan_barang')->nullOnDelete();
                }

                if (!Schema::hasColumn('retur_pembelian', 'pesanan_pembelian_id')) {
                    $table->foreignId('pesanan_pembelian_id')->nullable()->after('penerimaan_barang_id')
                        ->constrained('pesanan_pembelian')->nullOnDelete();
                }

                if (!Schema::hasColumn('retur_pembelian', 'status')) {
                    $table->enum('status', ['DRAFT', 'POSTED'])->default('POSTED')->after('tanggal_retur');
                }
            });

            if (Schema::hasColumn('retur_pembelian', 'pembelian_id')) {
                DB::statement('ALTER TABLE retur_pembelian MODIFY pembelian_id BIGINT UNSIGNED NULL');
            }
        }

        if (Schema::hasTable('retur_pembelian_item')) {
            Schema::table('retur_pembelian_item', function (Blueprint $table) {
                if (!Schema::hasColumn('retur_pembelian_item', 'penerimaan_barang_item_id')) {
                    $table->foreignId('penerimaan_barang_item_id')->nullable()->after('retur_pembelian_id')
                        ->constrained('penerimaan_barang_item')->nullOnDelete();
                }

                if (!Schema::hasColumn('retur_pembelian_item', 'produk_id')) {
                    $table->foreignId('produk_id')->nullable()->after('penerimaan_barang_item_id')
                        ->constrained('produk')->nullOnDelete();
                }
            });

            if (Schema::hasColumn('retur_pembelian_item', 'pembelian_item_id')) {
                DB::statement('ALTER TABLE retur_pembelian_item MODIFY pembelian_item_id BIGINT UNSIGNED NULL');
            }
        }
    }

    public function down(): void
    {
        // Intentionally left empty for legacy compatibility migration.
    }
};
