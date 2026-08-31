<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penjualan_request_logs', function (Blueprint $table) {
            $table->id();
            $table->string('client_request_id', 100)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cabang_id')->nullable()->constrained('cabang')->nullOnDelete();
            $table->foreignId('pesanan_penjualan_id')->nullable()->constrained('pesanan_penjualan')->nullOnDelete();
            $table->enum('status', ['PROCESSING', 'COMPLETED'])->default('PROCESSING');
            $table->string('mode', 20)->nullable();
            $table->string('message', 255)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjualan_request_logs');
    }
};
