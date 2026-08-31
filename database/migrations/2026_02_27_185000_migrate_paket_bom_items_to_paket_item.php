<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('paket') || !Schema::hasTable('bom_item') || !Schema::hasTable('paket_item')) {
            return;
        }

        if (!Schema::hasColumn('paket', 'bom_id')) {
            return;
        }

        $pakets = DB::table('paket')
            ->whereNotNull('bom_id')
            ->get(['id', 'bom_id']);

        foreach ($pakets as $paket) {
            $items = DB::table('bom_item')
                ->where('bom_id', $paket->bom_id)
                ->get(['produk_id', 'qty']);

            foreach ($items as $item) {
                if (!$item->produk_id || (float) $item->qty <= 0) {
                    continue;
                }

                DB::table('paket_item')->updateOrInsert(
                    [
                        'paket_id' => $paket->id,
                        'produk_id' => $item->produk_id,
                    ],
                    [
                        'qty' => (float) $item->qty,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        // No-op: data paket_item yang sudah dipakai transaksi tidak aman untuk dihapus otomatis.
    }
};
