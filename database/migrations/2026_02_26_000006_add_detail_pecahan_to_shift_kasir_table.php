<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shift_kasir', function (Blueprint $table) {
            $table->json('detail_pecahan')->nullable()->after('selisih');
        });
    }

    public function down(): void
    {
        Schema::table('shift_kasir', function (Blueprint $table) {
            $table->dropColumn('detail_pecahan');
        });
    }
};
