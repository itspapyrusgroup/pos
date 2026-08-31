<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE penjualan_void_otps MODIFY COLUMN tipe_void ENUM('FULL', 'PARTIAL', 'REMOVE', 'CHANGE_METHOD') NOT NULL");

        Schema::create('penjualan_payment_method_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_penjualan_id')->constrained('pesanan_penjualan')->cascadeOnDelete();
            $table->foreignId('pembayaran_penjualan_id')->constrained('pembayaran_penjualan')->cascadeOnDelete();
            $table->foreignId('otp_id')->nullable()->constrained('penjualan_void_otps')->nullOnDelete();
            $table->foreignId('from_metode_pembayaran_id')->constrained('metode_pembayaran')->cascadeOnDelete();
            $table->foreignId('to_metode_pembayaran_id')->constrained('metode_pembayaran')->cascadeOnDelete();
            $table->decimal('nominal', 18, 2);
            $table->text('alasan');
            $table->timestamp('corrected_at');
            $table->foreignId('corrected_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('authorized_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['pesanan_penjualan_id', 'corrected_at'], 'ppml_order_corrected_idx');
            $table->index(['pembayaran_penjualan_id', 'corrected_at'], 'ppml_payment_corrected_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjualan_payment_method_logs');

        DB::statement("ALTER TABLE penjualan_void_otps MODIFY COLUMN tipe_void ENUM('FULL', 'PARTIAL', 'REMOVE') NOT NULL");
    }
};
