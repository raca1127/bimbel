<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attempt extends Model
{
    protected $table = 'attempts';

    protected $fillable = [
        'pelajar_id',
        'materi_id',
        'score',
        'started_at',
        'finished_at',
        'duration_seconds',
        'points_awarded',
    ];

    public function pelajar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pelajar_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class, 'attempt_id');
    }

    public function materi(): BelongsTo
    {
        return $this->belongsTo(Materi::class, 'materi_id');
    }
}
