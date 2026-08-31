<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KartuStok extends Model
{
    use HasFactory;

    protected $table = 'kartu_stok';

    protected $fillable = [
        'produk_id',
        'cabang_id',
        'tipe_mutasi',
        'referensi_tipe',
        'referensi_id',
        'qty_masuk',
        'qty_keluar',
        'saldo_akhir',
        'catatan',
        'tanggal_mutasi',
    ];

    protected $casts = [
        'tanggal_mutasi' => 'datetime',
    ];
}
