<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cabang', function (Blueprint $table) {
            $table->boolean('tutup_kasir_email_enabled')
                ->default(false)
                ->after('allow_minus_stock');
            $table->json('tutup_kasir_email_recipients')
                ->nullable()
                ->after('tutup_kasir_email_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('cabang', function (Blueprint $table) {
            $table->dropColumn([
                'tutup_kasir_email_enabled',
                'tutup_kasir_email_recipients',
            ]);
        });
    }
};
