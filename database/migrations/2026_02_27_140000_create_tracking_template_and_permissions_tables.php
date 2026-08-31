<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tracking_templates', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 40)->unique();
            $table->string('nama', 120);
            $table->text('deskripsi')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('tracking_template_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tracking_template_id')->constrained('tracking_templates')->cascadeOnDelete();
            $table->string('step_kode', 50);
            $table->string('step_nama', 160);
            $table->enum('scope', ['KO', 'ITEM'])->default('ITEM');
            $table->unsignedInteger('urutan')->default(1);
            $table->boolean('is_wajib')->default(true);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['tracking_template_id', 'step_kode']);
        });

        Schema::create('jabatan_tracking_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jabatan_id')->constrained('jabatan')->cascadeOnDelete();
            $table->foreignId('tracking_template_step_id')->constrained('tracking_template_steps')->cascadeOnDelete();
            $table->boolean('can_view')->default(true);
            $table->boolean('can_update')->default(false);
            $table->timestamps();

            $table->unique(['jabatan_id', 'tracking_template_step_id'], 'uniq_jabatan_tracking_step');
        });

        Schema::table('kategori_produk', function (Blueprint $table) {
            $table->foreignId('tracking_template_id')->nullable()->after('tipe')->constrained('tracking_templates')->nullOnDelete();
        });

        $now = now();
        $templateId = DB::table('tracking_templates')->insertGetId([
            'kode' => 'DEFAULT_FOTO',
            'nama' => 'Default Foto End-to-End',
            'deskripsi' => 'Alur standar dari sesi foto sampai file/cetakan diserahkan ke customer.',
            'status' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $steps = [
            ['step_kode' => 'PHOTO_SESSION', 'step_nama' => 'Sesi Foto Selesai', 'scope' => 'ITEM', 'urutan' => 10],
            ['step_kode' => 'PHOTO_SELECTION_CS', 'step_nama' => 'Pemilihan Foto oleh CS', 'scope' => 'KO', 'urutan' => 20],
            ['step_kode' => 'ADDON_OFFER_CS', 'step_nama' => 'Penawaran Add-on oleh CS', 'scope' => 'KO', 'urutan' => 30],
            ['step_kode' => 'ADDON_PAYMENT', 'step_nama' => 'Pembayaran Add-on di Kasir', 'scope' => 'KO', 'urutan' => 40],
            ['step_kode' => 'DESIGN_EDIT', 'step_nama' => 'Proses Edit/Retouch', 'scope' => 'ITEM', 'urutan' => 50],
            ['step_kode' => 'EDIT_ACC_CS', 'step_nama' => 'ACC Hasil Edit oleh CS', 'scope' => 'KO', 'urutan' => 60],
            ['step_kode' => 'PRINT_PRODUCTION', 'step_nama' => 'Proses Cetak', 'scope' => 'ITEM', 'urutan' => 70],
            ['step_kode' => 'FRAME_PRODUCTION', 'step_nama' => 'Pasang Frame', 'scope' => 'ITEM', 'urutan' => 80],
            ['step_kode' => 'QC_PRODUCTION', 'step_nama' => 'QC Produksi', 'scope' => 'KO', 'urutan' => 90],
            ['step_kode' => 'PICKUP_DONE', 'step_nama' => 'Cetakan Sudah Diambil', 'scope' => 'KO', 'urutan' => 100],
            ['step_kode' => 'FILE_DELIVERED', 'step_nama' => 'File Sudah Dikirim', 'scope' => 'KO', 'urutan' => 110],
        ];

        foreach ($steps as $step) {
            DB::table('tracking_template_steps')->insert([
                'tracking_template_id' => $templateId,
                'step_kode' => $step['step_kode'],
                'step_nama' => $step['step_nama'],
                'scope' => $step['scope'],
                'urutan' => $step['urutan'],
                'is_wajib' => true,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('kategori_produk')->whereNull('tracking_template_id')->update([
            'tracking_template_id' => $templateId,
        ]);
    }

    public function down(): void
    {
        Schema::table('kategori_produk', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tracking_template_id');
        });

        Schema::dropIfExists('jabatan_tracking_permissions');
        Schema::dropIfExists('tracking_template_steps');
        Schema::dropIfExists('tracking_templates');
    }
};

