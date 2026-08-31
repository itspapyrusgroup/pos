<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Studio extends Model
{
    use HasFactory;

    protected $table = 'studio';

    protected $fillable = [
        'cabang_id',
        'tema_studio_id',
        'nama',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }

    public function temaStudio()
    {
        return $this->belongsTo(TemaStudio::class, 'tema_studio_id');
    }
}
