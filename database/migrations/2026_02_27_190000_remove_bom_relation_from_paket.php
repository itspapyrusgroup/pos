<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('paket', 'bom_id')) {
            return;
        }

        Schema::table('paket', function (Blueprint $table) {
            try {
                $table->dropConstrainedForeignId('bom_id');
            } catch (\Throwable $e) {
                $table->dropColumn('bom_id');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('paket', 'bom_id')) {
            return;
        }

        Schema::table('paket', function (Blueprint $table) {
            $table->foreignId('bom_id')->nullable()->after('deskripsi')->constrained('bom')->nullOnDelete();
        });
    }
};
