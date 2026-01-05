<?php

namespace App\Http\Controllers;

use App\Models\Materi;
use App\Models\Soal;
use App\Models\MateriPelajar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MateriController extends Controller
{
    public function __construct()
    {
        // extra safety — ensure only guru access controller actions
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if (! $user || ($user->role ?? '') !== 'guru') {
                abort(403);
            }
            return $next($request);
        });
    }
public function index()
{
    $guru = Auth::user();

    // Ambil materi yang dibuat oleh guru ini saja, dengan soal
    $materi = Materi::with('soal')
        ->where('guru_id', $guru->id)
        ->paginate(10);

    // Ambil ID materi yang dibookmark guru
    $bookmarkedIds = $guru->bookmarks()->pluck('materi_id')->toArray();

    // Ambil data materi yang dibookmark
    $bookmarkedMateri = Materi::whereIn('id', $bookmarkedIds)->get();

    $total = Materi::count();
    $readCount = $total;

    // Ambil riwayat attempt yang terkait materi guru ini
    $attempts = \App\Models\Attempt::whereHas('answers.soal', function($q) use ($guru) {
        $q->whereHas('materi', fn($m) => $m->where('guru_id', $guru->id));
    })->orderByDesc('created_at')->paginate(12);

    $top = collect();  // bisa diisi leaderboard nanti

    // Ambil soal guru sendiri
    $soal = \App\Models\Soal::whereHas('materi', fn($q) => $q->where('guru_id', $guru->id))->get();

    return view('teacher.dashboard', compact(
        'materi', 
        'bookmarkedIds', 
        'bookmarkedMateri', 
        'readCount', 
        'total', 
        'attempts', 
        'top', 
        'soal'
    ));
}



    public function store(Request $request)
    {
        if (Auth::user()->role !== 'guru') {
            abort(403);
        }

        $rawQuestions = $request->input('questions', []);
        $cleanQuestions = [];

        foreach ($rawQuestions as $q) {

            $pertanyaan = trim($q['pertanyaan'] ?? '');
            $type = $q['type'] ?? null;

            // WAJIB ada pertanyaan & type
            if ($pertanyaan === '' || ! in_array($type, ['mcq', 'essay'])) {
                continue;
            }

            // Normalisasi soal
            $question = [
                'pertanyaan' => $pertanyaan,
                'type' => $type,
                'choices' => [],
                'jawaban_benar' => $q['jawaban_benar'] ?? null,
            ];

            // MCQ: minimal 2 pilihan
            if ($type === 'mcq') {
                $choices = array_values(array_filter(
                    $q['choices'] ?? [],
                    fn ($c) => trim($c) !== ''
                ));

                if (count($choices) < 2) {
                    continue;
                }

                $question['choices'] = $choices;
            }

            $cleanQuestions[] = $question;
        }

        // VALIDASI LOGIKA BISNIS
        $mcqCount = collect($cleanQuestions)->where('type', 'mcq')->count();
        $essayCount = collect($cleanQuestions)->where('type', 'essay')->count();

        if ($mcqCount < 1 || $essayCount < 1) {
            return back()
                ->withInput()
                ->withErrors('Minimal harus ada 1 soal pilihan ganda dan 1 soal essay.');
        }

        // VALIDASI FORM UTAMA
        $data = $request->validate([
            'judul' => 'required|string|max:255',
            'konten' => 'required|string',
        ]);

        \DB::beginTransaction();

        try {
            $materi = Materi::create([
                'guru_id' => Auth::id(),
                'judul' => $data['judul'],
                'konten' => $data['konten'],
            ]);

            foreach ($cleanQuestions as $q) {
                Soal::create([
                    'materi_id' => $materi->id,
                    'pertanyaan' => $q['pertanyaan'],
                    'type' => $q['type'],
                    'choices' => $q['choices'],
                    'jawaban_benar' => $q['jawaban_benar'],
                ]);
            }

            \DB::commit();

            return redirect()
                ->route('teacher.materi.index')
                ->with('success', 'Materi dan soal berhasil dibuat.');

        } catch (\Throwable $e) {
            \DB::rollBack();

            \Log::error('Materi store failed', [
                'error' => $e->getMessage(),
                'questions_raw' => $rawQuestions,
                'questions_clean' => $cleanQuestions,
            ]);

            return back()
                ->withInput()
                ->withErrors('Terjadi kesalahan saat menyimpan materi.');
        }
    }


    public function edit($id)
    {
        $materi = Materi::with('soal')->findOrFail($id);
        if ($materi->guru_id !== Auth::id()) abort(403);
        return view('teacher.edit', compact('materi'));
    }

    public function update(Request $request, $id)
{
    $materi = Materi::with('soal')->findOrFail($id);
    if ($materi->guru_id !== Auth::id()) abort(403);

    $rawQuestions = $request->input('questions', []);
    $cleanQuestions = [];

    // Ambil soal lama
    $existingQuestions = $materi->soal->keyBy('id');

    foreach ($rawQuestions as $q) {
        $id = $q['id'] ?? null;

        // Jika input soal kosong, ambil soal lama
        if ($id && isset($existingQuestions[$id])) {
            $oldQ = $existingQuestions[$id];

            // jika pertanyaan kosong, pakai data lama
            $pertanyaan = trim($q['pertanyaan'] ?? '') ?: $oldQ->pertanyaan;
            $type = $q['type'] ?? $oldQ->type;

            $question = [
                'id' => $id,
                'pertanyaan' => $pertanyaan,
                'type' => $type,
                'choices' => [],
                'jawaban_benar' => $q['jawaban_benar'] ?? $oldQ->jawaban_benar,
            ];

            if ($type === 'mcq') {
                $choices = array_values(array_filter(
                    $q['choices'] ?? $oldQ->choices ?? [],
                    fn($c) => trim($c) !== ''
                ));

                // fallback ke pilihan lama jika kosong
                if (empty($choices)) {
                    $choices = $oldQ->choices ?? [];
                }

                // validasi jawaban benar
                $jawaban_benar = $q['jawaban_benar'] ?? $oldQ->jawaban_benar;
                if (!is_numeric($jawaban_benar) || $jawaban_benar < 0 || $jawaban_benar >= count($choices)) {
                    $jawaban_benar = null;
                }

                $question['choices'] = $choices;
                $question['jawaban_benar'] = $jawaban_benar;
            }

            $cleanQuestions[] = $question;
            continue;
        }

        // Soal baru
        $pertanyaan = trim($q['pertanyaan'] ?? '');
        $type = $q['type'] ?? null;

        if ($pertanyaan === '' || !in_array($type, ['mcq', 'essay'])) {
            continue;
        }

        $question = [
            'id' => null,
            'pertanyaan' => $pertanyaan,
            'type' => $type,
            'choices' => [],
            'jawaban_benar' => $q['jawaban_benar'] ?? null,
        ];

        if ($type === 'mcq') {
            $choices = array_values(array_filter(
                $q['choices'] ?? [],
                fn($c) => trim($c) !== ''
            ));

            if (empty($choices)) continue;

            $jawaban_benar = $q['jawaban_benar'];
            if (!is_numeric($jawaban_benar) || $jawaban_benar < 0 || $jawaban_benar >= count($choices)) {
                $jawaban_benar = null;
            }

            $question['choices'] = $choices;
            $question['jawaban_benar'] = $jawaban_benar;
        }

        $cleanQuestions[] = $question;
    }

    // Validasi bisnis
    $mcqCount = collect($cleanQuestions)->where('type', 'mcq')->count();
    $essayCount = collect($cleanQuestions)->where('type', 'essay')->count();

    if ($mcqCount < 1 || $essayCount < 1) {
        return back()
            ->withInput()
            ->withErrors('Minimal harus ada 1 soal pilihan ganda dan 1 soal essay.');
    }

    // Validasi materi
    $data = $request->validate([
        'judul' => 'required|string|max:255',
        'konten' => 'required|string',
    ]);

    \DB::beginTransaction();

    try {
        $materi->update([
            'judul' => $data['judul'],
            'konten' => $data['konten'],
        ]);

        $existingIds = $materi->soal->pluck('id')->toArray();
        $sentIds = [];

        foreach ($cleanQuestions as $q) {
            if ($q['id'] && in_array($q['id'], $existingIds)) {
                Soal::where('id', $q['id'])->update([
                    'pertanyaan' => $q['pertanyaan'],
                    'type' => $q['type'],
                    'choices' => $q['choices'],
                    'jawaban_benar' => $q['jawaban_benar'],
                ]);
                $sentIds[] = $q['id'];
            } else {
                $new = Soal::create([
                    'materi_id' => $materi->id,
                    'pertanyaan' => $q['pertanyaan'],
                    'type' => $q['type'],
                    'choices' => $q['choices'],
                    'jawaban_benar' => $q['jawaban_benar'],
                ]);
                $sentIds[] = $new->id;
            }
        }

        // Hapus soal yang dihapus
        $toDelete = array_diff($existingIds, $sentIds);
        if (!empty($toDelete)) {
            Soal::whereIn('id', $toDelete)->delete();
        }

        \DB::commit();

        return redirect()
            ->route('teacher.materi.index')
            ->with('success', 'Materi berhasil diperbarui.');

    } catch (\Throwable $e) {
        \DB::rollBack();
        \Log::error('Materi update failed', [
            'error' => $e->getMessage(),
            'materi_id' => $materi->id,
            'questions_raw' => $rawQuestions,
            'questions_clean' => $cleanQuestions,
        ]);

        return back()
            ->withInput()
            ->withErrors('Terjadi kesalahan saat memperbarui materi.');
    }
}



    public function destroy($id)
    {
        $materi = Materi::findOrFail($id);
        if ($materi->guru_id !== Auth::id()) abort(403);
        $materi->soal()->delete();
        $materi->delete();
        return redirect()->route('teacher.materi.index')->with('success', 'Materi dihapus.');
    }

    // List attempts related to this guru's materi
public function attempts(Request $request)
{
    $guru = Auth::user();
    $materiId = $request->query('materi_id');

    $query = \App\Models\Attempt::whereHas('answers.soal', function($q) use ($guru) {
        $q->whereHas('materi', fn($m) => $m->where('guru_id', $guru->id));
    })->with('answers.soal','pelajar');

    if ($materiId) {
        $query->whereHas('answers.soal', fn($q) => $q->where('materi_id', $materiId));
    }

    $attempts = $query->orderByDesc('created_at')->paginate(12);

    return view('teacher.attempts', compact('attempts'));
}


    // Show CSV import form
    public function importForm()
    {
        return view('teacher.import');
    }

    // Handle CSV import (simple parser; expects `soal_json` column with JSON array)
    public function importCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');
        $header = null;
        $created = 0;
        $errors = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (! $header) {
                $header = $row;
                continue;
            }
            $data = array_combine($header, $row);
            if (! $data) continue;
            $judul = $data['judul'] ?? null;
            $konten = $data['konten'] ?? null;
            $is_public = isset($data['is_public']) ? (bool) $data['is_public'] : false;
            $soalJson = $data['soal_json'] ?? '[]';
            $soals = json_decode($soalJson, true);
            if (! $judul || ! $konten) {
                $errors[] = "Baris: judul atau konten kosong";
                continue;
            }
            if (! is_array($soals)) {
                $errors[] = "Baris: soal_json tidak valid untuk materi {$judul}";
                continue;
            }

            $materi = Materi::create([
                'guru_id' => Auth::id(),
                'judul' => $judul,
                'konten' => $konten,
                'is_public' => $is_public,
            ]);

            foreach ($soals as $s) {
                $pert = $s['pertanyaan'] ?? null;
                if (! $pert) continue;
                $type = $s['type'] ?? 'mcq';
                $jawaban = $s['jawaban_benar'] ?? null;
                $choices = $s['choices'] ?? [];
                if (! is_array($choices)) $choices = [];
                Soal::create([
                    'materi_id' => $materi->id,
                    'pertanyaan' => $pert,
                    'type' => $type,
                    'choices' => $choices,
                    'jawaban_benar' => $jawaban,
                ]);
            }

            $created++;
        }
        fclose($handle);

        $summary = "Created: {$created}. Errors: " . implode('; ', $errors);
        return redirect()->route('teacher.materi.index')->with('import_summary', $summary);
    }

    // Show grading page for an attempt (only if attempt contains this guru's soal)
    public function gradeAttempt($attemptId)
    {
        $guru = Auth::user();
        $attempt = \App\Models\Attempt::with('answers.soal')->findOrFail($attemptId);
        $found = false;
        foreach ($attempt->answers as $a) {
            if ($a->soal && $a->soal->materi && $a->soal->materi->guru_id == $guru->id) { $found = true; break; }
        }
        if (! $found) abort(403);
        return view('teacher.grade', compact('attempt'));
    }

    // Save grading (manual) for essay answers
    public function saveGrading(Request $request, $attemptId)
    {
        $guru = Auth::user();
        $attempt = \App\Models\Attempt::with('answers.soal')->findOrFail($attemptId);
        $inputs = $request->input('is_correct', []);
        $changed = false;
        foreach ($attempt->answers as $ans) {
            if (! isset($inputs[$ans->id])) continue;
            $val = (bool) $inputs[$ans->id];
            if ($ans->soal && $ans->soal->materi && $ans->soal->materi->guru_id == $guru->id) {
                $ans->update(['is_correct' => $val]);
                $changed = true;
            }
        }

        if ($changed) {
            // recalc score
            $correct = $attempt->answers()->where('is_correct', true)->count();
            $total = $attempt->answers()->count();
            $score = $total ? intval(round(($correct/$total)*100)) : 0;
            $attempt->update(['score' => $score]);
            // Award points to the attempt owner for newly-correct answers (avoid double-award)
            $pointsPerCorrect = 10;
            if (empty($attempt->points_awarded) || $attempt->points_awarded == 0) {
                $points = $correct * $pointsPerCorrect;
                if ($points > 0 && $attempt->pelajar) {
                    $attempt->pelajar->increment('points', $points);
                    $attempt->update(['points_awarded' => $points]);
                }
            }

            // If this attempt is for a materi and passed, ensure MateriPelajar is marked completed
            if ($attempt->materi_id && $score >= 70) {
                $mp = MateriPelajar::firstOrCreate([
                    'pelajar_id' => $attempt->pelajar_id,
                    'materi_id' => $attempt->materi_id,
                ], ['status' => 'read']);
                if ($mp->status !== 'completed') {
                    $mp->status = 'completed';
                    $mp->save();
                    $materi = Materi::find($attempt->materi_id);
                    if ($materi) $materi->increment('completions');
                }
            }
        }

        return redirect()->route('teacher.attempts')->with('success', 'Penilaian disimpan.');
    }

    
    public function show(Materi $materi)
    {
        $guru = Auth::user(); // optional jika ingin cek hak akses

        return view('student.materi', compact('materi'));
    }
}


