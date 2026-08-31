<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('divisi', function (Blueprint $table) {
            $table->dropUnique(['kode']);
            $table->dropColumn('kode');
        });
    }

    public function down(): void
    {
        Schema::table('divisi', function (Blueprint $table) {
            $table->string('kode', 20)->nullable()->after('id');
            $table->unique('kode');
        });
    }
};

