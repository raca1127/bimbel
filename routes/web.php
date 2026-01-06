<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\MateriController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\AdminController;

Route::get('/', function () {
    return view('welcome');
});

// Export template CSV for materi import (auth guru)
Route::middleware('auth')->get('/guru/materi/template', function() {
    $path = resource_path('imports/materi_soal_template.csv');
    if (!file_exists($path)) {
        abort(404, 'Template CSV tidak ditemukan.');
    }
    return response()->download($path, 'materi_soal_template.csv', [
        'Content-Type' => 'text/csv',
    ]);
})->name('teacher.materi.template');

// Public materials (no auth)
Route::get('/materi', [PublicController::class, 'materials'])->name('public.materi');
Route::get('/materi/{id}', [PublicController::class, 'showMaterial'])->name('public.materi.show');

// Become guru request (auth required)
Route::middleware('auth')->group(function(){
    Route::get('/become-guru', [PublicController::class, 'showBecomeGuru'])->name('user.become_guru');
    Route::post('/become-guru', [PublicController::class, 'submitBecomeGuru'])->name('user.become_guru.submit');
});

// Simple auth pages
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Routes for guru (teacher)
Route::middleware('auth')->prefix('guru')->group(function () {
    Route::get('materi', [MateriController::class, 'index'])->name('teacher.materi.index');
    Route::post('materi', [MateriController::class, 'store'])->name('teacher.materi.store');
    Route::get('materi/import', [MateriController::class, 'importForm'])->name('teacher.materi.import');
    Route::post('materi/import', [MateriController::class, 'importCsv'])->name('teacher.materi.import.post');
    Route::delete('materi/{id}', [MateriController::class, 'destroy'])->name('teacher.materi.destroy');
    Route::get('materi/{id}/edit', [MateriController::class, 'edit'])->name('teacher.materi.edit');
    Route::put('materi/{id}', [MateriController::class, 'update'])->name('teacher.materi.update');

    // Attempts
    Route::get('attempts', [MateriController::class, 'attempts'])->name('teacher.attempts');
    Route::get('attempt/{id}/grade', [MateriController::class, 'gradeAttempt'])->name('teacher.attempt.grade');
    Route::post('attempt/{id}/grade', [MateriController::class, 'saveGrading'])->name('teacher.attempt.grade.save');
});


// Routes for admin
Route::middleware(['auth','role:admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.index');
    Route::post('/approve-guru/{id}', [AdminController::class, 'approveGuru'])->name('admin.approve_guru');
    Route::post('/reject-guru/{id}', [AdminController::class, 'rejectGuru'])->name('admin.reject_guru');
    Route::post('/block-user/{id}', [AdminController::class, 'blockUser'])->name('admin.block_user');
    Route::post('/unblock-user/{id}', [AdminController::class, 'unblockUser'])->name('admin.unblock_user');
    Route::post('/takedown-materi/{id}', [AdminController::class, 'takedownMateri'])->name('admin.takedown_materi');

    Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
    Route::get('/users/{id}', [AdminController::class, 'showUser'])->name('admin.user.show');
    Route::get('/materi', [AdminController::class, 'materiIndex'])->name('admin.materi.index');
    Route::get('/materi/{id}', [AdminController::class, 'showMateri'])->name('admin.materi.show');
});

// Routes for student (pelajar)
Route::middleware('auth')->prefix('pelajar')->group(function () {
    Route::get('/', [StudentController::class, 'index'])->name('student.index');
    Route::get('/dashboard', [StudentController::class, 'index'])->name('student.dashboard');

    Route::get('/materi/{materi}', [MateriController::class, 'show'])->name('student.materi.show');
    Route::post('/materi/{id}/read', [StudentController::class, 'readMateri'])->name('student.read');
    Route::post('/materi/{id}/exam/start', [StudentController::class, 'startExam'])->name('student.exam.start');
    Route::post('/materi/{id}/bookmark', [StudentController::class, 'toggleBookmark'])->name('student.bookmark.toggle');

    Route::get('/soal', [StudentController::class, 'viewSoal'])->name('student.soal');
    Route::post('/quiz/start', [StudentController::class, 'startQuiz'])->name('student.quiz.start');
    Route::get('/quiz/{attempt}', [StudentController::class, 'showQuiz'])->name('student.quiz.show');
    Route::post('/quiz/{attempt}/submit', [StudentController::class, 'submitQuiz'])->name('student.quiz.submit');

    Route::get('/attempt/{attempt}/result', [StudentController::class, 'showResult'])->name('student.attempt.result');
    Route::get('/leaderboard', [StudentController::class, 'leaderboard'])->name('student.leaderboard');
    Route::get('/history', [StudentController::class, 'history'])->name('student.history');
});
