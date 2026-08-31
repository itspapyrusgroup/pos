<?php

namespace Database\Seeders;

use App\Models\KategoriProduk;
use Illuminate\Database\Seeder;

class KategoriProdukSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = [
            ['kode' => '11', 'nama' => 'MINILAB', 'id_divisi' => 1, 'status' => true, 'tipe' => 'JASA'],
            ['kode' => '12', 'nama' => 'PLOTTER', 'id_divisi' => 1, 'status' => true, 'tipe' => 'JASA'],
            ['kode' => '13', 'nama' => 'PRODUKSI', 'id_divisi' => 1, 'status' => true, 'tipe' => 'JASA'],
            ['kode' => '21', 'nama' => 'JASA TOKO', 'id_divisi' => 2, 'status' => true, 'tipe' => 'JASA'],
            ['kode' => '10', 'nama' => 'LPS', 'id_divisi' => 2, 'status' => true, 'tipe' => 'JASA'],
            ['kode' => '23', 'nama' => 'ORDER JONAS', 'id_divisi' => 2, 'status' => true, 'tipe' => 'JASA'],
            ['kode' => '24', 'nama' => 'ORDER GUNARSA', 'id_divisi' => 2, 'status' => true, 'tipe' => 'JASA'],
            ['kode' => '25', 'nama' => 'ORDER GLASSWOOD', 'id_divisi' => 2, 'status' => true, 'tipe' => 'JASA'],
            ['kode' => '31', 'nama' => 'FT STUDIO NON-GRUP', 'id_divisi' => 3, 'status' => true, 'tipe' => 'JASA'],
            ['kode' => '32', 'nama' => 'TAMBAH ORANG', 'id_divisi' => 3, 'status' => true, 'tipe' => 'JASA'],
            ['kode' => '33', 'nama' => 'TAMBAH POSE', 'id_divisi' => 3, 'status' => true, 'tipe' => 'JASA'],
            ['kode' => '34', 'nama' => 'FT STUDIO GRUP', 'id_divisi' => 3, 'status' => true, 'tipe' => 'JASA'],
            ['kode' => '35', 'nama' => 'DESAIN GRAFIX', 'id_divisi' => 3, 'status' => true, 'tipe' => 'JASA'],
            ['kode' => '36', 'nama' => 'SEWA & KURSUS', 'id_divisi' => 3, 'status' => true, 'tipe' => 'JASA'],
            ['kode' => '41', 'nama' => 'VIDEOGRAFI', 'id_divisi' => 4, 'status' => true, 'tipe' => 'JASA'],
            ['kode' => '51', 'nama' => 'MARKETING PROMO', 'id_divisi' => 5, 'status' => true, 'tipe' => 'JASA'],
            ['kode' => '71', 'nama' => 'KAMERA', 'id_divisi' => 7, 'status' => false, 'tipe' => 'BARANG'],
            ['kode' => '72', 'nama' => 'STORAGE DATA', 'id_divisi' => 7, 'status' => false, 'tipe' => 'BARANG'],
            ['kode' => '73', 'nama' => 'BATERAI & CHARGER', 'id_divisi' => 7, 'status' => false, 'tipe' => 'BARANG'],
            ['kode' => '74', 'nama' => 'ALBUM', 'id_divisi' => 7, 'status' => false, 'tipe' => 'BARANG'],
            ['kode' => '75', 'nama' => 'BARANG PRODUKSI', 'id_divisi' => 7, 'status' => false, 'tipe' => 'BARANG'],
            ['kode' => '76', 'nama' => 'FRAME', 'id_divisi' => 7, 'status' => false, 'tipe' => 'BARANG'],
            ['kode' => '77', 'nama' => 'AKSESORIS', 'id_divisi' => 7, 'status' => false, 'tipe' => 'BARANG'],
            ['kode' => '26', 'nama' => 'ORDER BONGBOX', 'id_divisi' => 2, 'status' => true, 'tipe' => 'JASA'],
            ['kode' => '37', 'nama' => 'TAMBAH BACKGROUND', 'id_divisi' => 3, 'status' => true, 'tipe' => 'JASA'],
            ['kode' => '38', 'nama' => 'TAMBAH KOSTUM', 'id_divisi' => 3, 'status' => true, 'tipe' => 'JASA'],
            ['kode' => '27', 'nama' => 'ORDER GALERY FRAME', 'id_divisi' => 2, 'status' => true, 'tipe' => 'JASA'],
            ['kode' => '8', 'nama' => 'STOCK', 'id_divisi' => 8, 'status' => false, 'tipe' => 'BARANG'],
            ['kode' => '91', 'nama' => 'PAPER MINILAB', 'id_divisi' => 8, 'status' => false, 'tipe' => 'BARANG'],
            ['kode' => '92', 'nama' => 'CHEMICAL', 'id_divisi' => 8, 'status' => false, 'tipe' => 'BARANG'],
            ['kode' => '93', 'nama' => 'PAPER PLOTTER', 'id_divisi' => 8, 'status' => false, 'tipe' => 'BARANG'],
            ['kode' => '94', 'nama' => 'LAMINASI', 'id_divisi' => 8, 'status' => false, 'tipe' => 'BARANG'],
            ['kode' => '95', 'nama' => 'TINTA PLOTTER', 'id_divisi' => 8, 'status' => false, 'tipe' => 'BARANG'],
            ['kode' => '96', 'nama' => 'ATK', 'id_divisi' => 8, 'status' => false, 'tipe' => 'BARANG'],
            ['kode' => '97', 'nama' => 'ART', 'id_divisi' => 8, 'status' => false, 'tipe' => 'BARANG'],
            ['kode' => '98', 'nama' => 'KOSMETIK', 'id_divisi' => 8, 'status' => false, 'tipe' => 'BARANG'],
            ['kode' => '99', 'nama' => 'SUPPLIES', 'id_divisi' => 8, 'status' => false, 'tipe' => 'BARANG'],
            ['kode' => '39', 'nama' => 'MAKE UP', 'id_divisi' => 3, 'status' => true, 'tipe' => 'JASA'],
            ['kode' => '28', 'nama' => 'ORDER SARIKASO FRAME', 'id_divisi' => 2, 'status' => true, 'tipe' => 'JASA'],
            ['kode' => '29', 'nama' => 'ORDER ALBUM MAGZ', 'id_divisi' => 2, 'status' => true, 'tipe' => 'JASA'],
            ['kode' => '14', 'nama' => 'DEVELOP', 'id_divisi' => 1, 'status' => true, 'tipe' => 'JASA'],
            ['kode' => '15', 'nama' => 'MINI PRINTER', 'id_divisi' => 1, 'status' => false, 'tipe' => 'JASA'],
            ['kode' => '90', 'nama' => 'PAPER DEVELOP', 'id_divisi' => 8, 'status' => false, 'tipe' => 'BARANG'],
            ['kode' => '22', 'nama' => 'ORDER ANGKASA', 'id_divisi' => 2, 'status' => true, 'tipe' => 'JASA'],
            ['kode' => '16', 'nama' => 'EPSON', 'id_divisi' => 1, 'status' => false, 'tipe' => 'JASA'],
            ['kode' => '78', 'nama' => 'FOOD&DRINK', 'id_divisi' => 7, 'status' => false, 'tipe' => 'BARANG'],
            ['kode' => '100', 'nama' => 'PROFIL', 'id_divisi' => 9, 'status' => false, 'tipe' => 'BARANG'],
            ['kode' => '101', 'nama' => 'KACA', 'id_divisi' => 9, 'status' => false, 'tipe' => 'BARANG'],
            ['kode' => '102', 'nama' => 'MATTING', 'id_divisi' => 9, 'status' => false, 'tipe' => 'BARANG'],
            ['kode' => '103', 'nama' => 'RAM', 'id_divisi' => 9, 'status' => false, 'tipe' => 'BARANG'],
            ['kode' => '104', 'nama' => 'LINEN', 'id_divisi' => 9, 'status' => false, 'tipe' => 'BARANG'],
            ['kode' => '-', 'nama' => '-', 'id_divisi' => 1, 'status' => false, 'tipe' => 'JASA'],
            ['kode' => '79', 'nama' => 'FRAME X', 'id_divisi' => 6, 'status' => false, 'tipe' => 'BARANG'],
        ];

        foreach ($kategori as $item) {
            KategoriProduk::query()->updateOrCreate(
                ['kode' => $item['kode']],
                [
                    'kode' => $item['kode'],
                    'nama' => $item['nama'],
                    'id_divisi' => $item['id_divisi'],
                    'status' => (bool) $item['status'],
                    'tipe' => strtoupper((string) $item['tipe']),
                ]
            );
        }
    }
}

