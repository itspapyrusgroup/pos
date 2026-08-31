<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Addon extends Model
{
    use HasFactory;

    protected $table = 'addon';

    protected $fillable = [
        'kode',
        'nama',
        'kategori_addon_id',
        'bom_id',
        'deskripsi',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function kategoriAddon()
    {
        return $this->belongsTo(KategoriAddon::class, 'kategori_addon_id');
    }
}
