<?php

namespace Database\Seeders;

use App\Models\KategoriProduk;
use App\Models\Produk;
use App\Models\Satuan;
use Illuminate\Database\Seeder;

class ProdukSeeder extends Seeder
{
    public function run(): void
    {
        $frame = KategoriProduk::firstWhere('kode', '76');
        $minilab = KategoriProduk::firstWhere('kode', '11');
        $desain = KategoriProduk::firstWhere('kode', '35');
        $pcs = Satuan::firstWhere('nama', 'Pcs');
        $lembar = Satuan::firstWhere('nama', 'Lembar');

        $produk = [
            [
                'kode' => '76-1782',
                'nama' => 'FF 16RP 1022',
                'kategori_produk_kode' => $frame?->kode,
                'satuan_id' => $pcs?->id,
                'track_stok' => true,
                'harga_default' => 125000,

                'status' => true,
            ],
            [
                'kode' => '11-2064',
                'nama' => 'CTK MNLAB 8RP GLS GRUP',
                'kategori_produk_kode' => $minilab?->kode,
                'satuan_id' => $lembar?->id,
                'track_stok' => false,
                'harga_default' => 25000,

                'status' => true,
            ],
            [
                'kode' => '35-0021',
                'nama' => 'RETOUCH SLIM WAJAH (BAGIAN)',
                'kategori_produk_kode' => $desain?->kode,
                'satuan_id' => $pcs?->id,
                'track_stok' => false,
                'harga_default' => 50000,

                'status' => true,
            ],
        ];

        foreach ($produk as $item) {
            Produk::updateOrCreate(['kode' => $item['kode']], $item);
        }
    }
}
