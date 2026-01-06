<?php

namespace App\Http\Controllers;

use App\Models\Materi;
use App\Models\Soal;
use App\Models\Attempt;
use App\Models\MateriPelajar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MateriController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(Auth::check() && Auth::user()->role === 'guru', 403);
            return $next($request);
        });
    }

    /* =====================================================
     * DASHBOARD GURU
     * ===================================================== */
    public function index()
    {
        $guru = Auth::user();

        $materi = Materi::with('soal')
            ->where('guru_id', $guru->id)
            ->paginate(10);

        $attempts = Attempt::whereHas('answers.soal.materi', fn ($q) =>
            $q->where('guru_id', $guru->id)
        )
        ->with('pelajar')
        ->latest()
        ->paginate(12);

        return view('teacher.dashboard', compact('materi', 'attempts'));
    }

/* =====================================================
 * STORE MATERI
 * ===================================================== */
public function store(Request $request)
{
    $data = $request->validate([
        'judul'  => 'required|string|max:255',
        'konten' => 'required|string',
    ]);

    $questions = $this->sanitizeQuestions($request->input('questions', []));

    if ($questions['mcq'] < 1 || $questions['essay'] < 1) {
        return back()->withInput()
                     ->withErrors('Minimal harus ada 1 soal MCQ dan 1 soal Essay.');
    }

    DB::transaction(function() use ($data, $questions) {
        $materi = Materi::create([
            'guru_id' => Auth::id(),
            'judul'   => $data['judul'],
            'konten'  => $data['konten'],
        ]);

        foreach ($questions['items'] as $q) {
            Soal::create(array_merge($q, ['materi_id' => $materi->id]));
        }
    });

    return redirect()->route('teacher.materi.index')
                     ->with('success', 'Materi berhasil dibuat.');
}


/* =====================================================
 * UPDATE MATERI
 * ===================================================== */
public function update(Request $request, $id)
{
    $materi = Materi::with('soal')->findOrFail($id);
    abort_if($materi->guru_id !== Auth::id(), 403);

    $data = $request->validate([
        'judul'  => 'required|string|max:255',
        'konten' => 'required|string',
    ]);

    $questions = $this->sanitizeQuestions($request->input('questions', []));

    if ($questions['mcq'] < 1 || $questions['essay'] < 1) {
        return back()->withInput()
                     ->withErrors('Minimal harus ada 1 soal MCQ dan 1 soal Essay.');
    }

    DB::transaction(function() use ($materi, $data, $questions) {
        // Update materi
        $materi->update($data);

        $existingIds = $materi->soal->pluck('id')->toArray();
        $usedIds = [];

        foreach ($questions['items'] as $q) {
            if (!empty($q['id']) && in_array($q['id'], $existingIds)) {
                // Update soal lama
                $soal = Soal::find($q['id']);
                $soal->update([
                    'type'         => $q['type'],
                    'pertanyaan'   => $q['pertanyaan'],
                    'choices'      => $q['choices'] ?? null,
                    'jawaban_benar'=> $q['jawaban_benar'] ?? null,
                ]);
                $usedIds[] = $q['id'];
            } else {
                // Buat soal baru
                $newSoal = Soal::create(array_merge($q, ['materi_id' => $materi->id]));
                $usedIds[] = $newSoal->id;
            }
        }

        // Hapus soal lama yang sudah dihapus di form
        $toDelete = array_diff($existingIds, $usedIds);
        if (!empty($toDelete)) {
            Soal::whereIn('id', $toDelete)->delete();
        }
    });

    return redirect()->route('teacher.materi.index')
                     ->with('success', 'Materi berhasil diperbarui.');
}


    /* =====================================================
     * EDIT
     * ===================================================== */
    public function edit($id)
    {
        $materi = Materi::with('soal')->findOrFail($id);
        abort_if($materi->guru_id !== Auth::id(), 403);

        return view('teacher.edit', compact('materi'));
    }

    /* =====================================================
     * HELPER
     * ===================================================== */
protected function sanitizeQuestions(array $questions)
{
    $items = [];
    $mcqCount = 0;
    $essayCount = 0;

    foreach ($questions as $q) {
        $type = $q['type'] ?? 'mcq';
        $data = [
            'type'       => $type,
            'pertanyaan' => $q['pertanyaan'] ?? '',
        ];

        if ($type === 'mcq') {
            // Simpan hanya pilihan yang ada isinya
            $choices = array_values(array_filter($q['choices'] ?? [], fn($c) => trim($c) !== ''));
            $data['choices'] = !empty($choices) ? json_encode($choices) : null; // simpan sebagai JSON
            $data['jawaban_benar'] = isset($q['jawaban_benar_mcq']) ? (string)$q['jawaban_benar_mcq'] : null;
            $mcqCount++;
        } else {
            // Essay
            $data['choices'] = null;
            $data['jawaban_benar'] = $q['jawaban_benar_essay'] ?? null;
            $essayCount++;
        }

        if (!empty($q['id'])) {
            $data['id'] = $q['id'];
        }

        $items[] = $data;
    }

    return [
        'items' => $items,
        'mcq'   => $mcqCount,
        'essay' => $essayCount,
    ];
}


    /* =====================================================
     * DELETE
     * ===================================================== */
    public function destroy($id)
    {
        $materi = Materi::findOrFail($id);
        abort_if($materi->guru_id !== Auth::id(), 403);

        $materi->soal()->delete();
        $materi->delete();

        return back()->with('success', 'Materi dihapus.');
    }

    /* =====================================================
     * ATTEMPTS & GRADING
     * ===================================================== */
    public function attempts(Request $request)
    {
        $guru = Auth::user();
        $materi_id = $request->query('materi_id'); // ambil materi_id dari URL
        $status    = $request->query('status');    // graded / ungraded

        $query = Attempt::with(['pelajar', 'answers.soal.materi'])
            ->whereHas('answers.soal.materi', function($q) use ($guru, $materi_id) {
                $q->where('guru_id', $guru->id);
                if ($materi_id) {
                    $q->where('id', $materi_id);
                }
            });

        // Filter status
        if ($status === 'graded') {
            $query->whereNotNull('score');
        } elseif ($status === 'ungraded') {
            $query->whereNull('score');
        }

        // Sorting terbaru dulu
        $attempts = $query->latest()->paginate(12)->withQueryString();

        return view('teacher.attempts', compact('attempts', 'materi_id', 'status'));
    }



    public function gradeAttempt($attemptId)
    {
        $attempt = Attempt::with('answers.soal.materi')->findOrFail($attemptId);

        abort_unless(
            $attempt->answers->contains(fn ($a) =>
                $a->soal->materi->guru_id === Auth::id()
            ),
            403
        );

        return view('teacher.grade', compact('attempt'));
    }

    public function saveGrading(Request $request, $attemptId)
    {
        $attempt = Attempt::with('answers.soal')->findOrFail($attemptId);

        // Pastikan guru adalah pemilik materi
        abort_unless(
            $attempt->answers->contains(fn ($a) => $a->soal->materi->guru_id === Auth::id()),
            403
        );

        // 1️⃣ Update essay sesuai input guru
        foreach ($attempt->answers as $ans) {
            if ($ans->soal->type === 'essay') {
                $isCorrect = (bool) $request->input("is_correct.$ans->id", false);
                $ans->update(['is_correct' => $isCorrect]);
            }
        }

        // 2️⃣ MCQ otomatis dinilai (tidak tergantung input guru)
        foreach ($attempt->answers as $ans) {
            if ($ans->soal->type === 'mcq') {
                $ans->update([
                    'is_correct' => $this->checkMcq($ans->soal, $ans->answer)
                ]);
            }
        }

        // 3️⃣ Hitung skor akhir
        $total   = $attempt->answers()->count();
        $correct = $attempt->answers()->where('is_correct', true)->count();
        $score   = $total ? round(($correct / $total) * 100) : 0;

        $attempt->update(['score' => $score]);

        // 4️⃣ Tandai materi selesai jika lulus
        if ($attempt->materi_id && $score >= 70) {
            MateriPelajar::updateOrCreate(
                [
                    'pelajar_id' => $attempt->pelajar_id,
                    'materi_id'  => $attempt->materi_id,
                ],
                ['status' => 'completed']
            );
        }

        return redirect()->route('teacher.attempts')
            ->with('success', 'Nilai disimpan. Skor akhir: ' . $score);
    }

    /**
     * Cek jawaban MCQ.
     *
     * @param \App\Models\Soal $soal
     * @param string|null $given Jawaban yang diberikan siswa
     * @return bool
     */
    private function checkMcq(Soal $soal, $given)
    {
        // Pastikan jawaban benar disimpan di kolom jawaban_benar
        if ($soal->jawaban_benar === null) {
            return false;
        }

        // Cocokkan jawaban siswa dengan jawaban benar
        return trim(strtolower($given)) === trim(strtolower($soal->jawaban_benar));
    }


    /* =====================================================
     * CSV IMPORT (TETAP ADA)
     * ===================================================== */
    public function importForm()
    {
        return view('teacher.import');
    }

    public function importCsv(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $created = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            if (!$data || empty($data['judul']) || empty($data['konten'])) continue;

            $materi = Materi::create([
                'guru_id' => Auth::id(),
                'judul'   => $data['judul'],
                'konten'  => $data['konten'],
            ]);

            foreach (json_decode($data['soal_json'] ?? '[]', true) as $s) {
                Soal::create([
                    'materi_id' => $materi->id,
                    'pertanyaan'=> $s['pertanyaan'] ?? '',
                    'type'      => $s['type'] ?? 'mcq',
                    'choices'   => $s['choices'] ?? [],
                    'jawaban_benar' => $s['jawaban_benar'] ?? null,
                ]);
            }

            $created++;
        }

        fclose($handle);

        return back()->with('success', "Import selesai. Materi dibuat: $created");
    }



public function show(Materi $materi)
{
    $user = Auth::user();

    // Tandai materi sudah dibaca (opsional)
    if ($user && $user->role === 'pelajar') {
        \App\Models\MateriPelajar::updateOrCreate(
            [
                'pelajar_id' => $user->id,
                'materi_id' => $materi->id
            ],
            ['status' => 'read']
        );
    }

    return view('student.materi', compact('materi'));
}



}
