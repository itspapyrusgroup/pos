<?php

namespace Database\Seeders;

use App\Models\TemplateHarga;
use Illuminate\Database\Seeder;

class TemplateHargaSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['kode' => 'TH-0001', 'nama' => 'Template Toko Offline', 'keterangan' => 'Harga normal outlet', 'status' => true],
            ['kode' => 'TH-0002', 'nama' => 'Template Tokopedia', 'keterangan' => 'Harga channel Tokopedia', 'status' => true],
            ['kode' => 'TH-0003', 'nama' => 'Template Shopee', 'keterangan' => 'Harga channel Shopee', 'status' => true],
        ];

        foreach ($data as $item) {
            TemplateHarga::query()->updateOrCreate(['kode' => $item['kode']], $item);
        }
    }
}
