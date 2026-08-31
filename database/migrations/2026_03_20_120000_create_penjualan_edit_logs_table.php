<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('penjualan_edit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_penjualan_id')->constrained('pesanan_penjualan')->cascadeOnDelete();
            $table->foreignId('kantong_order_id')->nullable()->constrained('kantong_order')->nullOnDelete();
            $table->foreignId('edited_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('alasan');
            $table->json('before_snapshot');
            $table->json('after_snapshot');
            $table->timestamp('edited_at');
            $table->timestamps();

            $table->index(['pesanan_penjualan_id', 'edited_at'], 'penjualan_edit_logs_order_edited_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjualan_edit_logs');
    }
};
