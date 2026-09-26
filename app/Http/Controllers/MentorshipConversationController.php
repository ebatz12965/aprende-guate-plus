<?php

namespace App\Http\Controllers;

use App\Models\MentorshipSession;
use App\Notifications\PlatformAlertNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MentorshipConversationController extends Controller
{
    public function show(Request $request, MentorshipSession $session): View
    {
        $this->ensureParticipant($request, $session);
        $isMentor = $session->mentor_id === $request->user()->id;
        abort_unless($isMentor || in_array($session->status, ['accepted', 'scheduled'], true), 403);

        if (in_array($session->status, ['accepted', 'scheduled'], true)) {
            $session->messages()
                ->where('sender_id', '!=', $request->user()->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        $session->load(['student.studentProfile', 'mentor.mentorProfile', 'messages.sender']);

        return view('mentorship.session', [
            'session' => $session,
            'isMentor' => $isMentor,
        ]);
    }

    public function send(Request $request, MentorshipSession $session): RedirectResponse
    {
        $this->ensureParticipant($request, $session);
        abort_unless(in_array($session->status, ['accepted', 'scheduled'], true), 403);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $session->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        $recipient = $session->mentor_id === $request->user()->id ? $session->student : $session->mentor;
        $recipient->notify(new PlatformAlertNotification(
            category: 'messages',
            title: 'Nuevo mensaje de mentoría',
            body: $request->user()->name.' te envió un mensaje sobre “'.$session->topic.'”.',
            url: route('mentorship.sessions.show', $session),
            actionLabel: 'Leer mensaje',
        ));

        return back()->with('success', 'Mensaje enviado.');
    }

    private function ensureParticipant(Request $request, MentorshipSession $session): void
    {
        abort_unless(in_array($request->user()->id, [$session->student_id, $session->mentor_id], true), 403);
    }
}