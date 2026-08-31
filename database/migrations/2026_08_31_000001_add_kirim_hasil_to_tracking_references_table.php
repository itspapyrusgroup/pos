<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();

        DB::table('tracking_references')->updateOrInsert(
            ['kode' => 'KIRIM_HASIL'],
            [
                'nama' => 'Kirim Hasil',
                'tipe' => 'KO',
                'urutan' => 25,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $kirimHasilId = (int) DB::table('tracking_references')
            ->where('kode', 'KIRIM_HASIL')
            ->value('id');

        $kirimFileId = (int) DB::table('tracking_references')
            ->where('kode', 'KIRIM_FILE')
            ->value('id');

        if ($kirimHasilId <= 0 || $kirimFileId <= 0) {
            return;
        }

        $jabatanIds = DB::table('jabatan_tracking_references')
            ->where('tracking_reference_id', $kirimFileId)
            ->pluck('jabatan_id');

        foreach ($jabatanIds as $jabatanId) {
            DB::table('jabatan_tracking_references')->updateOrInsert(
                [
                    'jabatan_id' => (int) $jabatanId,
                    'tracking_reference_id' => $kirimHasilId,
                ],
                [
                    'can_update' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        $kirimHasilId = (int) DB::table('tracking_references')
            ->where('kode', 'KIRIM_HASIL')
            ->value('id');

        if ($kirimHasilId > 0) {
            DB::table('jabatan_tracking_references')
                ->where('tracking_reference_id', $kirimHasilId)
                ->delete();

            DB::table('tracking_references')
                ->where('id', $kirimHasilId)
                ->delete();
        }
    }
};
