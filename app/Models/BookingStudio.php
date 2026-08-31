<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingStudio extends Model
{
    use HasFactory;

    protected $table = 'booking_studio';

    protected $fillable = [
        'nomor_booking',
        'pesanan_penjualan_id',
        'pelanggan_id',
        'cabang_id',
        'studio_id',
        'tanggal_booking',
        'status',
    ];

    protected $casts = [
        'tanggal_booking' => 'datetime',
    ];

    public function pesananPenjualan()
    {
        return $this->belongsTo(PesananPenjualan::class, 'pesanan_penjualan_id');
    }

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }

    public function studio()
    {
        return $this->belongsTo(Studio::class, 'studio_id');
    }
}
