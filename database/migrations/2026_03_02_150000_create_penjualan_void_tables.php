<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('penjualan_void_otps', function (Blueprint $table) {
            $table->id();
            $table->string('kode_otp', 10)->unique();
            $table->foreignId('pesanan_penjualan_id')->constrained('pesanan_penjualan')->cascadeOnDelete();
            $table->enum('tipe_void', ['FULL', 'PARTIAL']);
            $table->enum('tipe_transaksi', ['CURRENT_DAY', 'BACKDATE']);
            $table->json('item_payload')->nullable();
            $table->timestamp('expired_at');
            $table->timestamp('used_at')->nullable();
            $table->foreignId('generated_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('used_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['pesanan_penjualan_id', 'expired_at']);
        });

        Schema::create('penjualan_void_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_penjualan_id')->constrained('pesanan_penjualan')->cascadeOnDelete();
            $table->foreignId('kantong_order_id')->nullable()->constrained('kantong_order')->nullOnDelete();
            $table->foreignId('otp_id')->nullable()->constrained('penjualan_void_otps')->nullOnDelete();
            $table->enum('tipe_void', ['FULL', 'PARTIAL']);
            $table->enum('tipe_transaksi', ['CURRENT_DAY', 'BACKDATE']);
            $table->text('alasan');
            $table->decimal('nominal_void', 18, 2)->default(0);
            $table->date('void_effective_date');
            $table->timestamp('voided_at');
            $table->foreignId('voided_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->json('item_payload')->nullable();
            $table->timestamps();

            $table->index('void_effective_date');
            $table->index(['pesanan_penjualan_id', 'void_effective_date'], 'idx_void_logs_order_effective');
        });

        Schema::table('pesanan_penjualan', function (Blueprint $table) {
            $table->timestamp('voided_at')->nullable()->after('catatan');
        });

        Schema::table('pesanan_penjualan_item', function (Blueprint $table) {
            $table->boolean('is_void')->default(false)->after('subtotal');
            $table->timestamp('voided_at')->nullable()->after('is_void');
            $table->foreignId('void_log_id')->nullable()->after('voided_at')->constrained('penjualan_void_logs')->nullOnDelete();
            $table->index(['pesanan_penjualan_id', 'is_void'], 'idx_pp_item_order_is_void');
        });
    }

    public function down(): void
    {
        Schema::table('pesanan_penjualan_item', function (Blueprint $table) {
            $table->dropForeign(['void_log_id']);
            $table->dropIndex('idx_pp_item_order_is_void');
            $table->dropColumn(['is_void', 'voided_at', 'void_log_id']);
        });

        Schema::table('pesanan_penjualan', function (Blueprint $table) {
            $table->dropColumn('voided_at');
        });

        Schema::dropIfExists('penjualan_void_logs');
        Schema::dropIfExists('penjualan_void_otps');
    }
};
