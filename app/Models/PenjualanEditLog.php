<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenjualanEditLog extends Model
{
    use HasFactory;

    protected $table = 'penjualan_edit_logs';

    protected $fillable = [
        'pesanan_penjualan_id',
        'kantong_order_id',
        'edited_by_user_id',
        'alasan',
        'before_snapshot',
        'after_snapshot',
        'edited_at',
    ];

    protected $casts = [
        'before_snapshot' => 'array',
        'after_snapshot' => 'array',
        'edited_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(PesananPenjualan::class, 'pesanan_penjualan_id');
    }

    public function kantongOrder()
    {
        return $this->belongsTo(KantongOrder::class, 'kantong_order_id');
    }

    public function editedBy()
    {
        return $this->belongsTo(User::class, 'edited_by_user_id');
    }
}
