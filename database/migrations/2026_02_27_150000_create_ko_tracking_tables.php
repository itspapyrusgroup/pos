<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ko_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_penjualan_id')->constrained('pesanan_penjualan')->cascadeOnDelete();
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->enum('status', ['OPEN', 'DONE'])->default('OPEN');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique('pesanan_penjualan_id');
        });

        Schema::create('ko_tracking_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ko_tracking_id')->constrained('ko_tracking')->cascadeOnDelete();
            $table->foreignId('tracking_template_step_id')->constrained('tracking_template_steps')->cascadeOnDelete();
            $table->foreignId('pesanan_penjualan_item_id')->nullable()->constrained('pesanan_penjualan_item')->nullOnDelete();
            $table->enum('status', ['TODO', 'IN_PROGRESS', 'DONE', 'REVISI'])->default('TODO');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->text('revision_notes')->nullable();
            $table->timestamps();

            $table->index(['ko_tracking_id', 'status']);
            $table->index(['tracking_template_step_id', 'status']);
            $table->unique(['ko_tracking_id', 'tracking_template_step_id', 'pesanan_penjualan_item_id'], 'uniq_ko_step_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ko_tracking_steps');
        Schema::dropIfExists('ko_tracking');
    }
};

