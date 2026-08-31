<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KoTrackingItemCheck extends Model
{
    use HasFactory;

    protected $table = 'ko_tracking_item_checks';

    protected $fillable = [
        'pesanan_penjualan_item_id',
        'produk_id',
        'is_checked',
        'checked_at',
        'checked_by_user_id',
    ];

    protected $casts = [
        'is_checked' => 'boolean',
        'checked_at' => 'datetime',
    ];

    public function orderItem()
    {
        return $this->belongsTo(PesananPenjualanItem::class, 'pesanan_penjualan_item_id');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function checkedBy()
    {
        return $this->belongsTo(User::class, 'checked_by_user_id');
    }
}
