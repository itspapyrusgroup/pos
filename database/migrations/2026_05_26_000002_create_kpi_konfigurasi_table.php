<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_konfigurasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabang_id')->constrained('cabang')->onDelete('cascade');
            $table->string('nama_konfigurasi', 100)->default('Default');
            $table->decimal('persen_cs_kasir_spv', 5, 2)->default(60.00); // 60%
            $table->decimal('persen_fotografer', 5, 2)->default(40.00); // 40%
            $table->boolean('include_kasir')->default(true);
            $table->boolean('include_spv')->default(true);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['cabang_id', 'nama_konfigurasi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_konfigurasi');
    }
};