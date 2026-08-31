<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriProduk extends Model
{
    use HasFactory;

    protected $table = 'kategori_produk';

    protected $fillable = [
        'kode',
        'id_divisi',
        'tracking_reference_id',
        'nama',
        'tipe',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function produk()
    {
        return $this->hasMany(Produk::class, 'kategori_produk_kode', 'kode');
    }

    public function divisi()
    {
        return $this->belongsTo(Divisi::class, 'id_divisi');
    }

    public function trackingReference()
    {
        return $this->belongsTo(TrackingReference::class, 'tracking_reference_id');
    }

}
