<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voucher_promosi', function (Blueprint $table) {
            $table->boolean('aktif_24_jam')->default(true)->after('aktif_sampai');
            $table->time('jam_mulai')->nullable()->after('aktif_24_jam');
            $table->time('jam_sampai')->nullable()->after('jam_mulai');
        });

        Schema::table('diskon_otomatis', function (Blueprint $table) {
            $table->boolean('aktif_24_jam')->default(true)->after('aktif_sampai');
            $table->time('jam_mulai')->nullable()->after('aktif_24_jam');
            $table->time('jam_sampai')->nullable()->after('jam_mulai');
        });

        Schema::create('voucher_promosi_cabang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_promosi_id')->constrained('voucher_promosi')->cascadeOnDelete();
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['voucher_promosi_id', 'cabang_id'], 'uniq_voucher_promosi_cabang');
        });

        Schema::create('diskon_otomatis_cabang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diskon_otomatis_id')->constrained('diskon_otomatis')->cascadeOnDelete();
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['diskon_otomatis_id', 'cabang_id'], 'uniq_diskon_otomatis_cabang');
        });

        $now = now();

        $voucherRows = DB::table('voucher_promosi')
            ->whereNotNull('cabang_id')
            ->select(['id', 'cabang_id'])
            ->get();

        foreach ($voucherRows as $row) {
            DB::table('voucher_promosi_cabang')->insertOrIgnore([
                'voucher_promosi_id' => $row->id,
                'cabang_id' => $row->cabang_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $diskonRows = DB::table('diskon_otomatis')
            ->whereNotNull('cabang_id')
            ->select(['id', 'cabang_id'])
            ->get();

        foreach ($diskonRows as $row) {
            DB::table('diskon_otomatis_cabang')->insertOrIgnore([
                'diskon_otomatis_id' => $row->id,
                'cabang_id' => $row->cabang_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('diskon_otomatis_cabang');
        Schema::dropIfExists('voucher_promosi_cabang');

        Schema::table('diskon_otomatis', function (Blueprint $table) {
            $table->dropColumn(['aktif_24_jam', 'jam_mulai', 'jam_sampai']);
        });

        Schema::table('voucher_promosi', function (Blueprint $table) {
            $table->dropColumn(['aktif_24_jam', 'jam_mulai', 'jam_sampai']);
        });
    }
};

