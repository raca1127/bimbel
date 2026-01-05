<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Materi;
use App\Models\User;

class MateriSeeder extends Seeder
{
    public function run(): void
    {
        $guru = User::where('email', 'guru_seed@example.com')->first() ?? User::where('role', 'guru')->first();

        // create 5 sample materi
        for ($i=1;$i<=5;$i++) {
            Materi::firstOrCreate([
                'judul' => "Materi Contoh #{$i}"
            ], [
                'guru_id' => $guru->id,
                'konten' => "Ini konten materi contoh nomor {$i}. Gunakan editor untuk memperkaya konten.",
                'is_public' => true,
                'reads' => rand(0,200),
                'completions' => rand(0,100),
            ]);
        }
    }
}
