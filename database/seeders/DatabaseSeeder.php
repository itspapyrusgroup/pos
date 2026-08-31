<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            RbacSeeder::class,
            PerusahaanSeeder::class,
            CabangSeeder::class,
            DivisiSeeder::class,
            KategoriProdukSeeder::class,
            SatuanSeeder::class,
            ProdukSeeder::class,
            PemasokSeeder::class,
            MetodePembayaranSeeder::class,
            SalesModeSeeder::class,
            KategoriPaketSeeder::class,
            KategoriAddonSeeder::class,
            TemplateHargaSeeder::class,
        ]);


        User::updateOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'username' => 'testuser', 'password' => bcrypt('password')]
        );
    }
}
