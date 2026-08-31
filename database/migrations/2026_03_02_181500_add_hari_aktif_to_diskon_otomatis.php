<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diskon_otomatis', function (Blueprint $table) {
            $table->json('hari_aktif')->nullable()->after('jam_sampai');
        });
    }

    public function down(): void
    {
        Schema::table('diskon_otomatis', function (Blueprint $table) {
            $table->dropColumn('hari_aktif');
        });
    }
};

