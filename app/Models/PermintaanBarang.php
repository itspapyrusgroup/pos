<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermintaanBarang extends Model
{
    use HasFactory;

    protected $table = 'permintaan_barang';

    protected $fillable = [
        'nomor_permintaan',
        'cabang_id',
        'tanggal_permintaan',
        'tanggal_dibutuhkan',
        'status',
        'dibuat_oleh',
        'catatan',
    ];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }

    public function items()
    {
        return $this->hasMany(PermintaanBarangItem::class, 'permintaan_barang_id');
    }

    public function pesananPembelian()
    {
        return $this->hasMany(PesananPembelian::class, 'permintaan_barang_id');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
