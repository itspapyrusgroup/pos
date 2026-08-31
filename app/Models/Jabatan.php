<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jabatan extends Model
{
    use HasFactory;

    protected $table = 'jabatan';

    protected $fillable = [
        'kode',
        'nama',
        'level',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function karyawan()
    {
        return $this->hasMany(Karyawan::class, 'jabatan_id');
    }

    public function trackingReferences()
    {
        return $this->belongsToMany(
            TrackingReference::class,
            'jabatan_tracking_references',
            'jabatan_id',
            'tracking_reference_id'
        )->withPivot('can_update')->withTimestamps();
    }
}
