<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KantongOrder extends Model
{
    use HasFactory;

    protected $table = 'kantong_order';

    protected $fillable = [
        'nomor_ko',
        'pesanan_penjualan_id',
        'cabang_id',
        'designer_id',
        'status',
        'tanggal_selesai',
        'catatan',
    ];

    protected $casts = [
        'tanggal_selesai' => 'date',
    ];

    public function pesananPenjualan()
    {
        return $this->belongsTo(PesananPenjualan::class, 'pesanan_penjualan_id');
    }
}
