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
        $materi = Materi::where('guru_id', $guru->id)->with('soal')->get();
        return view('teacher.dashboard', compact('materi'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'guru') {
            return redirect()->back()->with('error', 'Hanya guru yang dapat membuat materi.');
        }
        // filter out any empty question slots submitted by the form to avoid validation errors
        $rawQuestions = $request->input('questions', []);
        $filtered = [];
        foreach ($rawQuestions as $q) {
            if (isset($q['pertanyaan']) && strlen(trim($q['pertanyaan'])) > 0) {
                $filtered[] = $q;
            }
        }
        $request->merge(['questions' => $filtered]);

        // build rules: only require question fields if there is at least one non-empty question
        $rules = [
            'judul' => 'required|string|max:255',
            'konten' => 'required|string',
            'questions' => 'array',
        ];
        if (count($filtered) > 0) {
            $rules['questions.*.type'] = 'required|in:mcq,essay';
            $rules['questions.*.pertanyaan'] = 'required|string';
            $rules['questions.*.choices'] = 'array';
            $rules['questions.*.jawaban_benar'] = 'nullable';
        }

        $data = $request->validate($rules);

        $materi = Materi::create([
            'guru_id' => Auth::id(),
            'judul' => $data['judul'],
            'konten' => $data['konten'],
        ]);

        // create multiple soal if provided
        $questions = $request->input('questions', []);
        if (! empty($questions)) {
            foreach ($questions as $q) {
                $soalData = [
                    'materi_id' => $materi->id,
                    'pertanyaan' => $q['pertanyaan'] ?? '',
                    'type' => $q['type'] ?? 'mcq',
                ];
                if (($q['type'] ?? '') === 'mcq') {
                    $choices = $q['choices'] ?? [];
                    $soalData['choices'] = array_values(array_filter($choices, fn($c)=>strlen(trim($c))>0));
                    $soalData['jawaban_benar'] = $q['jawaban_benar'] ?? null;
                } else {
                    $soalData['jawaban_benar'] = $q['jawaban_benar'] ?? null;
                }
                Soal::create($soalData);
            }
        }

        return redirect()->route('teacher.materi.index')->with('success', 'Materi dan Soal berhasil dibuat.');
    }

    public function edit($id)
    {
        $materi = Materi::with('soal')->findOrFail($id);
        if ($materi->guru_id !== Auth::id()) abort(403);
        return view('teacher.edit', compact('materi'));
    }

    public function update(Request $request, $id)
    {
        $materi = Materi::findOrFail($id);
        if ($materi->guru_id !== Auth::id()) abort(403);
        // filter empty questions to avoid validation on blank slots
        $rawQuestions = $request->input('questions', []);
        $filtered = [];
        foreach ($rawQuestions as $q) {
            if (isset($q['pertanyaan']) && strlen(trim($q['pertanyaan'])) > 0) {
                $filtered[] = $q;
            }
        }
        $request->merge(['questions' => $filtered]);

        $rules = [
            'judul' => 'required|string|max:255',
            'konten' => 'required|string',
            'questions' => 'array',
            'questions.*.id' => 'nullable|integer',
        ];
        if (count($filtered) > 0) {
            $rules['questions.*.type'] = 'required|in:mcq,essay';
            $rules['questions.*.pertanyaan'] = 'required|string';
            $rules['questions.*.choices'] = 'array';
            $rules['questions.*.jawaban_benar'] = 'nullable';
        }

        $data = $request->validate($rules);

        $materi->update(['judul' => $data['judul'], 'konten' => $data['konten']]);

        $questions = $request->input('questions', []);
        $existingIds = $materi->soal()->pluck('id')->toArray();
        $sentIds = [];
        foreach ($questions as $q) {
            if (! empty($q['id']) && in_array($q['id'], $existingIds)) {
                $soal = Soal::find($q['id']);
                // ensure ownership
                if ($soal && $soal->materi && $soal->materi->guru_id == Auth::id()) {
                    $update = [
                        'pertanyaan' => $q['pertanyaan'] ?? $soal->pertanyaan,
                        'type' => $q['type'] ?? $soal->type,
                    ];
                    if (($q['type'] ?? $soal->type) === 'mcq') {
                        $update['choices'] = array_values(array_filter($q['choices'] ?? [], fn($c)=>strlen(trim($c))>0));
                        $update['jawaban_benar'] = $q['jawaban_benar'] ?? $soal->jawaban_benar;
                    } else {
                        $update['jawaban_benar'] = $q['jawaban_benar'] ?? $soal->jawaban_benar;
                    }
                    $soal->update($update);
                    $sentIds[] = $soal->id;
                }
            } else {
                // create new soal
                $soalData = [
                    'materi_id' => $materi->id,
                    'pertanyaan' => $q['pertanyaan'] ?? '',
                    'type' => $q['type'] ?? 'mcq',
                ];
                if (($q['type'] ?? '') === 'mcq') {
                    $choices = $q['choices'] ?? [];
                    $soalData['choices'] = array_values(array_filter($choices, fn($c)=>strlen(trim($c))>0));
                    $soalData['jawaban_benar'] = $q['jawaban_benar'] ?? null;
                } else {
                    $soalData['jawaban_benar'] = $q['jawaban_benar'] ?? null;
                }
                $created = Soal::create($soalData);
                $sentIds[] = $created->id;
            }
        }

        // delete removed soal (only those belonging to this materi)
        $toDelete = array_diff($existingIds, $sentIds);
        if (! empty($toDelete)) {
            Soal::whereIn('id', $toDelete)->delete();
        }

        return redirect()->route('teacher.materi.index')->with('success', 'Materi diperbarui.');
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
    public function attempts()
    {
        $guru = Auth::user();
        $attempts = \App\Models\Attempt::whereHas('answers.soal', function($q) use ($guru) {
            $q->whereHas('materi', fn($m) => $m->where('guru_id', $guru->id));
        })->with('answers.soal','pelajar')->paginate(12);
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
}
