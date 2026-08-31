<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenjualanVoidLog extends Model
{
    use HasFactory;

    protected $table = 'penjualan_void_logs';

    protected $fillable = [
        'pesanan_penjualan_id',
        'kantong_order_id',
        'otp_id',
        'tipe_void',
        'tipe_transaksi',
        'alasan',
        'nominal_void',
        'void_effective_date',
        'voided_at',
        'voided_by_user_id',
        'authorized_by_user_id',
        'item_payload',
    ];

    protected $casts = [
        'nominal_void' => 'float',
        'void_effective_date' => 'date',
        'voided_at' => 'datetime',
        'item_payload' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(PesananPenjualan::class, 'pesanan_penjualan_id');
    }

    public function voidedBy()
    {
        return $this->belongsTo(User::class, 'voided_by_user_id');
    }

    public function otp()
    {
        return $this->belongsTo(PenjualanVoidOtp::class, 'otp_id');
    }

    public function authorizedBy()
    {
        return $this->belongsTo(User::class, 'authorized_by_user_id');
    }
}
