<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('kategori_produk') && Schema::hasColumn('kategori_produk', 'tracking_template_id')) {
            Schema::table('kategori_produk', function (Blueprint $table) {
                $table->dropConstrainedForeignId('tracking_template_id');
            });
        }

        Schema::dropIfExists('ko_tracking_steps');
        Schema::dropIfExists('ko_tracking');
        Schema::dropIfExists('jabatan_tracking_permissions');
        Schema::dropIfExists('tracking_template_steps');
        Schema::dropIfExists('tracking_templates');
    }

    public function down(): void
    {
        if (Schema::hasTable('tracking_templates')) {
            return;
        }

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

        Schema::create('ko_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_penjualan_id')->unique()->constrained('pesanan_penjualan')->cascadeOnDelete();
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->string('status', 20)->default('OPEN');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ko_tracking_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ko_tracking_id')->constrained('ko_tracking')->cascadeOnDelete();
            $table->foreignId('tracking_template_step_id')->constrained('tracking_template_steps')->cascadeOnDelete();
            $table->foreignId('pesanan_penjualan_item_id')->nullable()->constrained('pesanan_penjualan_item')->nullOnDelete();
            $table->string('status', 20)->default('TODO');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->text('revision_notes')->nullable();
            $table->timestamps();
            $table->index(['ko_tracking_id', 'status']);
            $table->unique(['ko_tracking_id', 'tracking_template_step_id', 'pesanan_penjualan_item_id'], 'uniq_ko_step_item');
        });

        if (Schema::hasTable('kategori_produk') && !Schema::hasColumn('kategori_produk', 'tracking_template_id')) {
            Schema::table('kategori_produk', function (Blueprint $table) {
                $table->foreignId('tracking_template_id')->nullable()->after('tipe')->constrained('tracking_templates')->nullOnDelete();
            });
        }
    }
};
