<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FakturPembelian extends Model
{
    use HasFactory;

    protected $table = 'faktur_pembelian';

    protected $fillable = [
        'nomor_faktur',
        'pesanan_pembelian_id',
        'pemasok_id',
        'cabang_id',
        'tanggal_faktur',
        'jatuh_tempo',
        'total',
        'dibayar',
        'status',
        'dibuat_oleh',
        'catatan',
    ];

    public function pesananPembelian()
    {
        return $this->belongsTo(PesananPembelian::class, 'pesanan_pembelian_id');
    }

    public function pemasok()
    {
        return $this->belongsTo(Pemasok::class, 'pemasok_id');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }

    public function items()
    {
        return $this->hasMany(FakturPembelianItem::class, 'faktur_pembelian_id');
    }

    public function pembayaran()
    {
        return $this->hasMany(PembayaranPembelian::class, 'faktur_pembelian_id');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
