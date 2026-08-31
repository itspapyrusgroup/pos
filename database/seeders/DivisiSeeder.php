<?php

namespace Database\Seeders;

use App\Models\Divisi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DivisiSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $items = [
            ['id' => 1, 'nama' => 'CETAK', 'status' => true],
            ['id' => 10, 'nama' => 'CS', 'status' => true],
            ['id' => 2, 'nama' => 'TOKO (PRODUKSI)', 'status' => true],
            ['id' => 3, 'nama' => 'STUDIO', 'status' => true],
            ['id' => 4, 'nama' => 'EVENT', 'status' => true],
            ['id' => 5, 'nama' => 'OFFICE', 'status' => true],
            ['id' => 6, 'nama' => 'FRAME', 'status' => true],
            ['id' => 7, 'nama' => 'TOKO (BARANG)', 'status' => true],
            ['id' => 8, 'nama' => 'INVENTORY', 'status' => true],
            ['id' => 9, 'nama' => 'FFW', 'status' => true],
        ];

        Divisi::query()->upsert(
            array_map(function (array $item) use ($now) {
                return [
                    'id' => $item['id'],
                    'nama' => strtoupper((string) $item['nama']),
                    'status' => (bool) $item['status'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $items),
            ['id'],
            ['nama', 'status', 'updated_at']
        );
    }
}

