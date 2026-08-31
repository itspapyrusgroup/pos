<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenjualanRequestLog extends Model
{
    use HasFactory;

    protected $table = 'penjualan_request_logs';

    protected $fillable = [
        'client_request_id',
        'user_id',
        'cabang_id',
        'pesanan_penjualan_id',
        'status',
        'mode',
        'message',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function pesananPenjualan()
    {
        return $this->belongsTo(PesananPenjualan::class, 'pesanan_penjualan_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }
}
