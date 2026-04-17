<?php

use App\Http\Controllers\Public\TakerResultsController;
use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ImportExportController;
use App\Http\Controllers\Admin\LiveController;
use App\Http\Controllers\Admin\QuestionImageController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\QuizController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\AccessCodeController;
use App\Http\Controllers\Public\QuizLandingController;
use App\Http\Controllers\Public\QuizResumeController;
use App\Http\Controllers\Public\QuizTakeController;
use Illuminate\Support\Facades\Route;

// ─── Homepage ────────────────────────────────────────────────────────────────
Route::get('/', fn () => view('welcome'))->name('home');
Route::get('question-images/{path}', [QuestionImageController::class, 'show'])
    ->where('path', '.*')
    ->name('question-images.show');
Route::get('storage/question-images/{file}', [QuestionImageController::class, 'showLegacy'])
    ->where('file', '.*');

// ─── Admin area ─────────────────────────────────────────────────────────────
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/live', [LiveController::class, 'index'])->name('live');

    // Quizzes
    Route::resource('quizzes', QuizController::class);
    Route::post('question-images', [QuestionImageController::class, 'store'])->name('question-images.store');

    // Questions (nested under quiz)
    Route::prefix('quizzes/{quiz}')->name('quizzes.')->group(function () {
        Route::get('questions',                  [QuestionController::class, 'index'])->name('questions.index');
        Route::get('questions/create',           [QuestionController::class, 'create'])->name('questions.create');
        Route::post('questions',                 [QuestionController::class, 'store'])->name('questions.store');
        Route::get('questions/{question}/edit',  [QuestionController::class, 'edit'])->name('questions.edit');
        Route::put('questions/{question}',       [QuestionController::class, 'update'])->name('questions.update');
        Route::delete('questions/{question}',    [QuestionController::class, 'destroy'])->name('questions.destroy');
        Route::post('questions/reorder',         [QuestionController::class, 'reorder'])->name('questions.reorder');
        Route::patch('questions/{question}/toggle', [QuestionController::class, 'toggle'])->name('questions.toggle');
        Route::post('questions/{question}/copy', [QuestionController::class, 'copy'])->name('questions.copy');
        Route::get('questions/{question}/export', [ImportExportController::class, 'exportQuestion'])->name('questions.export');

        // Assignments (nested under quiz)
        Route::resource('assignments', AssignmentController::class)
            ->except(['show'])
            ->names('assignments');
        Route::get('assignments/{assignment}',   [AssignmentController::class, 'show'])->name('assignments.show');

        // Reports (nested under assignment)
        Route::get('assignments/{assignment}/report',
            [ReportController::class, 'index'])->name('assignments.report');
        Route::get('assignments/{assignment}/report/export',
            [ReportController::class, 'export'])->name('assignments.report.export');
        Route::get('assignments/{assignment}/report/{session}',
            [ReportController::class, 'show'])->name('assignments.report.session');
        Route::post('assignments/{assignment}/report/{session}/grade',
            [ReportController::class, 'grade'])->name('assignments.report.grade');
        Route::post('assignments/{assignment}/report/{session}/answers/{answer}/override',
            [ReportController::class, 'override'])->name('assignments.report.override');
    });

    // Import / Export
    Route::get('quizzes/{quiz}/export',  [ImportExportController::class, 'export'])->name('quizzes.export');
    Route::get('import',                 [ImportExportController::class, 'importForm'])->name('import');
    Route::post('import',                [ImportExportController::class, 'import'])->name('import.store');
});

// ─── Breeze profile routes ───────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ─── Public quiz routes ──────────────────────────────────────────────────────
Route::prefix('quiz')->name('quiz.')->group(function () {

    // Landing / Registration
    Route::get('{token}',        [QuizLandingController::class, 'show'])->name('show');
    Route::post('{token}',       [QuizLandingController::class, 'register'])->name('register');
    Route::post('{token}/request-code', [QuizLandingController::class, 'requestCode'])->name('request-code');
    Route::post('{token}/start', [QuizLandingController::class, 'start'])->name('start');

    // Access code verification
    Route::get('{token}/verify',  [AccessCodeController::class, 'showForm'])->name('verify');
    Route::post('{token}/verify', [AccessCodeController::class, 'verify'])->name('verify.submit');
    Route::post('{token}/resend', [AccessCodeController::class, 'resend'])->name('resend');

    // Taking the quiz (session ownership + expiry required)
    Route::middleware([
        \App\Http\Middleware\EnsureSessionOwnership::class,
        \App\Http\Middleware\EnsureSessionNotExpired::class,
    ])->group(function () {
        Route::get('session/{session}',           [QuizTakeController::class, 'show'])->name('take');
        Route::post('session/{session}/autosave', [QuizTakeController::class, 'autoSave'])->name('autosave');
        Route::post('session/{session}/submit',   [QuizTakeController::class, 'submit'])->name('submit');
    });

    // Resume via signed link (no ownership cookie required – it gets set here)
    Route::get('resume/{session}/{token}', [QuizResumeController::class, 'resume'])->name('resume');

    // Result page (public, session-based)
    Route::get('result/{session}', [QuizResumeController::class, 'result'])->name('result');
});

Route::redirect('q/{token}', '/quiz/{token}');
Route::redirect('q/{token}/verify', '/quiz/{token}/verify');

// ─── Taker results lookup ────────────────────────────────────────────────────
Route::get('/my-results',  [TakerResultsController::class, 'show'])->name('taker.results');
Route::post('/my-results', [TakerResultsController::class, 'request'])->name('taker.results.request');

require __DIR__.'/auth.php';
