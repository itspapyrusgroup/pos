<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('paket') || Schema::hasColumn('paket', 'harga_default')) {
            return;
        }

        Schema::table('paket', function (Blueprint $table) {
            $table->decimal('harga_default', 18, 2)->default(0)->after('nama');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('paket') || !Schema::hasColumn('paket', 'harga_default')) {
            return;
        }

        Schema::table('paket', function (Blueprint $table) {
            $table->dropColumn('harga_default');
        });
    }
};

