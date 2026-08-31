<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StokPenyesuaian extends Model
{
    use HasFactory;

    protected $table = 'stok_penyesuaian';

    protected $fillable = [
        'tanggal_penyesuaian',
        'cabang_id',
        'catatan',
        'dibuat_oleh',
    ];

    protected $casts = [
        'tanggal_penyesuaian' => 'date',
    ];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }

    public function dibuatOleh()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function items()
    {
        return $this->hasMany(StokPenyesuaianItem::class, 'stok_penyesuaian_id');
    }
}
