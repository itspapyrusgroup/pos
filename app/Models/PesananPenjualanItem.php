<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PesananPenjualanItem extends Model
{
    use HasFactory;

    protected $table = 'pesanan_penjualan_item';

    protected $fillable = [
        'pesanan_penjualan_id',
        'produk_id',
        'paket_id',
        'custom_paket_items',
        'shift_kasir_id',
        'kasir_user_id',
        'qty',
        'harga',
        'diskon',
        'subtotal',
        'is_void',
        'voided_at',
        'void_log_id',
    ];

    protected $casts = [
        'is_void' => 'boolean',
        'voided_at' => 'datetime',
        'custom_paket_items' => 'array',
    ];

    public function pesananPenjualan()
    {
        return $this->belongsTo(PesananPenjualan::class, 'pesanan_penjualan_id');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function paket()
    {
        return $this->belongsTo(Paket::class, 'paket_id');
    }

    public function tugasStudio()
    {
        return $this->hasMany(AntrianStudioTugas::class, 'pesanan_penjualan_item_id');
    }

    public function voidLog()
    {
        return $this->belongsTo(PenjualanVoidLog::class, 'void_log_id');
    }

    public function kasir()
    {
        return $this->belongsTo(User::class, 'kasir_user_id');
    }

    public function shiftKasir()
    {
        return $this->belongsTo(ShiftKasir::class, 'shift_kasir_id');
    }
}
