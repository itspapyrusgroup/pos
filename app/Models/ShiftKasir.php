<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftKasir extends Model
{
    use HasFactory;

    protected $table = 'shift_kasir';

    protected $fillable = [
        'cabang_id',
        'user_id',
        'modal_awal',
        'kas_expected',
        'kas_fisik',
        'selisih',
        'detail_pecahan',
        'dibuka_pada',
        'ditutup_pada',
        'status',
    ];

    protected $casts = [
        'modal_awal' => 'float',
        'kas_expected' => 'float',
        'kas_fisik' => 'float',
        'selisih' => 'float',
        'detail_pecahan' => 'array',
        'dibuka_pada' => 'datetime',
        'ditutup_pada' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function pembayaranPenjualan()
    {
        return $this->hasMany(PembayaranPenjualan::class, 'shift_kasir_id');
    }
}
