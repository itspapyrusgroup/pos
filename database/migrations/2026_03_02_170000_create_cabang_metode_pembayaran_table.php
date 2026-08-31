<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cabang_metode_pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->foreignId('metode_pembayaran_id')->constrained('metode_pembayaran')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['cabang_id', 'metode_pembayaran_id'], 'uniq_cabang_metode_pembayaran');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cabang_metode_pembayaran');
    }
};
