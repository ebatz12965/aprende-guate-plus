<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\MentorProfile;
use App\Models\MentorshipSession;
use App\Notifications\PlatformAlertNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Mentorías desde el espacio del estudiante: explorar mentores y solicitar sesiones. */
class MentorshipController extends Controller
{
    public function index(Request $request): View
    {
        // Solo se ofrecen mentores con rol Instructor vigente; nunca el propio usuario.
        $mentors = MentorProfile::with('user')
            ->whereHas('user', fn ($query) => $query->role('Instructor'))
            ->where('user_id', '!=', $request->user()->id)
            ->get();

        return view('adaptive.mentorship', ['mentors' => $mentors, 'sessions' => MentorshipSession::where('student_id', $request->user()->id)->with('mentor')->latest()->get()]);
    }

    public function store(Request $request, MentorProfile $mentor): RedirectResponse
    {
        $this->authorize('requestSession', $mentor);
        $data = $request->validate([
            'topic' => ['required', 'string', 'max:160'],
            'preferred_at' => ['nullable', 'date', 'after:now'],
        ]);

        if ($mentor->user_id === $request->user()->id) {
            return back()->with('error', 'No puedes solicitarte una mentoría a ti mismo.');
        }

        $hasOpenRequest = MentorshipSession::where('student_id', $request->user()->id)
            ->where('mentor_id', $mentor->user_id)
            ->whereIn('status', ['requested', 'accepted', 'scheduled'])
            ->exists();
        if ($hasOpenRequest) {
            return back()->with('error', 'Ya tienes una solicitud o sesión activa con este mentor. Revisa su estado en “Mis solicitudes”.');
        }

        $session = MentorshipSession::create([
            'student_id' => $request->user()->id,
            'mentor_id' => $mentor->user_id,
            'topic' => $data['topic'],
            'preferred_at' => $data['preferred_at'] ?? null,
        ]);

        $mentor->user->notify(new PlatformAlertNotification(
            category: 'mentorship',
            title: 'Nueva solicitud de mentoría',
            body: $request->user()->name.' solicita tu orientación sobre “'.$session->topic.'”.',
            url: route('mentor.dashboard'),
            actionLabel: 'Revisar solicitud',
        ));

        return back()->with('success', 'Solicitud enviada al mentor. Te avisaremos cuando tenga una respuesta.');
    }
}
