<?php

namespace Database\Seeders;

use App\Models\Satuan;
use Illuminate\Database\Seeder;

class SatuanSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['kode' => 'SAT-PCS', 'nama' => 'Pcs', 'keterangan' => 'Satuan per buah', 'status' => true],
            ['kode' => 'SAT-LBR', 'nama' => 'Lembar', 'keterangan' => 'Satuan per lembar', 'status' => true],
            ['kode' => 'SAT-PKT', 'nama' => 'Paket', 'keterangan' => 'Satuan per paket', 'status' => true],
            ['kode' => 'SAT-RLL', 'nama' => 'Roll', 'keterangan' => 'Satuan per roll', 'status' => true],
        ];

        foreach ($items as $item) {
            Satuan::query()->updateOrCreate(
                ['kode' => $item['kode']],
                $item
            );
        }
    }
}
