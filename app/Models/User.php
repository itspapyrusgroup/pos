<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'no_wa',
        'foto_profil',
        'status',
        'role_id',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function karyawan()
    {
        return $this->hasOne(Karyawan::class);
    }

    public function cabang()
    {
        return $this->belongsToMany(Cabang::class, 'cabang_user')->withTimestamps();
    }

    public function shiftKasir()
    {
        return $this->hasMany(ShiftKasir::class);
    }

    public function permissions(): Collection
    {
        if (!$this->role || !$this->role->relationLoaded('permissions')) {
            $this->loadMissing('role.permissions');
        }

        return $this->role?->permissions ?? collect();
    }

    public function hasPermission(string $kode): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->permissions()->contains('kode', $kode);
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->role && $this->role->nama === 'Super Admin';
    }
}
