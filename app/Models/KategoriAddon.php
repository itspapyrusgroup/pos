<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriAddon extends Model
{
    use HasFactory;

    protected $table = 'kategori_addon';

    protected $fillable = [
        'nama',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
}
