<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('karyawan_tracking_divisi_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained('karyawan')->cascadeOnDelete();
            $table->foreignId('divisi_id')->constrained('divisi')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['karyawan_id', 'divisi_id'], 'uniq_karyawan_tracking_divisi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karyawan_tracking_divisi_access');
    }
};
