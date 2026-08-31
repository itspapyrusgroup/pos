<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Perusahaan extends Model
{
    use HasFactory;

    protected $table = 'perusahaan';
    protected $fillable = [
        'id',
        'kode',
        'nama',
        'npwp',
        'alamat',
        'no_hp',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];

    // Generate kode perusahaan otomatis
    public static function generateKode()
    {
        $lastNumber = self::query()
            ->orderByDesc('id')
            ->value('kode');

        $nextNumber = 1;
        if ($lastNumber) {
            $lastNumber = str_replace('C', '', $lastNumber);
            $nextNumber = (int)$lastNumber + 1;
        }

        return 'C' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    public function cabangs()
    {
        return $this->hasMany(Cabang::class);
    }
}
