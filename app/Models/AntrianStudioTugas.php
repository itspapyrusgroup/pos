<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AntrianStudioTugas extends Model
{
    use HasFactory;

    protected $table = 'antrian_studio_tugas';

    protected $fillable = [
        'antrian_studio_id',
        'pesanan_penjualan_item_id',
        'produk_id',
        'nama_tugas',
        'qty',
        'is_selesai',
        'selesai_at',
        'selesai_by_user_id',
    ];

    protected $casts = [
        'qty' => 'float',
        'is_selesai' => 'boolean',
        'selesai_at' => 'datetime',
    ];

    public function antrianStudio()
    {
        return $this->belongsTo(AntrianStudio::class, 'antrian_studio_id');
    }

    public function pesananItem()
    {
        return $this->belongsTo(PesananPenjualanItem::class, 'pesanan_penjualan_item_id');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function selesaiBy()
    {
        return $this->belongsTo(User::class, 'selesai_by_user_id');
    }
}
