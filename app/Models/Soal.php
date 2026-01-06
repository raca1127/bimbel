<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Soal extends Model
{
    protected $table = 'soal';

    protected $fillable = [
        'materi_id',
        'pertanyaan',
        'jawaban_benar',
        'type',
        'choices',
    ];

    protected $casts = [
        'choices' => 'array',
    ];

    // Relasi ke materi
    public function materi(): BelongsTo
    {
        return $this->belongsTo(Materi::class);
    }

    // App\Models\Soal.php
    public function answers()
    {
        return $this->hasMany(Answer::class, 'soal_id');
    }

    
}
