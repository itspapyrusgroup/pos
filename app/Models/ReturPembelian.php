<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturPembelian extends Model
{
    use HasFactory;

    protected $table = 'retur_pembelian';

    protected $fillable = [
        'nomor_retur',
        'penerimaan_barang_id',
        'pesanan_pembelian_id',
        'pemasok_id',
        'cabang_id',
        'tanggal_retur',
        'status',
        'dibuat_oleh',
        'catatan',
    ];

    public function penerimaanBarang()
    {
        return $this->belongsTo(PenerimaanBarang::class, 'penerimaan_barang_id');
    }

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
        return $this->hasMany(ReturPembelianItem::class, 'retur_pembelian_id');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
