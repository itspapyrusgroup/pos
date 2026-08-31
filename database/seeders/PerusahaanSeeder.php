<?php

namespace Database\Seeders;

use App\Models\Perusahaan;
use Illuminate\Database\Seeder;

class PerusahaanSeeder extends Seeder
{
    public function run()
    {

        $perusahaan = [
            [
                'kode' => 'C001',
                'nama' => 'CV Cahaya Kasih Utama',
                'npwp' => '36.0812.12515',
                'alamat' => 'Jl Bengawan No 29, Bandung',
                'no_hp' => '08111111111',
                'status' => true,
            ],
            [
                'kode' => 'C002',
                'nama' => 'CV Cahaya Kasih Hati',
                'npwp' => '36.0812.12515',
                'alamat' => 'Jl CCM No 29, Bogor',
                'no_hp' => '08111111112',
                'status' => false,
            ],
        ];

        foreach ($perusahaan as $data) {
            Perusahaan::updateOrCreate(
                ['kode' => $data['kode']],
                $data
            );
        }
    }
}
