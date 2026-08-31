<?php

namespace Database\Seeders;

use App\Models\Cabang;
use App\Models\Perusahaan;
use Illuminate\Database\Seeder;

class CabangSeeder extends Seeder
{
    public function run()
    {
        $perusahaan = Perusahaan::first();

        $cabang = [
            [
                'kode' => 'BR001',
                'perusahaan_id' => $perusahaan->id,
                'nama' => 'Cabang Bandung',
                'alamat' => 'Jl Bengawan No 29, Bandung',
                'no_hp' => '08111111111',
                'status' => true,
            ],
            [
                'kode' => 'BR002',
                'perusahaan_id' => $perusahaan->id,
                'nama' => 'Cabang Bogor',
                'alamat' => 'Jl CCM No 29, Bogor',
                'no_hp' => '08111111112',
                'status' => false,
            ],
        ];

        foreach ($cabang as $data) {
            Cabang::updateOrCreate(
                ['kode' => $data['kode']],
                $data
            );
        }
    }
}
