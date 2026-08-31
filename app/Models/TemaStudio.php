<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemaStudio extends Model
{
    use HasFactory;

    protected $table = 'tema_studio';

    protected $fillable = [
        'nama',
        'deskripsi',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function studios()
    {
        return $this->hasMany(Studio::class, 'tema_studio_id');
    }
}
