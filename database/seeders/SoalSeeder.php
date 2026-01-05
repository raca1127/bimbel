<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Soal;
use App\Models\Materi;

class SoalSeeder extends Seeder
{
    public function run(): void
    {
        // attach several soal to each materi
        $materis = Materi::all();
        foreach ($materis as $m) {
            $count = rand(3,6);
            for ($s=0;$s<$count;$s++) {
                if (rand(0,1) === 0) {
                    $choices = [];
                    for ($c=0;$c<5;$c++) $choices[] = "Pilihan {$c} untuk soal {$s}";
                    Soal::firstOrCreate([
                        'materi_id' => $m->id,
                        'pertanyaan' => "Contoh MCQ soal {$s} untuk {$m->judul}"
                    ], [
                        'jawaban_benar' => '0',
                        'type' => 'mcq',
                        'choices' => $choices,
                    ]);
                } else {
                    Soal::firstOrCreate([
                        'materi_id' => $m->id,
                        'pertanyaan' => "Contoh essai soal {$s} untuk {$m->judul}"
                    ], [
                        'jawaban_benar' => 'Jawaban contoh',
                        'type' => 'essay',
                        'choices' => null,
                    ]);
                }
            }
        }
    }
}
