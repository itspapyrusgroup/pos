<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paket extends Model
{
    use HasFactory;

    protected $table = 'paket';

    protected $fillable = [
        'kode',
        'nama',
        'harga_default',
        'kategori_paket_id',
        'deskripsi',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'harga_default' => 'float',
    ];

    public function kategoriPaket()
    {
        return $this->belongsTo(KategoriPaket::class, 'kategori_paket_id');
    }

    public function items()
    {
        return $this->hasMany(PaketItem::class, 'paket_id', 'kode');
    }
}
