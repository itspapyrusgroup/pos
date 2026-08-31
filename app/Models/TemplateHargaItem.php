<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateHargaItem extends Model
{
    use HasFactory;

    protected $table = 'template_harga_item';

    protected $fillable = [
        'template_harga_id',
        'jenis_item',
        'item_id',
        'harga',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
}
