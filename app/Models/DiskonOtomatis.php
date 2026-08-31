<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiskonOtomatis extends Model
{
    use HasFactory;

    protected $table = 'diskon_otomatis';

    protected $fillable = [
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
        return $this->belongsToMany(Cabang::class, 'diskon_otomatis_cabang')
            ->withTimestamps();
    }

    public function pakets()
    {
        return $this->belongsToMany(Paket::class, 'diskon_otomatis_paket')
            ->withTimestamps();
    }
}
