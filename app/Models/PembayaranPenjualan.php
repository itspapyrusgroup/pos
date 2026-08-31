<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PembayaranPenjualan extends Model
{
    use HasFactory;

    protected $table = 'pembayaran_penjualan';

    protected $fillable = [
        'pesanan_penjualan_id',
        'metode_pembayaran_id',
        'shift_kasir_id',
        'kasir_user_id',
        'nominal',
        'tipe',
        'tanggal_bayar',
    ];

    protected $casts = [
        'tanggal_bayar' => 'datetime',
    ];

    public function metodePembayaran()
    {
        return $this->belongsTo(MetodePembayaran::class, 'metode_pembayaran_id');
    }

    public function pesananPenjualan()
    {
        return $this->belongsTo(PesananPenjualan::class, 'pesanan_penjualan_id');
    }

    public function kasir()
    {
        return $this->belongsTo(User::class, 'kasir_user_id');
    }

    public function shiftKasir()
    {
        return $this->belongsTo(ShiftKasir::class, 'shift_kasir_id');
    }

    public function paymentMethodLogs()
    {
        return $this->hasMany(PenjualanPaymentMethodLog::class, 'pembayaran_penjualan_id');
    }
}
