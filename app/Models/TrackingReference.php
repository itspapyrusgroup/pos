<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackingReference extends Model
{
    use HasFactory;

    protected $table = 'tracking_references';

    protected $fillable = [
        'kode',
        'nama',
        'tipe',
        'urutan',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function kategoriProduk()
    {
        return $this->hasMany(KategoriProduk::class, 'tracking_reference_id');
    }

    public function jabatanPermissions()
    {
        return $this->hasMany(JabatanTrackingReference::class, 'tracking_reference_id');
    }
}
