<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PesananPembelianItem extends Model
{
    use HasFactory;

    protected $table = 'pesanan_pembelian_item';

    protected $fillable = [
        'pesanan_pembelian_id',
        'produk_id',
        'qty',
        'harga',
        'subtotal',
        'catatan',
    ];

    public function pesananPembelian()
    {
        return $this->belongsTo(PesananPembelian::class, 'pesanan_pembelian_id');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function penerimaanItems()
    {
        return $this->hasMany(PenerimaanBarangItem::class, 'pesanan_pembelian_item_id');
    }
}
