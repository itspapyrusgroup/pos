<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JabatanTrackingReference extends Model
{
    use HasFactory;

    protected $table = 'jabatan_tracking_references';

    protected $fillable = [
        'jabatan_id',
        'tracking_reference_id',
        'can_update',
    ];

    protected $casts = [
        'can_update' => 'boolean',
    ];

    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class, 'jabatan_id');
    }

    public function trackingReference()
    {
        return $this->belongsTo(TrackingReference::class, 'tracking_reference_id');
    }
}
