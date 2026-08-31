<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AntrianStudio extends Model
{
    use HasFactory;

    protected $table = 'antrian_studio';

    protected $fillable = [
        'booking_studio_id',
        'cabang_id',
        'studio_id',
        'nomor_antrian',
        'status',
        'waktu_panggil',
        'called_at',
        'start_at',
        'end_at',
        'called_by_user_id',
        'started_by_user_id',
        'ended_by_user_id',
        'photographer_user_id',
    ];

    protected $casts = [
        'waktu_panggil' => 'datetime',
        'called_at' => 'datetime',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function bookingStudio()
    {
        return $this->belongsTo(BookingStudio::class, 'booking_studio_id');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }

    public function studio()
    {
        return $this->belongsTo(Studio::class, 'studio_id');
    }

    public function tugas()
    {
        return $this->hasMany(AntrianStudioTugas::class, 'antrian_studio_id');
    }

    public function calledBy()
    {
        return $this->belongsTo(User::class, 'called_by_user_id');
    }

    public function startedBy()
    {
        return $this->belongsTo(User::class, 'started_by_user_id');
    }

    public function endedBy()
    {
        return $this->belongsTo(User::class, 'ended_by_user_id');
    }

    public function photographer()
    {
        return $this->belongsTo(User::class, 'photographer_user_id');
    }
}
