<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('jabatan_ko_tracking_permissions');
        Schema::dropIfExists('karyawan_tracking_divisi_access');
    }

    public function down(): void
    {
        // intentionally empty: tabel lama tidak dipulihkan lagi.
    }
};
