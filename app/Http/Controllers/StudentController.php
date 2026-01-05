<?php

namespace App\Http\Controllers;

use App\Models\Materi;
use App\Models\MateriPelajar;
use App\Models\Soal;
use App\Models\Bookmark;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StudentController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            $role = $user->role ?? '';
            // allow pelajar or guru (guru may access student views)
            if (! $user || ! in_array($role, ['pelajar','guru'])) {
                abort(403);
            }
            return $next($request);
        });
    }

  // Student dashboard
public function index(Request $request)
{
    $pelajar = Auth::user();

    // Query dasar Materi
    $query = Materi::with('soal');

    // Jika ada query pencarian
    if ($request->has('q') && $request->q != '') {
        $search = $request->q;
        $query->where('judul', 'like', "%{$search}%")
              ->orWhere('konten', 'like', "%{$search}%");
    }

    // Ambil materi dengan pagination
    $materi = $query->paginate(8)->withQueryString(); // withQueryString biar q tetap di pagination

    // Ambil ID materi yang sudah completed
    $readIds = MateriPelajar::where('pelajar_id', $pelajar->id)
        ->where('status', 'completed')
        ->pluck('materi_id') // ambil ID materinya saja
        ->toArray();

    $readCount = count($readIds); // jumlah completed
    $total = Materi::count();
    $canAccessSoal = $total > 0 && $readCount >= $total;

    // bookmarked materi ids for current user
    $bookmarkedIds = $pelajar->bookmarks()->pluck('materi_id')->toArray();

    return view('student.dashboard', compact(
        'materi', 'canAccessSoal', 'readCount', 'total', 'bookmarkedIds', 'readIds'
    ));
}


    // Toggle bookmark for a materi (create or remove)
    public function toggleBookmark($id)
    {
        $user = Auth::user();
        $materi = Materi::findOrFail($id);

        $existing = Bookmark::where('user_id', $user->id)->where('materi_id', $materi->id)->first();
        if ($existing) {
            $existing->delete();
            return redirect()->back()->with('success', 'Bookmark dihapus.');
        }

        Bookmark::create(['user_id' => $user->id, 'materi_id' => $materi->id]);
        return redirect()->back()->with('success', 'Materi dibookmark.');
    }

    // mark materi read
    public function readMateri($id)
    {
        $pelajar = Auth::user();
        $materi = Materi::findOrFail($id);
        $record = MateriPelajar::where('pelajar_id', $pelajar->id)->where('materi_id', $materi->id)->first();
        
        if (! $record) {
            MateriPelajar::create(['pelajar_id' => $pelajar->id, 'materi_id' => $materi->id, 'status' => 'completed']);
            // increment reads for first-time read
            $materi->increment('reads');
        } elseif ($record->status !== 'completed') {
            $record->update(['status' => 'completed']);
            $materi->increment('reads');
        } else {
            // already read — increment only reads
            $materi->increment('reads');
        }

        return redirect()->route('student.index')->with('success', 'Materi ditandai sudah dibaca.');
    }

    // Start a new quiz attempt: create Attempt and select questions
    public function startQuiz(Request $request)
    {
        $pelajar = Auth::user();
        // legacy global quiz start — keep for compatibility
        $attempt = Attempt::create([
            'pelajar_id' => $pelajar->id,
            'started_at' => Carbon::now(),
        ]);

        // Select up to 5 MCQ and 5 essay randomly across all soal
        $mcq = Soal::where('type', 'mcq')->inRandomOrder()->limit(5)->get();
        $essay = Soal::where('type', 'essay')->inRandomOrder()->limit(5)->get();
        $questions = $mcq->merge($essay);

        foreach ($questions as $q) {
            AttemptAnswer::create([
                'attempt_id' => $attempt->id,
                'soal_id' => $q->id,
                'answer' => null,
                'is_correct' => false,
            ]);
        }

        return redirect()->route('student.quiz.show', $attempt->id);
    }

    // Start final exam for a specific materi
    public function startExam($materiId)
    {
        $pelajar = Auth::user();
        $materi = Materi::findOrFail($materiId);

        // allow both pelajar and guru to take the final exam
        $record = MateriPelajar::where('pelajar_id', $pelajar->id)->where('materi_id', $materi->id)->first();
        if ($pelajar->role === 'pelajar') {
            if (! $record || $record->status !== 'read') {
                return redirect()->route('student.index')->with('error', 'Anda harus membaca materi sebelum mengikuti ujian akhir.');
            }
        }

        $attempt = Attempt::create([
            'pelajar_id' => $pelajar->id,
            'materi_id' => $materi->id,
            'started_at' => Carbon::now(),
        ]);

        // select soal for this materi (limit to 10 total)
        $questions = Soal::where('materi_id', $materi->id)->inRandomOrder()->limit(10)->get();
        foreach ($questions as $q) {
            AttemptAnswer::create([
                'attempt_id' => $attempt->id,
                'soal_id' => $q->id,
                'answer' => null,
                'is_correct' => false,
            ]);
        }

        return redirect()->route('student.quiz.show', $attempt->id);
    }

    // Show quiz page with timer (50 minutes)
    public function showQuiz($attemptId)
    {
        $attempt = Attempt::with('answers.soal')->findOrFail($attemptId);
        $pelajar = Auth::user();
        if ($attempt->pelajar_id !== $pelajar->id) abort(403);

        $started = Carbon::parse($attempt->started_at);
        $now = Carbon::now();
        $elapsed = $now->diffInSeconds($started);
        $limitSeconds = 50 * 60; // 50 minutes
        $remaining = max(0, $limitSeconds - $elapsed);

        return view('student.quiz', compact('attempt', 'remaining'));
    }

    // Submit quiz answers and compute score
    public function submitQuiz(Request $request, $attemptId)
    {
        $user = Auth::user();
        if ($user->is_blocked) {
            return redirect()->route('student.index')->with('error', 'Akun Anda diblokir.');
        }
        $attempt = Attempt::with('answers.soal')->findOrFail($attemptId);
        $pelajar = Auth::user();
        if ($attempt->pelajar_id !== $pelajar->id) abort(403);

        $started = Carbon::parse($attempt->started_at);
        $now = Carbon::now();
        $duration = $now->diffInSeconds($started);
        $limitSeconds = 50 * 60;
        if ($duration > $limitSeconds) {
            // timeout — still accept but mark ended
        }

        $answers = $request->input('answers', []);
        $correct = 0;
        $total = $attempt->answers->count();

        foreach ($attempt->answers as $ans) {
            $soal = $ans->soal;
            $given = $answers[$soal->id] ?? null;
            $isCorrect = false;
            if ($soal->type === 'mcq') {
                // compare given value with jawaban_benar
                if ($given !== null && strval($given) === strval($soal->jawaban_benar)) {
                    $isCorrect = true;
                }
            } else {
                // essay: simple text match (case-insensitive trim)
                if ($given !== null && strtolower(trim($given)) === strtolower(trim($soal->jawaban_benar))) {
                    $isCorrect = true;
                }
            }

            $ans->update(['answer' => $given, 'is_correct' => $isCorrect]);
            if ($isCorrect) $correct++;
        }

        $score = $total ? intval(round(($correct / $total) * 100)) : 0;
        $attempt->update([
            'score' => $score,
            'finished_at' => $now,
            'duration_seconds' => $duration,
        ]);

        // Award points for correct answers (avoid double-award)
        $pointsPerCorrect = 10;
        if (empty($attempt->points_awarded) || $attempt->points_awarded == 0) {
            $points = $correct * $pointsPerCorrect;
            if ($points > 0) {
                $attempt->pelajar->increment('points', $points);
                $attempt->points_awarded = $points;
                $attempt->save();
            }
        }

        // If this attempt is for a materi (final exam) and score >= 70, mark as completed
        if ($attempt->materi_id) {
            if ($score >= 70) {
                $mp = MateriPelajar::firstOrCreate([
                    'pelajar_id' => $pelajar->id,
                    'materi_id' => $attempt->materi_id,
                ], ['status' => 'read']);

                if ($mp->status !== 'completed') {
                    $mp->status = 'completed';
                    $mp->save();
                    // increment completions count on materi
                    $materi = Materi::find($attempt->materi_id);
                    if ($materi) $materi->increment('completions');
                }
            }
        }

        return redirect()->route('student.attempt.result', $attempt->id)->with('success', 'Quiz selesai. Nilai anda: ' . $score);
    }

    public function showResult($attemptId)
    {
        $attempt = Attempt::with('answers.soal')->findOrFail($attemptId);
        $pelajar = Auth::user();
        if ($attempt->pelajar_id !== $pelajar->id) abort(403);
        return view('student.result', compact('attempt'));
    }

    // Show leaderboard
    public function leaderboard()
    {
        $top = Attempt::select('pelajar_id')
            ->selectRaw('MAX(score) as max_score')
            ->groupBy('pelajar_id')
            ->orderByDesc('max_score')
            ->with('pelajar')
            ->get();

        return view('student.leaderboard', compact('top'));
    }

    // Show attempt history for student
    public function history()
    {
        $pelajar = Auth::user();
        $attempts = Attempt::where('pelajar_id', $pelajar->id)->orderByDesc('created_at')->paginate(12);
        return view('student.history', compact('attempts'));
    }

    // View list of soal (if allowed)
    public function viewSoal()
    {
        $pelajar = Auth::user();
        $readCount = MateriPelajar::where('pelajar_id', $pelajar->id)->where('status', 'read')->count();
        $total = Materi::count();
        if ($total === 0 || $readCount < $total) {
            return redirect()->route('student.index')->with('error', 'Anda belum memenuhi syarat untuk mengakses soal.');
        }

        $soal = Soal::with('materi')->get();
        return view('student.soal', compact('soal'));
    }
}


