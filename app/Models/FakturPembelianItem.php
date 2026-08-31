<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FakturPembelianItem extends Model
{
    use HasFactory;

    protected $table = 'faktur_pembelian_item';

    protected $fillable = [
        'faktur_pembelian_id',
        'produk_id',
        'qty',
        'harga',
        'subtotal',
    ];

    public function fakturPembelian()
    {
        return $this->belongsTo(FakturPembelian::class, 'faktur_pembelian_id');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }
}
