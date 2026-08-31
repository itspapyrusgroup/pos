<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenerimaanBarang extends Model
{
    use HasFactory;

    protected $table = 'penerimaan_barang';

    protected $fillable = [
        'nomor_penerimaan',
        'pesanan_pembelian_id',
        'cabang_id',
        'tanggal_penerimaan',
        'status',
        'dibuat_oleh',
        'catatan',
    ];

    public function pesananPembelian()
    {
        return $this->belongsTo(PesananPembelian::class, 'pesanan_pembelian_id');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }

    public function items()
    {
        return $this->hasMany(PenerimaanBarangItem::class, 'penerimaan_barang_id');
    }

    public function retur()
    {
        return $this->hasMany(ReturPembelian::class, 'penerimaan_barang_id');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
