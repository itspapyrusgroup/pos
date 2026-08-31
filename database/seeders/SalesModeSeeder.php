<?php

namespace Database\Seeders;

use App\Models\SalesMode;
use Illuminate\Database\Seeder;

class SalesModeSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['kode' => 'SM-0001', 'nama' => 'Toko Offline', 'status' => true],
            ['kode' => 'SM-0002', 'nama' => 'Tokopedia', 'status' => true],
            ['kode' => 'SM-0003', 'nama' => 'Shopee', 'status' => true],
        ];

        foreach ($data as $item) {
            SalesMode::query()->updateOrCreate(['kode' => $item['kode']], $item);
        }
    }
}
