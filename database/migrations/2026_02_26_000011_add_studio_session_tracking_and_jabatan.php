<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('jabatan', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama', 100)->unique();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::table('karyawan', function (Blueprint $table) {
            $table->foreignId('jabatan_id')->nullable()->after('divisi_id')->constrained('jabatan')->nullOnDelete();
        });

        Schema::table('antrian_studio', function (Blueprint $table) {
            $table->timestamp('called_at')->nullable()->after('waktu_panggil');
            $table->timestamp('start_at')->nullable()->after('called_at');
            $table->timestamp('end_at')->nullable()->after('start_at');
            $table->foreignId('called_by_user_id')->nullable()->after('end_at')->constrained('users')->nullOnDelete();
            $table->foreignId('started_by_user_id')->nullable()->after('called_by_user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('ended_by_user_id')->nullable()->after('started_by_user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('photographer_user_id')->nullable()->after('ended_by_user_id')->constrained('users')->nullOnDelete();
        });

        Schema::create('antrian_studio_tugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('antrian_studio_id')->constrained('antrian_studio')->cascadeOnDelete();
            $table->foreignId('pesanan_penjualan_item_id')->nullable()->constrained('pesanan_penjualan_item')->nullOnDelete();
            $table->foreignId('produk_id')->nullable()->constrained('produk')->nullOnDelete();
            $table->string('nama_tugas', 180);
            $table->decimal('qty', 14, 2)->default(1);
            $table->boolean('is_selesai')->default(false);
            $table->timestamp('selesai_at')->nullable();
            $table->foreignId('selesai_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('antrian_studio_tugas');

        Schema::table('antrian_studio', function (Blueprint $table) {
            $table->dropConstrainedForeignId('photographer_user_id');
            $table->dropConstrainedForeignId('ended_by_user_id');
            $table->dropConstrainedForeignId('started_by_user_id');
            $table->dropConstrainedForeignId('called_by_user_id');
            $table->dropColumn(['called_at', 'start_at', 'end_at']);
        });

        Schema::table('karyawan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jabatan_id');
        });

        Schema::dropIfExists('jabatan');
    }
};
