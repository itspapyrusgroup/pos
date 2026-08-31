<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenjualanVoidOtp extends Model
{
    use HasFactory;

    protected $table = 'penjualan_void_otps';

    protected $fillable = [
        'kode_otp',
        'pesanan_penjualan_id',
        'tipe_void',
        'tipe_transaksi',
        'item_payload',
        'expired_at',
        'used_at',
        'generated_by_user_id',
        'used_by_user_id',
    ];

    protected $casts = [
        'item_payload' => 'array',
        'expired_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(PesananPenjualan::class, 'pesanan_penjualan_id');
    }
}
