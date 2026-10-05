<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AiOrchestratorController;
use App\Http\Controllers\Admin\AiSettingsController;
use App\Http\Controllers\Admin\AiUsageController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\MailSettingsController;
use App\Http\Controllers\Admin\MentorApplicationController as AdminMentorApplicationController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Learning\CareerProfileController;
use App\Http\Controllers\Learning\ChallengeController;
use App\Http\Controllers\Learning\DashboardController as LearningDashboardController;
use App\Http\Controllers\Learning\DiagnosticController;
use App\Http\Controllers\Learning\LearningPathController;
use App\Http\Controllers\Learning\MentorshipController as StudentMentorshipController;
use App\Http\Controllers\Learning\TopicController;
use App\Http\Controllers\Learning\TutorController;
use App\Http\Controllers\MentorApplicationController;
use App\Http\Controllers\MentorController;
use App\Http\Controllers\MentorshipConversationController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\PlatformGuideController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SocialiteController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');
Route::view('/terminos-y-condiciones', 'legal.terms')->name('legal.terms');
Route::view('/politica-de-privacidad', 'legal.privacy')->name('legal.privacy');
Route::view('/politica-de-cookies', 'legal.cookies')->name('legal.cookies');
Route::view('/preguntas-frecuentes', 'legal.faq')->name('legal.faq');
Route::get('/perfil/{slug}', [CareerProfileController::class, 'show'])->where('slug', '[A-Za-z0-9]{24}')->name('adaptive.public-profile');

Route::get('/dashboard', function () {
    $space = session('active_space');
    if ($space === 'admin' && auth()->user()->hasRole('Admin')) {
        return redirect()->route('admin.dashboard');
    }

    if ($space === 'mentor' && (auth()->user()->hasRole('Instructor') || auth()->user()->hasRole('Admin'))) {
        return redirect()->route('mentor.dashboard');
    }

    if (! $space && auth()->user()->hasRole('Admin')) {
        return redirect()->route('admin.dashboard');
    }
    if (! $space && auth()->user()->hasRole('Instructor')) {
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
    Route::post('/espacio/cambiar', [WorkspaceController::class, 'switch'])->name('workspace.switch');
    Route::get('/mi-panel', LearningDashboardController::class)->name('adaptive.dashboard');
    Route::get('/mi-panel/estado', [LearningDashboardController::class, 'status'])->name('adaptive.dashboard.status');
    Route::get('/diagnostico', [DiagnosticController::class, 'show'])->name('adaptive.diagnostic');
    Route::post('/diagnostico/iniciar', [DiagnosticController::class, 'start'])->middleware('throttle:ai-generation')->name('adaptive.diagnostic.start');
    Route::get('/diagnostico/{attempt}/preguntas', [DiagnosticController::class, 'questions'])->name('adaptive.diagnostic.questions');
    Route::post('/diagnostico/{attempt}/finalizar', [DiagnosticController::class, 'store'])->middleware('throttle:ai-generation')->name('adaptive.diagnostic.store');
    Route::get('/diagnostico/{attempt}/generando', [DiagnosticController::class, 'generation'])->name('adaptive.diagnostic.generation');
    Route::post('/diagnostico/{attempt}/reintentar', [DiagnosticController::class, 'retry'])->middleware('throttle:ai-generation')->name('adaptive.diagnostic.retry');
    Route::get('/mi-ruta', [LearningPathController::class, 'show'])->name('adaptive.path');
    Route::get('/mi-ruta/fases/{item}', [LearningPathController::class, 'showItem'])->name('adaptive.path.item.show');
    Route::post('/mi-ruta/fases/{item}/quiz', [LearningPathController::class, 'submitQuiz'])->name('adaptive.path.item.quiz');
    Route::get('/mi-ruta/temas/{topic}', [TopicController::class, 'show'])->name('adaptive.topic.show');
    Route::post('/mi-ruta/temas/{topic}/leccion', [TopicController::class, 'generateLesson'])->middleware('throttle:ai-generation')->name('adaptive.topic.lesson');
    Route::post('/mi-ruta/temas/{topic}/practica', [TopicController::class, 'generatePractice'])->middleware('throttle:ai-generation')->name('adaptive.topic.practice');
    Route::get('/mi-ruta/temas/{topic}/generacion', [TopicController::class, 'generationStatus'])->name('adaptive.topic.generation.status');
    Route::post('/mi-ruta/temas/{topic}/practica/entregar', [TopicController::class, 'submitPractice'])->middleware('throttle:ai-generation')->name('adaptive.topic.practice.submit');
    Route::get('/mi-ruta/temas/{topic}/practica/descargar', [TopicController::class, 'downloadPractice'])->name('adaptive.topic.practice.download');
    Route::get('/tutor-ia', [TutorController::class, 'show'])->name('adaptive.tutor');
    Route::post('/tutor-ia/mensajes', [TutorController::class, 'sendMessage'])->middleware('throttle:ai-tutor')->name('adaptive.tutor.message');
    Route::get('/retos', [ChallengeController::class, 'index'])->name('adaptive.challenges');
    Route::get('/retos/{challenge}', [ChallengeController::class, 'show'])->name('adaptive.challenges.show');
    Route::post('/retos/{challenge}', [ChallengeController::class, 'submit'])->middleware('throttle:ai-generation')->name('adaptive.challenges.submit');
    Route::get('/retos/{challenge}/material/{resource}', [ChallengeController::class, 'downloadResource'])->whereNumber('resource')->name('adaptive.challenges.resource');
    Route::get('/retos/evidencias/{submission}/archivo', [ChallengeController::class, 'downloadAttachment'])->name('adaptive.challenges.attachment');
    Route::get('/mentorias', [StudentMentorshipController::class, 'index'])->name('adaptive.mentorship');
    Route::post('/mentorias/{mentor}/solicitar', [StudentMentorshipController::class, 'store'])->middleware('throttle:10,1')->name('adaptive.mentorship.request');
    Route::get('/mentorias/sesiones/{session}', [MentorshipConversationController::class, 'show'])->name('mentorship.sessions.show');
    Route::post('/mentorias/sesiones/{session}/mensajes', [MentorshipConversationController::class, 'send'])->name('mentorship.sessions.messages.send');
    Route::get('/mentorias/sesiones/{session}/mensajes', [MentorshipConversationController::class, 'messages'])->name('mentorship.sessions.messages');
    Route::get('/mentorias/widget', [MentorshipConversationController::class, 'widget'])->name('mentorship.widget');
    Route::post('/mentorias/widget/{session}/leer', [MentorshipConversationController::class, 'markWidgetRead'])->name('mentorship.widget.read');
    Route::post('/mentorias/sesiones/{session}/cancelar', [MentorController::class, 'cancel'])->name('mentor.sessions.cancel');
    Route::get('/notificaciones', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::post('/guia-plataforma/preguntar', [PlatformGuideController::class, 'ask'])->middleware('throttle:ai-tutor')->name('platform.guide.ask');
    Route::patch('/notificaciones/preferencias', [NotificationsController::class, 'updatePreferences'])->name('notifications.preferences.update');
    Route::post('/notificaciones/{notification}/leer', [NotificationsController::class, 'markRead'])->name('notifications.read');
    Route::post('/notificaciones/leer-todas', [NotificationsController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/perfil-profesional', [CareerProfileController::class, 'edit'])->name('adaptive.career');
    Route::patch('/perfil-profesional', [CareerProfileController::class, 'update'])->name('adaptive.career.update');
    Route::post('/perfil-profesional/enlace', [CareerProfileController::class, 'regeneratePublicLink'])->middleware('throttle:10,1')->name('adaptive.career.public-link');
    Route::get('/solicitar-ser-mentor', [MentorApplicationController::class, 'create'])->name('mentor-application.create');
    Route::post('/solicitar-ser-mentor/cuestionario', [MentorApplicationController::class, 'generateQuestionnaire'])->middleware('throttle:ai-generation')->name('mentor-application.questionnaire');
    Route::post('/solicitar-ser-mentor', [MentorApplicationController::class, 'store'])->name('mentor-application.store');
});

Route::middleware(['auth', 'verified', 'role:Instructor|Admin'])->group(function () {
    Route::get('/mentor', [MentorController::class, 'dashboard'])->name('mentor.dashboard');
    Route::get('/mentor/calendario', [MentorController::class, 'calendar'])->name('mentor.calendar');
    Route::get('/mentor/sesiones', [MentorController::class, 'sessions'])->name('mentor.sessions');
    Route::get('/mentor/configuracion', [MentorController::class, 'settings'])->name('mentor.settings');
    Route::patch('/mentor/configuracion', [MentorController::class, 'updateSettings'])->name('mentor.settings.update');
    Route::get('/mentor/solicitudes/{session}', [MentorshipConversationController::class, 'show'])->name('mentor.sessions.show');
    Route::post('/mentor/solicitudes/{session}/aceptar', [MentorController::class, 'accept'])->name('mentor.sessions.accept');
    Route::post('/mentor/solicitudes/{session}/rechazar', [MentorController::class, 'reject'])->name('mentor.sessions.reject');
});

Route::prefix('admin')->as('admin.')->middleware(['auth', 'verified', 'role:Admin'])->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::resource('users', AdminUserController::class);
    Route::get('roles', [AdminRoleController::class, 'index'])->name('roles.index');
    Route::get('configuracion-ia', [AiSettingsController::class, 'edit'])->name('ai-settings.edit');
    Route::put('configuracion-ia', [AiSettingsController::class, 'update'])->name('ai-settings.update');
    Route::get('consumo-ia', [AiUsageController::class, 'index'])->name('ai-usage.index');
    Route::get('orquestador-ia', [AiOrchestratorController::class, 'index'])->name('ai-orchestrator.index');
    Route::put('orquestador-ia/{agent}', [AiOrchestratorController::class, 'update'])->name('ai-orchestrator.update');
    Route::delete('orquestador-ia/{agent}', [AiOrchestratorController::class, 'reset'])->name('ai-orchestrator.reset');
    Route::get('configuracion-correo', [MailSettingsController::class, 'edit'])->name('mail-settings.edit');
    Route::put('configuracion-correo', [MailSettingsController::class, 'update'])->name('mail-settings.update');
    Route::post('configuracion-correo/probar', [MailSettingsController::class, 'sendTest'])->name('mail-settings.test');
    Route::get('avisos', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('avisos', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::post('avisos/{announcement}/publicar', [AnnouncementController::class, 'publish'])->name('announcements.publish');
    Route::delete('avisos/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
    Route::get('solicitudes-mentor', [AdminMentorApplicationController::class, 'index'])->name('mentor-applications.index');
    Route::get('solicitudes-mentor/{application}', [AdminMentorApplicationController::class, 'show'])->name('mentor-applications.show');
    Route::post('solicitudes-mentor/{application}/aprobar', [AdminMentorApplicationController::class, 'approve'])->name('mentor-applications.approve');
    Route::post('solicitudes-mentor/{application}/rechazar', [AdminMentorApplicationController::class, 'reject'])->name('mentor-applications.reject');
});
