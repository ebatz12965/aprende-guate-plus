<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SocialiteController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\ModuleController as AdminModuleController;
use App\Http\Controllers\Admin\ClassController as AdminClassController;
use App\Http\Controllers\Admin\TaskController as AdminTaskController;
use App\Http\Controllers\Admin\QuizController as AdminQuizController;
use App\Http\Controllers\Admin\QuestionController as AdminQuestionController;
use App\Http\Controllers\Admin\SubmissionController as AdminSubmissionController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\LevelController as AdminLevelController;
use App\Http\Controllers\Admin\AiSettingsController;
use App\Http\Controllers\Admin\MailSettingsController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\AdaptiveLearningController;

/*
|--------------------------------------------------------------------------
| Rutas Públicas
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/cursos', [SearchController::class, 'courses'])->name('public.courses');
Route::get('/curso/{course:slug}', [SearchController::class, 'courseDetail'])->name('public.course.detail');
Route::get('/instructores', [SearchController::class, 'instructors'])->name('public.instructors');
Route::get('/instructor/{user}', [SearchController::class, 'instructorProfile'])->name('public.instructor.profile');
Route::get('/perfil/{user}', [AdaptiveLearningController::class, 'publicProfile'])->name('adaptive.public-profile');
Route::get('/certificado/{certificate:code}', [CertificateController::class, 'show'])->name('certificate.show');

/*
|--------------------------------------------------------------------------
| Rutas de Autenticación y Perfil
|--------------------------------------------------------------------------
*/
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

// Rutas de Socialite
Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect'])->name('socialite.redirect');
Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback']);

/*
|--------------------------------------------------------------------------
| Rutas de Estudiante
|--------------------------------------------------------------------------
*/
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
    Route::post('/retos/{challenge}', [AdaptiveLearningController::class, 'submitChallenge'])->name('adaptive.challenges.submit');
    Route::get('/mentorias', [AdaptiveLearningController::class, 'mentorship'])->name('adaptive.mentorship');
    Route::post('/mentorias/{mentor}/solicitar', [AdaptiveLearningController::class, 'requestMentorship'])->name('adaptive.mentorship.request');
    Route::get('/perfil-profesional', [AdaptiveLearningController::class, 'career'])->name('adaptive.career');
    Route::patch('/perfil-profesional', [AdaptiveLearningController::class, 'updateCareer'])->name('adaptive.career.update');
    Route::get('/mis-cursos', [StudentController::class, 'myCourses'])->name('student.courses');
    Route::get('/cursos/{course}/estudiar', [StudentController::class, 'studyCourse'])->name('student.course.study');
    Route::post('/curso/{course}/inscribir', [EnrollmentController::class, 'enroll'])->name('student.course.enroll');
    Route::get('/tarea/{task}', [StudentController::class, 'viewTask'])->name('student.task.view');
    Route::post('/tarea/{task}', [StudentController::class, 'submitTask'])->name('student.task.submit');
    Route::get('/mis-calificaciones', [StudentController::class, 'myGrades'])->name('student.grades');
    Route::post('/clase/{class}/completar', [ProgressController::class, 'markAsComplete'])->name('student.class.complete');
    Route::get('/cuestionario/{quiz}', [StudentController::class, 'takeQuiz'])->name('student.quiz.take');
    Route::post('/cuestionario/{quiz}', [StudentController::class, 'submitQuiz'])->name('student.quiz.submit');
    Route::get('/mis-certificados', [StudentController::class, 'myCertificates'])->name('student.certificates');
});

Route::middleware(['auth', 'verified', 'role:Instructor|Admin'])->group(function () {
    Route::get('/mentor', function () {
        $mentor = auth()->user();
        $sessions = \App\Models\MentorshipSession::where('mentor_id', $mentor->id)->with('student')->latest()->get();
        return view('mentor.dashboard', compact('sessions'));
    })->name('mentor.dashboard');
});

/*
|--------------------------------------------------------------------------
| Rutas de Administración
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => ['auth', 'role:Admin']], function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

    // Rutas de Gestión
    Route::resource('users', AdminUserController::class);
    Route::resource('roles', AdminRoleController::class);
    Route::resource('categories', AdminCategoryController::class);
    Route::resource('levels', AdminLevelController::class);
    Route::get('configuracion-ia', [AiSettingsController::class, 'edit'])->name('ai-settings.edit');
    Route::put('configuracion-ia', [AiSettingsController::class, 'update'])->name('ai-settings.update');
    Route::get('configuracion-correo', [MailSettingsController::class, 'edit'])->name('mail-settings.edit');
    Route::put('configuracion-correo', [MailSettingsController::class, 'update'])->name('mail-settings.update');
    Route::post('configuracion-correo/probar', [MailSettingsController::class, 'sendTest'])->name('mail-settings.test');
    Route::get('/permissions', [PermissionController::class, 'permissions'])->name('permissions.index');
    Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

    // Rutas de Contenido del Curso
    // La gestión de cursos, módulos y clases se delega también a Instructores.
    Route::resource('classes.tasks', AdminTaskController::class)->shallow();
    Route::resource('classes.quizzes', AdminQuizController::class)->shallow();
    Route::get('quizzes/{quiz}/attempts', [AdminQuizController::class, 'viewAttempts'])->name('quizzes.attempts');
    Route::resource('quizzes.questions', AdminQuestionController::class)->shallow();
    Route::resource('submissions', AdminSubmissionController::class)->only(['edit', 'update'])->shallow();

    // Rutas para Inscripciones
    Route::post('courses/{course}/enrollments', [EnrollmentController::class, 'store'])->name('enrollments.store');
    Route::delete('courses/{course}/enrollments/{user}', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');
});

/*
|--------------------------------------------------------------------------
| Gestión de contenido: administración e instructores
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => ['auth', 'role:Admin|Instructor']], function () {
    Route::resource('courses', AdminCourseController::class);
    Route::post('courses/{course}/status', [AdminCourseController::class, 'toggleStatus'])->name('courses.toggleStatus');
    Route::resource('courses.modules', AdminModuleController::class)->shallow();
    Route::resource('modules.classes', AdminClassController::class)->shallow();
    Route::get('classes/{class}/video-status', [AdminClassController::class, 'videoStatus'])->name('classes.video-status');
});
