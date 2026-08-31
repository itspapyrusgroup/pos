<?php

namespace Database\Seeders;

use App\Models\MetodePembayaran;
use Illuminate\Database\Seeder;

class MetodePembayaranSeeder extends Seeder
{
    public function run(): void
    {
        $metode = [
            ['kode' => 'CASH', 'nama' => 'Cash', 'status' => true],
            ['kode' => 'QRIS', 'nama' => 'QRIS', 'status' => true],
            ['kode' => 'DEBIT', 'nama' => 'Debit', 'status' => true],
            ['kode' => 'KREDIT', 'nama' => 'Kredit', 'status' => true],
            ['kode' => 'TRANSFER', 'nama' => 'Transfer', 'status' => true],
        ];

        foreach ($metode as $item) {
            MetodePembayaran::updateOrCreate(['kode' => $item['kode']], $item);
        }
    }
}
