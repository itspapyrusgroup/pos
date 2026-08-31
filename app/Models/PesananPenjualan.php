<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PesananPenjualan extends Model
{
    use HasFactory;

    protected $table = 'pesanan_penjualan';

    protected $fillable = [
        'nomor_so',
        'pelanggan_id',
        'customer_name',
        'customer_phone',
        'customer_address',
        'cabang_id',
        'sales_mode_id',
        'template_harga_id',
        'shift_kasir_id',
        'kasir_user_id',
        'cs_user_id',
        'cs1_user_id',
        'cs2_user_id',
        'spv_user_id',
        'fotografer_user_id',
        'total',
        'diskon_otomatis',
        'paid_total',
        'balance',
        'status_pembayaran',
        'catatan',
        'voided_at',
    ];

    protected $casts = [
        'voided_at' => 'datetime',
        'diskon_otomatis' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(PesananPenjualanItem::class, 'pesanan_penjualan_id');
    }

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }

    public function pembayaran()
    {
        return $this->hasMany(PembayaranPenjualan::class, 'pesanan_penjualan_id');
    }

    public function kantongOrder()
    {
        return $this->hasOne(KantongOrder::class, 'pesanan_penjualan_id');
    }

    public function kasir()
    {
        return $this->belongsTo(User::class, 'kasir_user_id');
    }

    public function shiftKasir()
    {
        return $this->belongsTo(ShiftKasir::class, 'shift_kasir_id');
    }

    public function cs1()
    {
        return $this->belongsTo(User::class, 'cs1_user_id');
    }

    public function cs()
    {
        return $this->belongsTo(User::class, 'cs_user_id');
    }

    public function cs2()
    {
        return $this->belongsTo(User::class, 'cs2_user_id');
    }

    public function spv()
    {
        return $this->belongsTo(User::class, 'spv_user_id');
    }

    public function fotografer()
    {
        return $this->belongsTo(User::class, 'fotografer_user_id');
    }

    public function voidLogs()
    {
        return $this->hasMany(PenjualanVoidLog::class, 'pesanan_penjualan_id');
    }

    public function paymentMethodLogs()
    {
        return $this->hasMany(PenjualanPaymentMethodLog::class, 'pesanan_penjualan_id');
    }

    public function editLogs()
    {
        return $this->hasMany(PenjualanEditLog::class, 'pesanan_penjualan_id');
    }
}
