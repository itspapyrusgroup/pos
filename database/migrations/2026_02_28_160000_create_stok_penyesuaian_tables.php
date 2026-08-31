<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stok_penyesuaian', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_penyesuaian');
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->text('catatan')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['cabang_id', 'tanggal_penyesuaian']);
        });

        Schema::create('stok_penyesuaian_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stok_penyesuaian_id')->constrained('stok_penyesuaian')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('produk')->cascadeOnDelete();
            $table->decimal('stok_sebelum', 18, 2)->default(0);
            $table->decimal('stok_setelah', 18, 2)->default(0);
            $table->decimal('qty_selisih', 18, 2)->default(0);
            $table->timestamps();
            $table->unique(['stok_penyesuaian_id', 'produk_id'], 'stok_penyesuaian_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_penyesuaian_item');
        Schema::dropIfExists('stok_penyesuaian');
    }
};
