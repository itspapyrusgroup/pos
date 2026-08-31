<?php

namespace Database\Seeders;

use App\Models\Pemasok;
use Illuminate\Database\Seeder;

class PemasokSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'kode' => 'SUP0001',
                'nama' => 'AGE HARDWARE',
                'kontak' => 'Default',
                'telepon' => null,
                'alamat' => 'AGE HARDWARE',
                'kategori' => 'Default',
                'credit_terms_hari' => 0,
                'status' => true,
            ],
            [
                'kode' => 'SUP0002',
                'nama' => 'AKAL',
                'kontak' => 'AKAL',
                'telepon' => '+08-960093000',
                'alamat' => 'Pearl Paralympics no 35, J.B.leipzig mask',
                'kategori' => 'Default',
                'credit_terms_hari' => 0,
                'status' => true,
            ],
        ];

        foreach ($data as $item) {
            Pemasok::updateOrCreate(['kode' => $item['kode']], $item);
        }
    }
}
