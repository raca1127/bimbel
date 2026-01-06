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
            if (! $user || ! in_array($user->role, ['pelajar', 'guru'])) {
                abort(403);
            }
            return $next($request);
        });
    }

    /* =====================================================
     * DASHBOARD
     * ===================================================== */
public function index(Request $request)
{
    $pelajar = Auth::user();

    // Query Materi dengan filter pencarian
    $query = Materi::query();

    if ($request->filled('q')) {
        $query->where(function ($q) use ($request) {
            $q->where('judul', 'like', "%{$request->q}%")
              ->orWhere('konten', 'like', "%{$request->q}%");
        });
    }

    // Ambil materi paginasi
    $materi = $query->paginate(8)->withQueryString();

    // Materi yang sudah dibaca / completed
    $readIds = MateriPelajar::where('pelajar_id', $pelajar->id)
        ->where('status', 'completed')
        ->pluck('materi_id')
        ->toArray();

    $readCount = count($readIds);

    // Total materi
    $totalMateri = Materi::count();

    // Total soal di semua materi
    $totalSoal = Soal::count();

    // Materi yang bisa diakses soal
    $canAccessSoal = $readCount >= $totalMateri && $totalMateri > 0;

    // Materi yang di-bookmark
    $bookmarkedIds = $pelajar->bookmarks()->pluck('materi_id')->toArray();

    return view('student.dashboard', compact(
        'materi',
        'readIds',
        'bookmarkedIds',
        'canAccessSoal',
        'totalMateri',
        'totalSoal',  // <-- total soal dari semua materi
        'readCount'
    ));
}



    /* =====================================================
     * BOOKMARK
     * ===================================================== */
    public function toggleBookmark($id)
    {
        $user = Auth::user();

        $bookmark = Bookmark::where('user_id', $user->id)
            ->where('materi_id', $id)
            ->first();

        if ($bookmark) {
            $bookmark->delete();
            return back()->with('success', 'Bookmark dihapus.');
        }

        Bookmark::create([
            'user_id' => $user->id,
            'materi_id' => $id
        ]);

        return back()->with('success', 'Materi dibookmark.');
    }

/* =====================================================
 * READ MATERI
 * ===================================================== */
public function readMateri($id)
{
    $pelajar = Auth::user();
    $materi = Materi::findOrFail($id);

    // Ambil atau buat record MateriPelajar
    $mp = MateriPelajar::firstOrNew(
        [
            'pelajar_id' => $pelajar->id,
            'materi_id'  => $materi->id,
        ]
    );

    // Cek apakah baru dibuat atau belum completed
    if (!$mp->exists || $mp->status !== 'completed') {
        $mp->status = 'completed';
        $mp->save();

        // Increment hanya jika status baru saja menjadi completed
        $materi->increment('completions');
    }

    return redirect()->route('student.index')
        ->with('success', 'Materi berhasil ditandai sebagai sudah dibaca.');
}


    /* =====================================================
     * START EXAM (PER MATERI)
     * ===================================================== */
    public function startExam($materiId)
    {
        $pelajar = Auth::user();
        $materi = Materi::findOrFail($materiId);

        if ($pelajar->role === 'pelajar') {
            $allowed = MateriPelajar::where([
                'pelajar_id' => $pelajar->id,
                'materi_id'  => $materi->id,
                'status'     => 'completed'
            ])->exists();

            if (! $allowed) {
                return redirect()->route('student.index')
                    ->with('error', 'Anda harus membaca materi terlebih dahulu.');
            }
        }

        $attempt = Attempt::create([
            'pelajar_id' => $pelajar->id,
            'materi_id'  => $materi->id,
            'started_at' => Carbon::now(),
            'score'      => null
        ]);

        $questions = Soal::where('materi_id', $materi->id)
            ->inRandomOrder()
            ->limit(10)
            ->get();

        foreach ($questions as $q) {
            AttemptAnswer::create([
                'attempt_id' => $attempt->id,
                'soal_id'    => $q->id,
                'answer'     => null,
                'is_correct' => false // default false, bukan null
            ]);
        }

        return redirect()->route('student.quiz.show', $attempt->id);
    }

    /* =====================================================
     * SHOW QUIZ
     * ===================================================== */
    public function showQuiz($attemptId)
    {
        $attempt = Attempt::with('answers.soal')->findOrFail($attemptId);

        abort_if($attempt->pelajar_id !== Auth::id(), 403);

        $limitSeconds = 50 * 60;
        $elapsed = Carbon::now()->diffInSeconds($attempt->started_at);
        $remaining = max(0, $limitSeconds - $elapsed);

        return view('student.quiz', compact('attempt', 'remaining'));
    }

    /* =====================================================
    * SUBMIT QUIZ
    * ===================================================== */
    public function submitQuiz(Request $request, $attemptId)
    {
        $attempt = Attempt::with('answers.soal')->findOrFail($attemptId);

        abort_if($attempt->pelajar_id !== Auth::id(), 403);

        $answersInput = $request->input('answers', []);

        foreach ($attempt->answers as $answer) {
            $soal  = $answer->soal;
            $given = $answersInput[$soal->id] ?? null;

            if ($soal->type === 'mcq') {
                $answer->update([
                    'answer'     => $given,
                    'is_correct' => $this->checkMcq($soal, $given) ?? false
                ]);
            } else {
                // Essay, set default false sementara
                $answer->update([
                    'answer'     => $given,
                    'is_correct' => false
                ]);
            }
        }

        $attempt->update([
            'finished_at'      => now(),
            'duration_seconds' => now()->diffInSeconds($attempt->started_at),
            'score'            => null // default 0
        ]);

        return redirect()
            ->route('student.attempt.result', $attempt->id)
            ->with('success', 'Jawaban berhasil dikirim. Menunggu penilaian guru.');
    }


    /* =====================================================
     * MCQ CHECKER
     * ===================================================== */
    private function checkMcq($soal, $given)
    {
        if ($given === null) return false;
        return strtolower(trim($given)) === strtolower(trim($soal->jawaban_benar));
    }

/* =====================================================
 * RESULT
 * ===================================================== */
public function showResult($attemptId)
{
    $attempt = Attempt::with(['answers.soal'])->findOrFail($attemptId);

    // Hanya pemilik attempt yang boleh melihat
    abort_if($attempt->pelajar_id !== Auth::id(), 403);

    return view('student.result', compact('attempt'));
}


    /* =====================================================
     * HISTORY
     * ===================================================== */
    public function history()
    {
        $attempts = Attempt::where('pelajar_id', Auth::id())
            ->latest()
            ->paginate(12);

        return view('student.history', compact('attempts'));
    }

    /* =====================================================
     * LEADERBOARD (HANYA NILAI FINAL)
     * ===================================================== */
    public function leaderboard()
    {
        $top = Attempt::whereNotNull('score')
            ->select('pelajar_id')
            ->selectRaw('MAX(score) as max_score')
            ->groupBy('pelajar_id')
            ->orderByDesc('max_score')
            ->with('pelajar')
            ->get();

        return view('student.leaderboard', compact('top'));
    }
}
