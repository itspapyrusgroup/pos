<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetodePembayaran extends Model
{
    use HasFactory;

    protected $table = 'metode_pembayaran';

    protected $fillable = [
        'kode',
        'nama',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function cabang()
    {
        return $this->belongsToMany(
            Cabang::class,
            'cabang_metode_pembayaran',
            'metode_pembayaran_id',
            'cabang_id'
        )->withTimestamps();
    }
}
