<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiKonfigurasi extends Model
{
    use HasFactory;

    protected $table = 'kpi_konfigurasi';

    protected $fillable = [
        'cabang_id',
        'nama_konfigurasi',
        'persen_cs_kasir_spv',
        'persen_fotografer',
        'include_kasir',
        'include_spv',
        'status',
    ];

    protected $casts = [
        'persen_cs_kasir_spv' => 'float',
        'persen_fotografer' => 'float',
        'include_kasir' => 'boolean',
        'include_spv' => 'boolean',
        'status' => 'boolean',
    ];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }
}