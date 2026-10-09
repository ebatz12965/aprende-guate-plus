<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Jobs\EvaluateInterviewStageJob;
use App\Jobs\GenerateInterviewPlanJob;
use App\Jobs\GenerateInterviewQuestionsJob;
use App\Jobs\GenerateInterviewTestJob;
use App\Models\DiagnosticAttempt;
use App\Models\InterviewSimulation;
use App\Models\InterviewStage;
use App\Services\Interviews\InterviewConversation;
use App\Services\Interviews\PracticalTestService;
use App\Services\Interviews\ReasoningTestService;
use App\Services\Interviews\VoiceInterviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Simulador de postulación: crear un proceso de selección ficticio y recorrer sus etapas. */
class InterviewController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('interviews.index', [
            'simulations' => InterviewSimulation::where('user_id', $user->id)->withCount([
                'stages', 'stages as finished_stages_count' => fn ($query) => $query->whereIn('status', InterviewStage::FINISHED),
            ])->latest()->get(),
            'suggestedTitle' => $this->suggestedTitle($user->id),
            'remainingToday' => $this->remainingToday($user->id),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'job_title' => ['required', 'string', 'min:3', 'max:160'],
            'area' => ['required', Rule::in(array_keys(InterviewSimulation::AREAS))],
            'seniority' => ['required', Rule::in(array_keys(InterviewSimulation::SENIORITY))],
            'job_description' => ['nullable', 'string', 'max:6000'],
        ]);
        $userId = $request->user()->id;

        if ($this->remainingToday($userId) <= 0) {
            return back()->withInput()->with('error', 'Ya creaste las simulaciones disponibles por hoy. Mañana puedes crear otra; mientras tanto, continúa las que tienes.');
        }
        if (InterviewSimulation::where('user_id', $userId)->where('status', 'generating')->where('created_at', '>', now()->subMinutes(10))->exists()) {
            return back()->withInput()->with('error', 'Ya estamos preparando otra simulación. Espera a que termine para crear una nueva.');
        }

        $simulation = InterviewSimulation::create($data + ['user_id' => $userId, 'status' => 'generating']);
        GenerateInterviewPlanJob::dispatch($simulation->id)->onQueue('ai');

        return redirect()->route('adaptive.interviews.show', $simulation);
    }

    public function show(InterviewSimulation $simulation): View
    {
        $this->authorize('view', $simulation);
        $simulation->load('stages');

        return view('interviews.show', ['simulation' => $simulation]);
    }

    /** Estado para la pantalla de espera mientras se prepara el plan. */
    public function status(InterviewSimulation $simulation): JsonResponse
    {
        $this->authorize('view', $simulation);

        return response()->json(['status' => $simulation->status]);
    }

    public function retry(InterviewSimulation $simulation): RedirectResponse
    {
        $this->authorize('view', $simulation);
        abort_unless($simulation->status === 'failed', 422);
        $simulation->update(['status' => 'generating', 'generation_error' => null]);
        GenerateInterviewPlanJob::dispatch($simulation->id)->onQueue('ai');

        return redirect()->route('adaptive.interviews.show', $simulation);
    }

    public function stage(Request $request, InterviewSimulation $simulation, InterviewStage $stage, VoiceInterviewService $voice): View|RedirectResponse
    {
        $this->ensureStage($simulation, $stage);
        if ($stage->status === 'locked') {
            return redirect()->route('adaptive.interviews.show', $simulation)->with('info', 'Completa primero la etapa anterior.');
        }
        // El reloj de una prueba empieza cuando la persona la abre por primera vez, no cuando se generó.
        if ($stage->isTest() && $stage->status === 'in_progress' && $stage->started_at === null) {
            $stage->update(['started_at' => now()]);
        }
        $next = $simulation->stages()->where('position', $stage->position + 1)->first();

        return view('interviews.stage', [
            'simulation' => $simulation,
            'stage' => $stage,
            'turns' => $stage->turns->map(fn ($turn) => $turn->toChat())->values(),
            'next' => $next && $next->status !== 'locked' ? $next : null,
            // Durante una prueba con tiempo la sesión no se cierra por inactividad antes de su hora límite.
            'idleHoldUntil' => match (true) {
                $stage->isTest() && $stage->status === 'in_progress' => $stage->deadline()?->toIso8601String(),
                // En una llamada la persona habla sin tocar el teclado: no se cierra la sesión mientras dure.
                $stage->status === 'in_progress' && ($stage->content['mode'] ?? null) === 'voice' => now()->addSeconds((int) config('ai.voice.max_seconds') + 300)->toIso8601String(),
                default => null,
            },
            'voiceUnavailable' => $stage->isInterview() && $stage->status === 'available' ? $voice->unavailableReason($request->user(), $stage) : null,
            'voiceMaxMinutes' => (int) ceil(config('ai.voice.max_seconds') / 60),
            'voiceCredits' => $stage->isInterview() && $stage->status === 'available' ? $voice->credits($request->user()) : null,
        ]);
    }

    /** Comienza una etapa: en la cola se prepara el banco de preguntas o la prueba. Las entrevistas (RR. HH. y técnica) pueden ser por voz. */
    public function start(Request $request, InterviewSimulation $simulation, InterviewStage $stage, VoiceInterviewService $voice): RedirectResponse
    {
        $this->ensureStage($simulation, $stage);
        abort_unless($stage->status === 'available', 422);
        $mode = $request->input('mode') === 'voice' && $stage->isInterview() ? 'voice' : 'chat';
        if ($mode === 'voice' && ($reason = $voice->unavailableReason($request->user(), $stage))) {
            return back()->with('error', $reason);
        }
        // Empezar por voz gasta la entrevista por voz que da una ruta completada (salvo en las cuentas de prueba).
        $credit = $mode === 'voice' && $voice->useCredit($request->user());
        $stage->update(['status' => 'preparing', 'content' => $stage->isTest() ? null : ['mode' => $mode] + ($mode === 'voice' ? ['voice_credit' => $credit || $voice->unrestricted($request->user())] : [])]);
        $stage->isTest()
            ? GenerateInterviewTestJob::dispatch($stage->id)->onQueue('ai')
            : GenerateInterviewQuestionsJob::dispatch($stage->id)->onQueue('ai');

        return redirect()->route('adaptive.interviews.stage', [$simulation, $stage]);
    }

    /** Clave temporal para que el navegador se conecte a la llamada de voz. */
    public function voiceSession(Request $request, InterviewSimulation $simulation, InterviewStage $stage, VoiceInterviewService $voice): JsonResponse
    {
        $this->ensureVoiceCall($simulation, $stage);

        return response()->json($voice->createCall($request->user(), $stage));
    }

    /** Al colgar: transcripción y consumo de la llamada. */
    public function voiceFinish(Request $request, InterviewSimulation $simulation, InterviewStage $stage, VoiceInterviewService $voice): JsonResponse
    {
        $this->ensureVoiceCall($simulation, $stage);
        $data = $request->validate([
            'transcript' => ['required', 'array', 'max:60'],
            'transcript.*.role' => ['required', 'in:interviewer,candidate'],
            'transcript.*.text' => ['nullable', 'string', 'max:4000'],
            'usage' => ['array'],
            'usage.input_tokens' => ['nullable', 'integer', 'min:0'],
            'usage.output_tokens' => ['nullable', 'integer', 'min:0'],
            'seconds' => ['required', 'integer', 'min:0', 'max:3600'],
            'diagnostics' => ['nullable', 'array'],
        ]);
        $voice->finish($stage, $data['transcript'], $data['usage'] ?? [], $data['seconds'], $data['diagnostics'] ?? []);

        return response()->json(['redirect' => route('adaptive.interviews.stage', [$simulation, $stage])]);
    }

    /** Si la voz falla o la persona prefiere escribir, la misma entrevista sigue por chat. */
    public function switchToChat(Request $request, InterviewSimulation $simulation, InterviewStage $stage, InterviewConversation $conversation, VoiceInterviewService $voice): RedirectResponse
    {
        $this->ensureVoiceCall($simulation, $stage);
        $voice->refundCredit($request->user(), $stage);
        $stage->turns()->delete();
        $stage->update(['content' => ['mode' => 'chat', 'voice_credit' => false] + ($stage->content ?? [])]);
        $conversation->begin($stage->refresh());

        return redirect()->route('adaptive.interviews.stage', [$simulation, $stage]);
    }

    private function ensureVoiceCall(InterviewSimulation $simulation, InterviewStage $stage): void
    {
        $this->ensureStage($simulation, $stage);
        abort_unless($stage->isInterview() && $stage->status === 'in_progress' && ($stage->content['mode'] ?? null) === 'voice', 409, 'Esta entrevista ya no está en modo de voz.');
    }

    /** Estado para las pantallas de espera (preparando preguntas o evaluando). */
    public function stageStatus(InterviewSimulation $simulation, InterviewStage $stage): JsonResponse
    {
        $this->ensureStage($simulation, $stage);

        return response()->json(['status' => $stage->status, 'evaluation' => $stage->content['evaluation_status'] ?? null]);
    }

    public function answer(Request $request, InterviewSimulation $simulation, InterviewStage $stage, InterviewConversation $conversation): JsonResponse
    {
        $this->ensureStage($simulation, $stage);
        abort_if(($stage->content['mode'] ?? null) === 'voice', 409, 'Esta entrevista se está haciendo por voz.');
        $data = $request->validate(['answer' => ['required', 'string', 'min:2', 'max:'.InterviewConversation::MAX_ANSWER]]);
        $turns = $conversation->answer($stage, $data['answer']);
        $stage->refresh();

        return response()->json([
            'turns' => $turns,
            'status' => $stage->status,
            'question' => ($stage->content['current'] ?? 0) + 1,
            'total' => count($stage->content['questions'] ?? []),
        ]);
    }

    public function retryEvaluation(InterviewSimulation $simulation, InterviewStage $stage): RedirectResponse
    {
        $this->ensureStage($simulation, $stage);
        abort_unless($stage->status === 'submitted' && ($stage->content['evaluation_status'] ?? null) === 'failed', 422);
        $stage->update(['content' => ['evaluation_status' => 'queued'] + $stage->content]);
        EvaluateInterviewStageJob::dispatch($stage->id)->onQueue('ai');

        return redirect()->route('adaptive.interviews.stage', [$simulation, $stage]);
    }

    /** Entrega de una prueba. La de razonamiento se califica al instante; la práctica pasa al evaluador. */
    public function submitTest(Request $request, InterviewSimulation $simulation, InterviewStage $stage, ReasoningTestService $reasoning): RedirectResponse
    {
        $this->ensureStage($simulation, $stage);
        abort_unless($stage->isTest() && $stage->status === 'in_progress', 409, 'Esta prueba ya fue entregada.');
        // Margen de dos minutos por la conexión; después se acepta igual, pero queda marcada como fuera de tiempo.
        $late = $stage->deadline() !== null && now()->greaterThan($stage->deadline()->copy()->addMinutes(2));

        if ($stage->type === 'reasoning_test') {
            $data = $request->validate(['choices' => ['array'], 'choices.*' => ['nullable', 'integer', 'min:0', 'max:3']]);
            $grade = $reasoning->grade($stage->content['items'] ?? [], $data['choices'] ?? []);
            DB::transaction(function () use ($stage, $data, $grade, $late) {
                $stage->update(['answers' => ['choices' => $data['choices'] ?? [], 'late' => $late], 'status' => 'evaluated', 'score' => $grade['score'], 'evaluation' => $grade, 'submitted_at' => now(), 'evaluated_at' => now()]);
                $stage->finishAndUnlockNext();
            });

            return redirect()->route('adaptive.interviews.stage', [$simulation, $stage])->with('success', 'Prueba entregada.');
        }

        $data = $request->validate([
            'answer' => ['nullable', 'string', 'max:'.PracticalTestService::MAX_ANSWER, 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,txt,csv,xlsx,docx,pptx'],
        ], ['answer.required_without' => 'Escribe tu solución o adjunta un archivo.']);
        $answers = ['text' => $data['answer'] ?? '', 'late' => $late];
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $answers['file'] = ['path' => $file->store('interview-evidence/'.$request->user()->id, 'local'), 'name' => $file->getClientOriginalName(), 'mime' => $file->getMimeType(), 'size' => $file->getSize()];
        }
        DB::transaction(function () use ($stage, $answers) {
            $stage->update(['answers' => $answers, 'status' => 'submitted', 'submitted_at' => now(), 'content' => ['evaluation_status' => 'queued'] + ($stage->content ?? [])]);
            $stage->finishAndUnlockNext();
        });
        EvaluateInterviewStageJob::dispatch($stage->id)->onQueue('ai');

        return redirect()->route('adaptive.interviews.stage', [$simulation, $stage]);
    }

    /** Archivo entregado en la prueba práctica; solo para quien lo subió. */
    public function attachment(InterviewSimulation $simulation, InterviewStage $stage): StreamedResponse
    {
        $this->ensureStage($simulation, $stage);
        $file = $stage->answers['file'] ?? null;
        abort_unless($file && Storage::disk('local')->exists($file['path']), 404);

        return Storage::disk('local')->download($file['path'], $file['name']);
    }

    private function ensureStage(InterviewSimulation $simulation, InterviewStage $stage): void
    {
        $this->authorize('view', $simulation);
        abort_unless($stage->interview_simulation_id === $simulation->id, 404);
    }

    public function destroy(InterviewSimulation $simulation): RedirectResponse
    {
        $this->authorize('delete', $simulation);
        // Los archivos entregados en la prueba práctica también se borran del disco.
        foreach ($simulation->stages()->get() as $stage) {
            if (isset($stage->answers['file']['path'])) {
                Storage::disk('local')->delete($stage->answers['file']['path']);
            }
        }
        $simulation->delete();

        return redirect()->route('adaptive.interviews')->with('success', 'La simulación y tus respuestas fueron eliminadas.');
    }

    private function remainingToday(int $userId): int
    {
        $used = InterviewSimulation::where('user_id', $userId)->where('created_at', '>=', now()->startOfDay())->count();

        return max(0, (int) config('ai.interview_daily_limit') - $used);
    }

    /** Puesto que la persona escribió como meta en su diagnóstico, para no tener que escribirlo otra vez. */
    private function suggestedTitle(int $userId): string
    {
        $attempt = DiagnosticAttempt::where('user_id', $userId)->whereNotNull('analysis')->latest()->first();

        return (string) (data_get($attempt?->analysis, 'target_role') ?: '');
    }
}
