<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PembayaranPembelian extends Model
{
    use HasFactory;

    protected $table = 'pembayaran_pembelian';

    protected $fillable = [
        'nomor_pembayaran',
        'faktur_pembelian_id',
        'metode_pembayaran_id',
        'tanggal_bayar',
        'nominal',
        'dibuat_oleh',
        'catatan',
    ];

    public function fakturPembelian()
    {
        return $this->belongsTo(FakturPembelian::class, 'faktur_pembelian_id');
    }

    public function metodePembayaran()
    {
        return $this->belongsTo(MetodePembayaran::class, 'metode_pembayaran_id');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
