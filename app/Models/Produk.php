<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    use HasFactory;

    protected $table = 'produk';

    protected $fillable = [
        'kode',
        'nama',
        'kategori_produk_kode',
        'satuan_id',
        'track_stok',
        'harga_default',
        'status',
    ];

    protected $casts = [
        'track_stok' => 'boolean',
        'status' => 'boolean',
    ];

    public function kategoriProduk()
    {
        return $this->belongsTo(KategoriProduk::class, 'kategori_produk_kode', 'kode');
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }
}
