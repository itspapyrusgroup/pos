<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_mode', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 100);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('kategori_paket', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('kategori_addon', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('bom', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 150);
            $table->enum('tipe', ['PAKET', 'ADDON']);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('bom_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained('bom')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('produk')->cascadeOnDelete();
            $table->decimal('qty', 14, 2);
            $table->timestamps();
        });

        Schema::table('paket', function (Blueprint $table) {
            $table->foreignId('kategori_paket_id')->nullable()->after('nama')->constrained('kategori_paket')->nullOnDelete();
            $table->foreignId('bom_id')->nullable()->after('deskripsi')->constrained('bom')->nullOnDelete();
        });

        Schema::create('addon', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 150);
            $table->foreignId('kategori_addon_id')->nullable()->constrained('kategori_addon')->nullOnDelete();
            $table->foreignId('bom_id')->nullable()->constrained('bom')->nullOnDelete();
            $table->text('deskripsi')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('template_harga', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 150);
            $table->text('keterangan')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('template_harga_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_harga_id')->constrained('template_harga')->cascadeOnDelete();
            $table->enum('jenis_item', ['PRODUK', 'PAKET', 'ADDON']);
            $table->unsignedBigInteger('item_id');
            $table->decimal('harga', 18, 2)->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->unique(['template_harga_id', 'jenis_item', 'item_id'], 'uniq_template_item');
        });

        Schema::create('cabang_sales_mode', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->foreignId('sales_mode_id')->constrained('sales_mode')->cascadeOnDelete();
            $table->foreignId('template_harga_id')->nullable()->constrained('template_harga')->nullOnDelete();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->unique(['cabang_id', 'sales_mode_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cabang_sales_mode');
        Schema::dropIfExists('template_harga_item');
        Schema::dropIfExists('template_harga');
        Schema::dropIfExists('addon');

        Schema::table('paket', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kategori_paket_id');
            $table->dropConstrainedForeignId('bom_id');
        });

        Schema::dropIfExists('bom_item');
        Schema::dropIfExists('bom');
        Schema::dropIfExists('kategori_addon');
        Schema::dropIfExists('kategori_paket');
        Schema::dropIfExists('sales_mode');
    }
};
