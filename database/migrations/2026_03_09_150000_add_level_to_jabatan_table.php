<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('jabatan')) {
            return;
        }

        Schema::table('jabatan', function (Blueprint $table) {
            if (!Schema::hasColumn('jabatan', 'level')) {
                $table->string('level', 20)->default('STAFF')->after('nama');
            }
        });

        DB::table('jabatan')
            ->whereNull('level')
            ->orWhere('level', '')
            ->update(['level' => 'STAFF']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('jabatan') || !Schema::hasColumn('jabatan', 'level')) {
            return;
        }

        Schema::table('jabatan', function (Blueprint $table) {
            $table->dropColumn('level');
        });
    }
};
