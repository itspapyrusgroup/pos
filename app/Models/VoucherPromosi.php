<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherPromosi extends Model
{
    use HasFactory;

    protected $table = 'voucher_promosi';

    protected $fillable = [
        'kode',
        'nama',
        'tipe_diskon',
        'nilai_diskon',
        'minimum_pembelian',
        'cabang_id',
        'aktif_mulai',
        'aktif_sampai',
        'aktif_24_jam',
        'jam_mulai',
        'jam_sampai',
        'hari_aktif',
        'kuota',
        'terpakai',
        'status',
    ];

    protected $casts = [
        'aktif_mulai' => 'date',
        'aktif_sampai' => 'date',
        'aktif_24_jam' => 'boolean',
        'hari_aktif' => 'array',
        'status' => 'boolean',
    ];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }

    public function cabangs()
    {
        return $this->belongsToMany(Cabang::class, 'voucher_promosi_cabang')
            ->withTimestamps();
    }
}
