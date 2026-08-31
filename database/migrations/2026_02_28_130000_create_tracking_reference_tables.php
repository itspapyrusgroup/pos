<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tracking_references', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 40)->unique();
            $table->string('nama', 120);
            $table->enum('tipe', ['ITEM', 'KO'])->default('ITEM');
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::table('kategori_produk', function (Blueprint $table) {
            $table->foreignId('tracking_reference_id')
                ->nullable()
                ->after('id_divisi')
                ->constrained('tracking_references')
                ->nullOnDelete();
        });

        Schema::create('jabatan_tracking_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jabatan_id')->constrained('jabatan')->cascadeOnDelete();
            $table->foreignId('tracking_reference_id')->constrained('tracking_references')->cascadeOnDelete();
            $table->boolean('can_update')->default(true);
            $table->timestamps();

            $table->unique(['jabatan_id', 'tracking_reference_id'], 'uniq_jabatan_tracking_reference');
        });

        $now = now();
        DB::table('tracking_references')->insert([
            ['kode' => 'CS_1', 'nama' => 'CS 1', 'tipe' => 'ITEM', 'urutan' => 10, 'status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['kode' => 'CS_2', 'nama' => 'CS 2', 'tipe' => 'ITEM', 'urutan' => 20, 'status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['kode' => 'FOTOGRAFER', 'nama' => 'Fotografer', 'tipe' => 'ITEM', 'urutan' => 30, 'status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['kode' => 'SPV_FRONT', 'nama' => 'SPV Front', 'tipe' => 'ITEM', 'urutan' => 40, 'status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['kode' => 'SPV_PRODUKSI', 'nama' => 'SPV Produksi', 'tipe' => 'ITEM', 'urutan' => 50, 'status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['kode' => 'SPV_DG', 'nama' => 'SPV Desain Grafis', 'tipe' => 'ITEM', 'urutan' => 60, 'status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['kode' => 'STAFF_DG', 'nama' => 'Staff Desain Grafis', 'tipe' => 'ITEM', 'urutan' => 70, 'status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['kode' => 'STAFF_PRODUKSI_CETAK', 'nama' => 'Staff Produksi Cetak', 'tipe' => 'ITEM', 'urutan' => 80, 'status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['kode' => 'STAFF_PRODUKSI_FRAME', 'nama' => 'Staff Produksi Frame', 'tipe' => 'ITEM', 'urutan' => 90, 'status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['kode' => 'QC_PAKET', 'nama' => 'QC Paket', 'tipe' => 'KO', 'urutan' => 10, 'status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['kode' => 'KIRIM_FILE', 'nama' => 'Kirim File', 'tipe' => 'KO', 'urutan' => 20, 'status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['kode' => 'PENGAMBILAN', 'nama' => 'Pengambilan', 'tipe' => 'KO', 'urutan' => 30, 'status' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        if (Schema::hasTable('jabatan_ko_tracking_permissions')) {
            $kodeToId = DB::table('tracking_references')
                ->pluck('id', 'kode')
                ->all();

            $legacyRows = DB::table('jabatan_ko_tracking_permissions')
                ->where('can_update', true)
                ->get(['jabatan_id', 'step_kode']);

            foreach ($legacyRows as $legacyRow) {
                $kode = strtoupper((string) $legacyRow->step_kode);
                $trackingId = (int) ($kodeToId[$kode] ?? 0);
                if ($trackingId <= 0) {
                    continue;
                }

                DB::table('jabatan_tracking_references')->updateOrInsert(
                    [
                        'jabatan_id' => (int) $legacyRow->jabatan_id,
                        'tracking_reference_id' => $trackingId,
                    ],
                    [
                        'can_update' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('jabatan_tracking_references');

        Schema::table('kategori_produk', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tracking_reference_id');
        });

        Schema::dropIfExists('tracking_references');
    }
};
