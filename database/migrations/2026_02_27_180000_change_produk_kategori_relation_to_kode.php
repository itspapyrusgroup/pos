<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('produk', 'kategori_produk_kode')) {
            Schema::table('produk', function (Blueprint $table) {
                $table->string('kategori_produk_kode', 30)->nullable()->after('nama');
            });
        }

        if (Schema::hasColumn('produk', 'kategori_produk_id')) {
            DB::statement('
                UPDATE produk p
                LEFT JOIN kategori_produk k ON k.id = p.kategori_produk_id
                SET p.kategori_produk_kode = k.kode
            ');

            try {
                Schema::table('produk', function (Blueprint $table) {
                    $table->dropForeign(['kategori_produk_id']);
                });
            } catch (\Throwable $e) {
                // ignore jika FK lama sudah tidak ada
            }

            Schema::table('produk', function (Blueprint $table) {
                $table->dropColumn('kategori_produk_id');
            });
        }

        try {
            Schema::table('produk', function (Blueprint $table) {
                $table->dropForeign(['kategori_produk_kode']);
            });
        } catch (\Throwable $e) {
            // ignore jika FK belum ada
        }

        DB::statement('ALTER TABLE produk MODIFY kategori_produk_kode VARCHAR(30) NULL');
        DB::statement('
            UPDATE produk p
            JOIN kategori_produk k ON k.id = CAST(p.kategori_produk_kode AS UNSIGNED)
            SET p.kategori_produk_kode = k.kode
            WHERE p.kategori_produk_kode REGEXP "^[0-9]+$"
        ');

        Schema::table('produk', function (Blueprint $table) {
            $table->foreign('kategori_produk_kode')
                ->references('kode')
                ->on('kategori_produk')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('produk', 'kategori_produk_id')) {
            Schema::table('produk', function (Blueprint $table) {
                $table->unsignedBigInteger('kategori_produk_id')->nullable()->after('nama');
            });
        }

        if (Schema::hasColumn('produk', 'kategori_produk_kode')) {
            DB::statement('
                UPDATE produk p
                LEFT JOIN kategori_produk k ON k.kode = p.kategori_produk_kode
                SET p.kategori_produk_id = k.id
            ');

            try {
                Schema::table('produk', function (Blueprint $table) {
                    $table->dropForeign(['kategori_produk_kode']);
                });
            } catch (\Throwable $e) {
                // ignore jika FK belum ada
            }

            Schema::table('produk', function (Blueprint $table) {
                $table->dropColumn('kategori_produk_kode');
            });
        }

        try {
            Schema::table('produk', function (Blueprint $table) {
                $table->dropForeign(['kategori_produk_id']);
            });
        } catch (\Throwable $e) {
            // ignore jika FK sudah tidak ada
        }

        Schema::table('produk', function (Blueprint $table) {
            $table->foreign('kategori_produk_id')
                ->references('id')
                ->on('kategori_produk')
                ->nullOnDelete();
        });
    }
};
