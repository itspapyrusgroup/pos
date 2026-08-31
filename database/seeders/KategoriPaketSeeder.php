<?php

namespace Database\Seeders;

use App\Models\KategoriPaket;
use Illuminate\Database\Seeder;

class KategoriPaketSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Personal', 'Family', 'Prewedding'] as $nama) {
            KategoriPaket::query()->updateOrCreate(['nama' => $nama], [
                'nama' => $nama,
                'status' => true,
            ]);
        }
    }
}
