<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'guru_status',
        'is_blocked',
        'points',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Relasi untuk guru → materi
    public function materi(): HasMany
    {
        return $this->hasMany(Materi::class, 'guru_id');
    }

    // Relasi untuk pelajar → materi yang dibaca
    public function materiPelajar(): HasMany
    {
        return $this->hasMany(MateriPelajar::class, 'pelajar_id');
    }


    public function isBlocked(): bool
    {
        return (bool) $this->is_blocked;
    }

    public function getPointsAttribute($value)
    {
        return (int) ($value ?? 0);
    }

    // Relasi bookmark materi
    public function bookmarks()
    {
        return $this->hasMany(\App\Models\Bookmark::class, 'user_id');
    }
}
