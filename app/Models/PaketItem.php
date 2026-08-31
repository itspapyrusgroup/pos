<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaketItem extends Model
{
    use HasFactory;

    protected $table = 'paket_item';

    protected $fillable = [
        'paket_id',
        'produk_id',
        'qty',
    ];

    public function paket()
    {
        return $this->belongsTo(Paket::class, 'paket_id', 'kode');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }
}
