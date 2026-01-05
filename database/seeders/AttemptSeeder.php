<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\User;
use App\Models\Soal;
use Carbon\Carbon;

class AttemptSeeder extends Seeder
{
    public function run(): void
    {
        $pelajar = User::where('role', 'pelajar')->first();
        if (! $pelajar) return;
        // create a few attempts across random materi
        $materi = \App\Models\Materi::inRandomOrder()->first();
        if (! $materi) return;

        $soals = Soal::where('materi_id', $materi->id)->inRandomOrder()->limit(8)->get();
        if ($soals->isEmpty()) return;

        $attempt = Attempt::create([
            'pelajar_id' => $pelajar->id,
            'materi_id' => $materi->id,
            'score' => rand(50,95),
            'started_at' => Carbon::now()->subMinutes(60),
            'finished_at' => Carbon::now()->subMinutes(10),
            'duration_seconds' => rand(10,50)*60,
        ]);

        foreach ($soals as $s) {
            AttemptAnswer::create([
                'attempt_id' => $attempt->id,
                'soal_id' => $s->id,
                'answer' => null,
                'is_correct' => (rand(0,100) > 60),
            ]);
        }
    }
}
