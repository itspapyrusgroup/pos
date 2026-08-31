<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenjualanPaymentMethodLog extends Model
{
    use HasFactory;

    protected $table = 'penjualan_payment_method_logs';

    protected $fillable = [
        'pesanan_penjualan_id',
        'pembayaran_penjualan_id',
        'otp_id',
        'from_metode_pembayaran_id',
        'to_metode_pembayaran_id',
        'nominal',
        'alasan',
        'corrected_at',
        'corrected_by_user_id',
        'authorized_by_user_id',
    ];

    protected $casts = [
        'nominal' => 'float',
        'corrected_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(PesananPenjualan::class, 'pesanan_penjualan_id');
    }

    public function pembayaran()
    {
        return $this->belongsTo(PembayaranPenjualan::class, 'pembayaran_penjualan_id');
    }

    public function otp()
    {
        return $this->belongsTo(PenjualanVoidOtp::class, 'otp_id');
    }

    public function fromMethod()
    {
        return $this->belongsTo(MetodePembayaran::class, 'from_metode_pembayaran_id');
    }

    public function toMethod()
    {
        return $this->belongsTo(MetodePembayaran::class, 'to_metode_pembayaran_id');
    }

    public function correctedBy()
    {
        return $this->belongsTo(User::class, 'corrected_by_user_id');
    }

    public function authorizedBy()
    {
        return $this->belongsTo(User::class, 'authorized_by_user_id');
    }
}
