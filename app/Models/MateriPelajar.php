<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MateriPelajar extends Model
{
    protected $table = 'materi_pelajar';

    protected $fillable = [
        'pelajar_id',
        'materi_id',
        'status',
    ];

    // Relasi ke pelajar
    public function pelajar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pelajar_id');
    }

    // Relasi ke materi
    public function materi(): BelongsTo
    {
        return $this->belongsTo(Materi::class);
    }
}
