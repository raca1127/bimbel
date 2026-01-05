<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\MateriController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicController;


Route::get('/', function () {
    return view('welcome');
});

// Export template CSV for materi import
Route::get('/guru/materi/template', function() {
    $path = resource_path('imports/materi_soal_template.csv');
    return response()->download($path, 'materi_soal_template.csv', [
        'Content-Type' => 'text/csv',
    ]);
})->name('teacher.materi.template');

// Public materials (no auth)
Route::get('/materi', [PublicController::class, 'materials'])->name('public.materi');
Route::get('/materi/{id}', [PublicController::class, 'showMaterial'])->name('public.materi.show');

Route::get('/student/materi/{materi}', [MateriController::class, 'show'])
    ->name('student.materi.show');


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

// Rute untuk guru — memakai controller MateriController
Route::middleware('auth')->group(function () {
    Route::get('/guru/materi', [MateriController::class, 'index'])->name('teacher.materi.index');
    Route::post('/guru/materi', [MateriController::class, 'store'])->name('teacher.materi.store');
    Route::get('/guru/materi/import', [MateriController::class, 'importForm'])->name('teacher.materi.import');
    Route::post('/guru/materi/import', [MateriController::class, 'importCsv'])->name('teacher.materi.import.post');
    Route::delete('/guru/materi/{id}', [MateriController::class, 'destroy'])->name('teacher.materi.destroy');
    Route::get('/guru/materi/{id}/edit', [MateriController::class, 'edit'])->name('teacher.materi.edit');
    Route::post('/guru/materi/{id}', [MateriController::class, 'update'])->name('teacher.materi.update');
    Route::get('/guru/attempts', [MateriController::class, 'attempts'])->name('teacher.attempts');
    Route::get('/guru/attempt/{id}/grade', [MateriController::class, 'gradeAttempt'])->name('teacher.attempt.grade');
    Route::post('/guru/attempt/{id}/grade', [MateriController::class, 'saveGrading'])->name('teacher.attempt.grade.save');
    Route::get('/guru/attempts', [MateriController::class, 'attempts'])->name('teacher.attempts');

});

// Admin routes
Route::middleware(['auth','role:admin'])->prefix('admin')->group(function () {
    Route::get('/', [App\Http\Controllers\AdminController::class, 'index'])->name('admin.index');
    Route::post('/approve-guru/{id}', [App\Http\Controllers\AdminController::class, 'approveGuru'])->name('admin.approve_guru');
    Route::post('/reject-guru/{id}', [App\Http\Controllers\AdminController::class, 'rejectGuru'])->name('admin.reject_guru');
    Route::post('/block-user/{id}', [App\Http\Controllers\AdminController::class, 'blockUser'])->name('admin.block_user');
    Route::post('/unblock-user/{id}', [App\Http\Controllers\AdminController::class, 'unblockUser'])->name('admin.unblock_user');
    Route::post('/takedown-materi/{id}', [App\Http\Controllers\AdminController::class, 'takedownMateri'])->name('admin.takedown_materi');
    // additional admin pages
    Route::get('/users', [App\Http\Controllers\AdminController::class, 'users'])->name('admin.users');
    Route::get('/users/{id}', [App\Http\Controllers\AdminController::class, 'showUser'])->name('admin.user.show');
    Route::get('/materi', [App\Http\Controllers\AdminController::class, 'materiIndex'])->name('admin.materi.index');
    Route::get('/materi/{id}', [App\Http\Controllers\AdminController::class, 'showMateri'])->name('admin.materi.show');
});

// Rute untuk pelajar
Route::middleware('auth')->group(function () {
    Route::get('/pelajar', [StudentController::class, 'index'])->name('student.index');
    // Alias for dashboard switch (guru ke pelajar)
    Route::get('/dashboard/pelajar', [StudentController::class, 'index'])->name('student.dashboard');
    Route::post('/pelajar/materi/{id}/read', [StudentController::class, 'readMateri'])->name('student.read');
    Route::get('/pelajar/soal', [StudentController::class, 'viewSoal'])->name('student.soal');
    // Quiz flow
    Route::post('/pelajar/quiz/start', [StudentController::class, 'startQuiz'])->name('student.quiz.start');
    // start final exam for a specific materi
    Route::post('/pelajar/materi/{id}/exam/start', [StudentController::class, 'startExam'])->name('student.exam.start');
    // bookmark toggle (pelajar or guru can bookmark)
    Route::post('/pelajar/materi/{id}/bookmark', [StudentController::class, 'toggleBookmark'])->name('student.bookmark.toggle');
    Route::get('/pelajar/quiz/{attempt}', [StudentController::class, 'showQuiz'])->name('student.quiz.show');
    Route::post('/pelajar/quiz/{attempt}/submit', [StudentController::class, 'submitQuiz'])->name('student.quiz.submit');
    Route::get('/pelajar/attempt/{attempt}/result', [StudentController::class, 'showResult'])->name('student.attempt.result');
    Route::get('/pelajar/leaderboard', [StudentController::class, 'leaderboard'])->name('student.leaderboard');
    Route::get('/pelajar/history', [StudentController::class, 'history'])->name('student.history');
});

