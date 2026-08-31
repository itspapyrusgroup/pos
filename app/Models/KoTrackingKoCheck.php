<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KoTrackingKoCheck extends Model
{
    use HasFactory;

    protected $table = 'ko_tracking_ko_checks';

    protected $fillable = [
        'pesanan_penjualan_id',
        'step_kode',
        'is_checked',
        'checked_at',
        'checked_by_user_id',
    ];

    protected $casts = [
        'is_checked' => 'boolean',
        'checked_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(PesananPenjualan::class, 'pesanan_penjualan_id');
    }

    public function checkedBy()
    {
        return $this->belongsTo(User::class, 'checked_by_user_id');
    }
}
