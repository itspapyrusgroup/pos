<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CabangSalesMode;
use App\Models\MetodePembayaran;
use App\Models\Studio;

class Cabang extends Model
{
    use HasFactory;

    protected $table = 'cabang';
    protected $fillable = [
        'kode',
        'perusahaan_id',
        'nama',
        'alamat',
        'no_hp',
        'struk_footer',
        'warna_header',
        'allow_minus_stock',
        'tutup_kasir_email_enabled',
        'tutup_kasir_email_recipients',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'allow_minus_stock' => 'boolean',
        'tutup_kasir_email_enabled' => 'boolean',
        'tutup_kasir_email_recipients' => 'array',
    ];

    // Relasi ke perusahaan
    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class);
    }

    public function salesModes()
    {
        return $this->hasMany(CabangSalesMode::class, 'cabang_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'cabang_user')->withTimestamps();
    }

    public function studios()
    {
        return $this->hasMany(Studio::class, 'cabang_id');
    }

    public function metodePembayaran()
    {
        return $this->belongsToMany(
            MetodePembayaran::class,
            'cabang_metode_pembayaran',
            'cabang_id',
            'metode_pembayaran_id'
        )->withTimestamps();
    }

    // Generate kode cabang otomatis
    public static function generateKode()
    {
        $lastNumber = self::query()
            ->orderByDesc('id')
            ->value('kode');

        $nextNumber = 1;
        if ($lastNumber) {
            $lastNumber = str_replace('BR', '', $lastNumber);
            $nextNumber = (int)$lastNumber + 1;
        }

        return 'BR' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

}
