<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kategori_produk', function (Blueprint $table) {
            $table->string('kode', 30)->nullable()->after('id');
            $table->boolean('status')->default(true)->after('nama');
            $table->unique('kode');
        });

        $kategori = DB::table('kategori_produk')->orderBy('id')->get();
        foreach ($kategori as $item) {
            DB::table('kategori_produk')
                ->where('id', $item->id)
                ->update([
                    'kode' => $item->kode ?: 'GLN-' . str_pad((string) $item->id, 4, '0', STR_PAD_LEFT),
                    'status' => $item->status ?? true,
                ]);
        }

        Schema::table('produk', function (Blueprint $table) {
            $table->foreignId('satuan_id')->nullable()->after('kategori_produk_id')->constrained('satuan')->nullOnDelete();
        });

        $satuanByName = DB::table('satuan')->get()->mapWithKeys(function ($item) {
            return [strtolower(trim($item->nama)) => $item->id];
        });

        DB::table('produk')->orderBy('id')->get()->each(function ($produk) use ($satuanByName) {
            $namaSatuan = strtolower(trim((string) $produk->satuan));
            $satuanId = $satuanByName[$namaSatuan] ?? null;

            if ($satuanId) {
                DB::table('produk')
                    ->where('id', $produk->id)
                    ->update(['satuan_id' => $satuanId]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->dropConstrainedForeignId('satuan_id');
        });

        Schema::table('kategori_produk', function (Blueprint $table) {
            $table->dropUnique(['kode']);
            $table->dropColumn(['kode', 'status']);
        });
    }
};
