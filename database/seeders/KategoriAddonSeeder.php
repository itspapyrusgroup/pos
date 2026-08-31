<?php

namespace Database\Seeders;

use App\Models\KategoriAddon;
use Illuminate\Database\Seeder;

class KategoriAddonSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Retouch', 'Print', 'Frame'] as $nama) {
            KategoriAddon::query()->updateOrCreate(['nama' => $nama], [
                'nama' => $nama,
                'status' => true,
            ]);
        }
    }
}
