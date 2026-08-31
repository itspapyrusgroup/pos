<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ko_tracking_item_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_penjualan_item_id')->constrained('pesanan_penjualan_item')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('produk')->cascadeOnDelete();
            $table->boolean('is_checked')->default(false);
            $table->timestamp('checked_at')->nullable();
            $table->foreignId('checked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['pesanan_penjualan_item_id', 'produk_id'], 'uniq_ko_item_produk_check');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ko_tracking_item_checks');
    }
};
