<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cabang', function (Blueprint $table) {
            if (!Schema::hasColumn('cabang', 'warna_header')) {
                $table->string('warna_header', 30)->nullable()->after('struk_footer');
            }
        });
    }
/*  */
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cabang', function (Blueprint $table) {
            if (Schema::hasColumn('cabang', 'warna_header')) {
                $table->dropColumn('warna_header');
            }
        });
    }
};
