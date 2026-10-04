<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\SubmitChallengeEvidenceRequest;
use App\Jobs\EvaluateChallengeSubmissionJob;
use App\Models\Challenge;
use App\Models\ChallengeSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChallengeController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $path = $user->learningPaths()->whereIn('status', ['active', 'completed'])->with('items')->latest()->first();
        $total = $path?->items->count() ?? 0;
        $completed = $path?->items->where('status', 'completed')->count() ?? 0;
        $progress = $total ? (int) floor($completed / $total * 100) : 0;
        $challenges = $path ? Challenge::where('user_id', $user->id)->where('learning_path_id', $path->id)->orderBy('milestone')->get() : collect();

        return view('adaptive.challenges', [
            'path' => $path,
            'progress' => $progress,
            'challenges' => $challenges,
            'challengesByMilestone' => $challenges->keyBy('milestone'),
            'submissions' => ChallengeSubmission::where('user_id', $user->id)->whereIn('challenge_id', $challenges->pluck('id'))->get()->keyBy('challenge_id'),
        ]);
    }

    public function show(Request $request, Challenge $challenge): View
    {
        $this->authorize('view', $challenge);

        return view('adaptive.challenge-show', [
            'challenge' => $challenge,
            'submission' => ChallengeSubmission::where('challenge_id', $challenge->id)->where('user_id', $request->user()->id)->first(),
        ]);
    }

    public function submit(SubmitChallengeEvidenceRequest $request, Challenge $challenge): RedirectResponse
    {
        $this->authorize('view', $challenge);
        $data = $request->validated();

        $existing = ChallengeSubmission::where('challenge_id', $challenge->id)->where('user_id', $request->user()->id)->first();
        $payload = ['answer' => $data['answer'] ?? null, 'methodology' => $data['methodology'], 'status' => 'queued', 'score' => null, 'feedback' => null, 'evaluation' => null, 'evaluated_at' => null];

        if ($request->hasFile('attachment')) {
            if ($existing?->attachment_path) {
                Storage::disk('local')->delete($existing->attachment_path);
            }
            $file = $request->file('attachment');
            $path = $file->store('challenge-evidence/'.$request->user()->id, 'local');
            $payload = array_merge($payload, ['attachment_path' => $path, 'attachment_name' => $file->getClientOriginalName(), 'attachment_mime' => $file->getMimeType(), 'attachment_size' => $file->getSize(), 'attachment_analysis' => null]);
        }

        $submission = ChallengeSubmission::updateOrCreate(['challenge_id' => $challenge->id, 'user_id' => $request->user()->id], $payload);
        EvaluateChallengeSubmissionJob::dispatch($submission->id)->onQueue('ai');

        return redirect()->route('adaptive.challenges.show', $challenge)->with('success', 'Evidencia guardada. El agente evaluador preparará tu resultado en segundo plano.');
    }

    public function downloadResource(Challenge $challenge, int $resource): Response
    {
        $this->authorize('view', $challenge);
        $item = data_get($challenge->resources ?? [], $resource);
        abort_unless(is_array($item) && filled(data_get($item, 'content')), 404);
        $mime = (string) data_get($item, 'mime', 'text/plain; charset=UTF-8');
        $content = (string) data_get($item, 'content');
        if (str_starts_with(strtolower($mime), 'text/csv')) {
            $content = "\xEF\xBB\xBF".$content;
        }

        return response($content, 200, ['Content-Type' => $mime, 'Content-Disposition' => 'attachment; filename="'.str_replace('"', '', data_get($item, 'filename', 'material.txt')).'"']);
    }

    public function downloadAttachment(ChallengeSubmission $submission): StreamedResponse
    {
        $this->authorize('view', $submission);
        abort_unless($submission->attachment_path && Storage::disk('local')->exists($submission->attachment_path), 404);

        return Storage::disk('local')->download($submission->attachment_path, $submission->attachment_name);
    }
}
