<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StokPenyesuaianItem extends Model
{
    use HasFactory;

    protected $table = 'stok_penyesuaian_item';

    protected $fillable = [
        'stok_penyesuaian_id',
        'produk_id',
        'stok_sebelum',
        'stok_setelah',
        'qty_selisih',
    ];

    public function penyesuaian()
    {
        return $this->belongsTo(StokPenyesuaian::class, 'stok_penyesuaian_id');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }
}
