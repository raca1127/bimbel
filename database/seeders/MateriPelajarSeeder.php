<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MateriPelajar;
use App\Models\User;
use App\Models\Materi;

class MateriPelajarSeeder extends Seeder
{
    public function run(): void
    {
        $pelajars = User::where('role', 'pelajar')->get();
        $materis = Materi::all();

        foreach ($pelajars as $pelajar) {
            foreach ($materis as $materi) {
                MateriPelajar::updateOrCreate([
                    'pelajar_id' => $pelajar->id,
                    'materi_id' => $materi->id,
                ], [
                    'status' => (rand(0,100) > 70) ? 'completed' : 'read'
                ]);
            }
        }
    }
}
