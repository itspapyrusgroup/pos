<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PesananPembelian extends Model
{
    use HasFactory;

    protected $table = 'pesanan_pembelian';

    protected $fillable = [
        'nomor_po',
        'permintaan_barang_id',
        'pemasok_id',
        'cabang_id',
        'tanggal_po',
        'tanggal_kirim',
        'status',
        'dibuat_oleh',
        'catatan',
    ];

    public function permintaanBarang()
    {
        return $this->belongsTo(PermintaanBarang::class, 'permintaan_barang_id');
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
        return $this->hasMany(PesananPembelianItem::class, 'pesanan_pembelian_id');
    }

    public function penerimaan()
    {
        return $this->hasMany(PenerimaanBarang::class, 'pesanan_pembelian_id');
    }

    public function faktur()
    {
        return $this->hasMany(FakturPembelian::class, 'pesanan_pembelian_id');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
