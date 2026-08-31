<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('paket_item') || !Schema::hasTable('paket')) {
            return;
        }

        if (!Schema::hasColumn('paket_item', 'paket_id')) {
            return;
        }

        if (Schema::hasColumn('paket_item', 'paket_kode_tmp')) {
            Schema::table('paket_item', function (Blueprint $table) {
                $table->dropColumn('paket_kode_tmp');
            });
        }

        Schema::table('paket_item', function (Blueprint $table) {
            $table->string('paket_kode_tmp', 30)->nullable()->after('id');
        });

        $paketKodeById = DB::table('paket')->pluck('kode', 'id');
        DB::table('paket_item')->select('id', 'paket_id')->orderBy('id')->chunk(500, function ($rows) use ($paketKodeById) {
            foreach ($rows as $row) {
                $kode = $paketKodeById[(string) $row->paket_id] ?? $paketKodeById[(int) $row->paket_id] ?? null;
                if (!$kode) {
                    continue;
                }
                DB::table('paket_item')->where('id', $row->id)->update(['paket_kode_tmp' => (string) $kode]);
            }
        });

        $unmappedCount = DB::table('paket_item')->whereNull('paket_kode_tmp')->count();
        if ($unmappedCount > 0) {
            throw new \RuntimeException("Migrasi dibatalkan: {$unmappedCount} baris paket_item tidak punya pasangan paket.id yang valid.");
        }

        Schema::table('paket_item', function (Blueprint $table) {
            try {
                $table->dropForeign(['paket_id']);
            } catch (\Throwable $e) {
                // No-op if foreign key already removed.
            }

            try {
                $table->dropUnique('paket_item_paket_id_produk_id_unique');
            } catch (\Throwable $e) {
                // No-op if unique index name differs.
            }
        });

        Schema::table('paket_item', function (Blueprint $table) {
            $table->dropColumn('paket_id');
        });

        Schema::table('paket_item', function (Blueprint $table) {
            $table->renameColumn('paket_kode_tmp', 'paket_id');
        });

        Schema::table('paket_item', function (Blueprint $table) {
            $table->unique(['paket_id', 'produk_id']);
            $table->foreign('paket_id')->references('kode')->on('paket')->cascadeOnDelete();
        });

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE paket_item MODIFY paket_id VARCHAR(30) NOT NULL");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE paket_item ALTER COLUMN paket_id TYPE VARCHAR(30)");
            DB::statement("ALTER TABLE paket_item ALTER COLUMN paket_id SET NOT NULL");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('paket_item') || !Schema::hasTable('paket') || !Schema::hasColumn('paket_item', 'paket_id')) {
            return;
        }

        if (Schema::hasColumn('paket_item', 'paket_id_tmp_bigint')) {
            Schema::table('paket_item', function (Blueprint $table) {
                $table->dropColumn('paket_id_tmp_bigint');
            });
        }

        Schema::table('paket_item', function (Blueprint $table) {
            $table->unsignedBigInteger('paket_id_tmp_bigint')->nullable()->after('id');
        });

        $paketIdByKode = DB::table('paket')->pluck('id', 'kode');
        DB::table('paket_item')->select('id', 'paket_id')->orderBy('id')->chunk(500, function ($rows) use ($paketIdByKode) {
            foreach ($rows as $row) {
                $paketId = $paketIdByKode[(string) $row->paket_id] ?? null;
                if (!$paketId) {
                    continue;
                }
                DB::table('paket_item')->where('id', $row->id)->update(['paket_id_tmp_bigint' => (int) $paketId]);
            }
        });

        $unmappedCount = DB::table('paket_item')->whereNull('paket_id_tmp_bigint')->count();
        if ($unmappedCount > 0) {
            throw new \RuntimeException("Rollback dibatalkan: {$unmappedCount} baris paket_item tidak punya pasangan paket.kode yang valid.");
        }

        Schema::table('paket_item', function (Blueprint $table) {
            try {
                $table->dropForeign(['paket_id']);
            } catch (\Throwable $e) {
                // No-op if foreign key already removed.
            }

            try {
                $table->dropUnique('paket_item_paket_id_produk_id_unique');
            } catch (\Throwable $e) {
                // No-op if unique index name differs.
            }
        });

        Schema::table('paket_item', function (Blueprint $table) {
            $table->dropColumn('paket_id');
        });

        Schema::table('paket_item', function (Blueprint $table) {
            $table->renameColumn('paket_id_tmp_bigint', 'paket_id');
        });

        Schema::table('paket_item', function (Blueprint $table) {
            $table->unique(['paket_id', 'produk_id']);
            $table->foreign('paket_id')->references('id')->on('paket')->cascadeOnDelete();
        });

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE paket_item MODIFY paket_id BIGINT UNSIGNED NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE paket_item ALTER COLUMN paket_id TYPE BIGINT USING paket_id::bigint');
            DB::statement('ALTER TABLE paket_item ALTER COLUMN paket_id SET NOT NULL');
        }
    }
};
