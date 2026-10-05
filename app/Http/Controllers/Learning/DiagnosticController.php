<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\StartDiagnosticRequest;
use App\Jobs\GenerateDiagnosticQuestionsJob;
use App\Jobs\GenerateLearningPathJob;
use App\Models\DiagnosticAttempt;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiagnosticController extends Controller
{
    private const ALREADY_PROCESSED = 'Este cuestionario ya fue procesado. Puedes iniciar un nuevo diagnóstico cuando lo necesites.';

    public function show(Request $request): View
    {
        return view('adaptive.diagnostic', [
            'settings' => PlatformSetting::aiLimits(),
            // Rehacer el diagnóstico reemplaza la ruta activa: la vista lo avisa.
            'activePath' => $request->user()->learningPaths()->active()->with('items')->latest('id')->first(),
        ]);
    }

    public function start(StartDiagnosticRequest $request): RedirectResponse
    {
        $data = $request->validated();
        // Las preguntas se generan en la cola: la petición responde de inmediato.
        $attempt = DiagnosticAttempt::create([
            'user_id' => $request->user()->id,
            'primary_answer' => $data['primary_answer'],
            'answers' => [],
            'status' => 'preparing',
        ]);
        GenerateDiagnosticQuestionsJob::dispatch($attempt->id)->onQueue('ai');

        return redirect()->route('adaptive.diagnostic.questions', $attempt);
    }

    public function questions(DiagnosticAttempt $attempt): View|RedirectResponse
    {
        $this->authorize('view', $attempt);

        if ($attempt->status === 'preparing') {
            return view('adaptive.diagnostic-preparing', compact('attempt'));
        }

        if ($attempt->status !== 'started') {
            return redirect()->route('adaptive.diagnostic')->with('info', self::ALREADY_PROCESSED);
        }

        return view('adaptive.diagnostic-questions', compact('attempt'));
    }

    public function store(Request $request, DiagnosticAttempt $attempt): RedirectResponse
    {
        $this->authorize('view', $attempt);

        if ($attempt->status !== 'started') {
            return redirect()->route('adaptive.diagnostic')->with('info', self::ALREADY_PROCESSED);
        }

        $data = $request->validate(['answers' => ['required', 'array', 'size:4'], 'answers.*' => ['required', 'integer', 'between:0,3']]);

        $attempt->update(['answers' => $data['answers']]);
        $this->queueGeneration($attempt);

        return redirect()->route('adaptive.diagnostic.generation', $attempt)
            ->with('success', 'Estamos preparando tu ruta personalizada en segundo plano.');
    }

    public function generation(DiagnosticAttempt $attempt): View|RedirectResponse
    {
        $this->authorize('view', $attempt);

        if ($attempt->status === 'completed') {
            return redirect()->route('adaptive.path')->with('success', 'Tu ruta personalizada está lista. Empieza con la primera fase.');
        }

        return view('adaptive.diagnostic-generating', compact('attempt'));
    }

    public function retry(DiagnosticAttempt $attempt): RedirectResponse
    {
        $this->authorize('view', $attempt);
        abort_unless($attempt->status === 'failed', 409);

        $this->queueGeneration($attempt);

        return redirect()->route('adaptive.diagnostic.generation', $attempt)
            ->with('success', 'Reintentamos la generación de tu ruta.');
    }

    private function queueGeneration(DiagnosticAttempt $attempt): void
    {
        $attempt->update([
            'status' => 'generating',
            'generation_error' => null,
            'generation_requested_at' => now(),
        ]);

        GenerateLearningPathJob::dispatch($attempt->id)->onQueue('ai');
    }
}
