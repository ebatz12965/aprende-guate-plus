<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\ChallengeSubmission;
use App\Models\MentorshipSession;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $path = $user->learningPaths()->active()->with('items.topics')->latest()->first();
        $total = $path?->items->count() ?? 0;
        $completed = $path?->items->where('status', 'completed')->count() ?? 0;

        return view('adaptive.dashboard', [
            'profile' => $user->studentProfile,
            'path' => $path,
            'total' => $total,
            'completed' => $completed,
            'progress' => $total ? (int) round($completed / $total * 100) : 0,
            'nextItem' => $path?->items->firstWhere('status', 'in_progress') ?? $path?->items->firstWhere('status', 'pending'),
            'challengeCount' => ChallengeSubmission::where('user_id', $user->id)->count(),
            'upcomingSession' => MentorshipSession::where('student_id', $user->id)->whereIn('status', ['requested', 'scheduled'])->with('mentor')->orderBy('scheduled_at')->first(),
        ]);
    }
}
