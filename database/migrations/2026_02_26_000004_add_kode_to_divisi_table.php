<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('divisi', function (Blueprint $table) {
            $table->string('kode', 20)->nullable()->after('id');
            $table->unique('kode');
        });

        $rows = DB::table('divisi')->select('id')->whereNull('kode')->orderBy('id')->get();
        foreach ($rows as $row) {
            DB::table('divisi')->where('id', $row->id)->update([
                'kode' => 'D' . str_pad((string) $row->id, 3, '0', STR_PAD_LEFT),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('divisi', function (Blueprint $table) {
            $table->dropUnique(['kode']);
            $table->dropColumn('kode');
        });
    }
};
