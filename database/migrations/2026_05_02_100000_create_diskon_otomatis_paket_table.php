<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diskon_otomatis_paket', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diskon_otomatis_id')->constrained('diskon_otomatis')->cascadeOnDelete();
            $table->foreignId('paket_id')->constrained('paket')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['diskon_otomatis_id', 'paket_id'], 'uniq_diskon_otomatis_paket');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diskon_otomatis_paket');
    }
};
