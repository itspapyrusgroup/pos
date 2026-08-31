<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pemasok extends Model
{
    use HasFactory;

    protected $table = 'pemasok';

    protected $fillable = [
        'kode',
        'nama',
        'kontak',
        'telepon',
        'alamat',
        'npwp',
        'kategori',
        'credit_terms_hari',
        'status',
        'catatan',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
}
