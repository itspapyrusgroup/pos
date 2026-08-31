<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StokCabang extends Model
{
    use HasFactory;

    protected $table = 'stok_cabang';

    protected $fillable = [
        'produk_id',
        'cabang_id',
        'qty',
        'qty_on_order',
    ];
}
