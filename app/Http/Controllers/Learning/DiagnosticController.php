<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateLearningPathJob;
use App\Models\DiagnosticAttempt;
use App\Models\PlatformSetting;
use App\Services\AiTutorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiagnosticController extends Controller
{
    private const ALREADY_PROCESSED = 'Este cuestionario ya fue procesado. Puedes iniciar un nuevo diagnóstico cuando lo necesites.';

    public function show(): View
    {
        return view('adaptive.diagnostic', ['settings' => PlatformSetting::aiLimits()]);
    }

    public function start(Request $request, AiTutorService $tutor): RedirectResponse
    {
        $settings = PlatformSetting::aiLimits();
        $data = $request->validate(['primary_answer' => ['required', 'string', 'min:'.$settings['diagnostic_min_characters'], 'max:'.$settings['diagnostic_max_characters']]]);
        $attempt = DiagnosticAttempt::create([
            'user_id' => $request->user()->id,
            'primary_answer' => $data['primary_answer'],
            'answers' => [],
            'questions' => $tutor->diagnosticQuestions($data['primary_answer'], $request->user()->id),
        ]);

        return redirect()->route('adaptive.diagnostic.questions', $attempt);
    }

    public function questions(Request $request, DiagnosticAttempt $attempt): View|RedirectResponse
    {
        abort_unless($attempt->user_id === $request->user()->id, 404);

        if ($attempt->status !== 'started') {
            return redirect()->route('adaptive.diagnostic')->with('info', self::ALREADY_PROCESSED);
        }

        return view('adaptive.diagnostic-questions', compact('attempt'));
    }

    public function store(Request $request, DiagnosticAttempt $attempt): RedirectResponse
    {
        abort_unless($attempt->user_id === $request->user()->id, 404);

        if ($attempt->status !== 'started') {
            return redirect()->route('adaptive.diagnostic')->with('info', self::ALREADY_PROCESSED);
        }

        $data = $request->validate(['answers' => ['required', 'array', 'size:4'], 'answers.*' => ['required', 'integer', 'between:0,3']]);

        $attempt->update(['answers' => $data['answers']]);
        $this->queueGeneration($attempt);

        return redirect()->route('adaptive.diagnostic.generation', $attempt)
            ->with('success', 'Estamos preparando tu ruta personalizada en segundo plano.');
    }

    public function generation(Request $request, DiagnosticAttempt $attempt): View|RedirectResponse
    {
        abort_unless($attempt->user_id === $request->user()->id, 404);

        if ($attempt->status === 'completed') {
            return redirect()->route('adaptive.path')->with('success', 'Tu ruta personalizada está lista. Empieza con la primera fase.');
        }

        return view('adaptive.diagnostic-generating', compact('attempt'));
    }

    public function retry(Request $request, DiagnosticAttempt $attempt): RedirectResponse
    {
        abort_unless($attempt->user_id === $request->user()->id, 404);
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
