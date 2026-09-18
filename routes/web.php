<?php

use App\Http\Controllers\AdaptiveLearningController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AiSettingsController;
use App\Http\Controllers\Admin\AiUsageController;
use App\Http\Controllers\Admin\AiOrchestratorController;
use App\Http\Controllers\Admin\MailSettingsController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SocialiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');
Route::get('/perfil/{user}', [AdaptiveLearningController::class, 'publicProfile'])->name('adaptive.public-profile');

Route::get('/dashboard', function () {
    if (auth()->user()->hasRole('Admin')) {
        return redirect()->route('admin.dashboard');
    }

    if (auth()->user()->hasRole('Instructor')) {
        return redirect()->route('mentor.dashboard');
    }

    return redirect()->route('adaptive.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect'])->name('socialite.redirect');
Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback']);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/mi-panel', [AdaptiveLearningController::class, 'dashboard'])->name('adaptive.dashboard');
    Route::get('/diagnostico', [AdaptiveLearningController::class, 'diagnostic'])->name('adaptive.diagnostic');
    Route::post('/diagnostico/iniciar', [AdaptiveLearningController::class, 'startDiagnostic'])->middleware('throttle:ai')->name('adaptive.diagnostic.start');
    Route::get('/diagnostico/{attempt}/preguntas', [AdaptiveLearningController::class, 'diagnosticQuestions'])->name('adaptive.diagnostic.questions');
    Route::post('/diagnostico/{attempt}/finalizar', [AdaptiveLearningController::class, 'storeDiagnostic'])->middleware('throttle:ai')->name('adaptive.diagnostic.store');
    Route::get('/diagnostico/{attempt}/generando', [AdaptiveLearningController::class, 'diagnosticGeneration'])->name('adaptive.diagnostic.generation');
    Route::post('/diagnostico/{attempt}/reintentar', [AdaptiveLearningController::class, 'retryDiagnosticGeneration'])->middleware('throttle:ai')->name('adaptive.diagnostic.retry');
    Route::get('/mi-ruta', [AdaptiveLearningController::class, 'path'])->name('adaptive.path');
    Route::get('/mi-ruta/fases/{item}', [AdaptiveLearningController::class, 'showPathItem'])->name('adaptive.path.item.show');
    Route::post('/mi-ruta/fases/{item}/quiz', [AdaptiveLearningController::class, 'submitPathItemQuiz'])->name('adaptive.path.item.quiz');
    Route::get('/mi-ruta/temas/{topic}', [AdaptiveLearningController::class, 'showTopic'])->name('adaptive.topic.show');
    Route::post('/mi-ruta/temas/{topic}/leccion', [AdaptiveLearningController::class, 'generateTopicLesson'])->middleware('throttle:ai')->name('adaptive.topic.lesson');
    Route::post('/mi-ruta/temas/{topic}/practica', [AdaptiveLearningController::class, 'generateTopicPractice'])->middleware('throttle:ai')->name('adaptive.topic.practice');
    Route::post('/mi-ruta/temas/{topic}/practica/entregar', [AdaptiveLearningController::class, 'submitTopicPractice'])->name('adaptive.topic.practice.submit');
    Route::get('/mi-ruta/temas/{topic}/practica/descargar', [AdaptiveLearningController::class, 'downloadTopicPractice'])->name('adaptive.topic.practice.download');
    Route::get('/tutor-ia', [AdaptiveLearningController::class, 'tutor'])->name('adaptive.tutor');
    Route::post('/tutor-ia/mensajes', [AdaptiveLearningController::class, 'sendTutorMessage'])->middleware('throttle:ai')->name('adaptive.tutor.message');
    Route::get('/retos', [AdaptiveLearningController::class, 'challenges'])->name('adaptive.challenges');
    Route::get('/retos/{challenge}', [AdaptiveLearningController::class, 'showChallenge'])->name('adaptive.challenges.show');
    Route::post('/retos/{challenge}', [AdaptiveLearningController::class, 'submitChallenge'])->middleware('throttle:ai')->name('adaptive.challenges.submit');
    Route::get('/retos/{challenge}/material/{resource}', [AdaptiveLearningController::class, 'downloadChallengeResource'])->whereNumber('resource')->name('adaptive.challenges.resource');
    Route::get('/retos/evidencias/{submission}/archivo', [AdaptiveLearningController::class, 'downloadChallengeAttachment'])->name('adaptive.challenges.attachment');
    Route::get('/mentorias', [AdaptiveLearningController::class, 'mentorship'])->name('adaptive.mentorship');
    Route::post('/mentorias/{mentor}/solicitar', [AdaptiveLearningController::class, 'requestMentorship'])->name('adaptive.mentorship.request');
    Route::get('/perfil-profesional', [AdaptiveLearningController::class, 'career'])->name('adaptive.career');
    Route::patch('/perfil-profesional', [AdaptiveLearningController::class, 'updateCareer'])->name('adaptive.career.update');
});

Route::middleware(['auth', 'verified', 'role:Instructor|Admin'])->group(function () {
    Route::get('/mentor', function () {
        $mentor = auth()->user();
        $sessions = \App\Models\MentorshipSession::where('mentor_id', $mentor->id)->with('student')->latest()->get();

        return view('mentor.dashboard', compact('sessions'));
    })->name('mentor.dashboard');
});

Route::prefix('admin')->as('admin.')->middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::resource('users', AdminUserController::class);
    Route::get('roles', [AdminRoleController::class, 'index'])->name('roles.index');
    Route::get('configuracion-ia', [AiSettingsController::class, 'edit'])->name('ai-settings.edit');
    Route::put('configuracion-ia', [AiSettingsController::class, 'update'])->name('ai-settings.update');
    Route::get('consumo-ia', [AiUsageController::class, 'index'])->name('ai-usage.index');
    Route::get('orquestador-ia', [AiOrchestratorController::class, 'index'])->name('ai-orchestrator.index');
    Route::get('configuracion-correo', [MailSettingsController::class, 'edit'])->name('mail-settings.edit');
    Route::put('configuracion-correo', [MailSettingsController::class, 'update'])->name('mail-settings.update');
    Route::post('configuracion-correo/probar', [MailSettingsController::class, 'sendTest'])->name('mail-settings.test');});