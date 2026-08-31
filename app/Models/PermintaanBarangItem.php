<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermintaanBarangItem extends Model
{
    use HasFactory;

    protected $table = 'permintaan_barang_item';

    protected $fillable = [
        'permintaan_barang_id',
        'produk_id',
        'qty',
        'catatan',
    ];

    public function permintaanBarang()
    {
        return $this->belongsTo(PermintaanBarang::class, 'permintaan_barang_id');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }
}
