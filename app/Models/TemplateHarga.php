<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateHarga extends Model
{
    use HasFactory;

    protected $table = 'template_harga';

    protected $fillable = [
        'kode',
        'nama',
        'keterangan',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(TemplateHargaItem::class, 'template_harga_id');
    }
}
