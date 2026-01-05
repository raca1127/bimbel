<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Materi extends Model
{
    protected $table = 'materi';

    protected $fillable = [
        'guru_id',
        'judul',
        'konten',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    // Relasi ke guru
    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    // Relasi ke soal (1 materi → many soal)
    public function soal(): HasMany
    {
        return $this->hasMany(Soal::class);
    }

    // Relasi ke materi_pelajar
    public function materiPelajar(): HasMany
    {
        return $this->hasMany(MateriPelajar::class);
    }

    // Relasi ke bookmark
    public function bookmarks(): HasMany
    {
        return $this->hasMany(\App\Models\Bookmark::class, 'materi_id');
    }
}
