<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        \Illuminate\Support\Facades\DB::table('permissions')->insert([
            'kode' => 'laporan.kpi.view',
            'modul' => 'laporan.kpi',
            'aksi' => 'report',
            'label' => 'Lihat Laporan KPI Omset',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('permissions')
            ->where('kode', 'laporan.kpi.view')
            ->delete();
    }
};