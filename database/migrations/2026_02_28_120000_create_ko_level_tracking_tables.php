<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ko_tracking_ko_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_penjualan_id')->constrained('pesanan_penjualan')->cascadeOnDelete();
            $table->string('step_kode', 40);
            $table->boolean('is_checked')->default(false);
            $table->timestamp('checked_at')->nullable();
            $table->foreignId('checked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['pesanan_penjualan_id', 'step_kode'], 'uniq_ko_tracking_ko_step');
        });

        Schema::create('jabatan_ko_tracking_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jabatan_id')->constrained('jabatan')->cascadeOnDelete();
            $table->string('step_kode', 40);
            $table->boolean('can_update')->default(true);
            $table->timestamps();

            $table->unique(['jabatan_id', 'step_kode'], 'uniq_jabatan_ko_tracking_step');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jabatan_ko_tracking_permissions');
        Schema::dropIfExists('ko_tracking_ko_checks');
    }
};
