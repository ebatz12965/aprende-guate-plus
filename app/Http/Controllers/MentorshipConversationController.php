<?php

namespace App\Http\Controllers;

use App\Models\MentorshipSession;
use App\Notifications\PlatformAlertNotification;
use App\Services\MentorshipCompletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MentorshipConversationController extends Controller
{
    public function show(Request $request, MentorshipSession $session): View
    {
        $this->authorize('participate', $session);
        $isMentor = $session->mentor_id === $request->user()->id;
        abort_unless($isMentor || in_array($session->status, ['accepted', 'scheduled', 'completed'], true), 403);

        if ($session->isActive()) {
            $session->messages()
                ->where('sender_id', '!=', $request->user()->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        $session->load(['student.studentProfile', 'mentor.mentorProfile', 'messages.sender', 'completedBy']);

        return view('mentorship.session', [
            'session' => $session,
            'isMentor' => $isMentor,
        ]);
    }

    public function send(Request $request, MentorshipSession $session): RedirectResponse|JsonResponse
    {
        $this->authorize('participate', $session);
        abort_unless($session->isActive(), 403, $session->status === 'completed' ? 'Esta sesión ya finalizó; la conversación quedó cerrada.' : 'Esta conversación no está disponible.');
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $message = $session->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        // Solo el aviso dentro de la plataforma: el correo llega una vez, con toda la conversación, al finalizar la sesión.
        $recipient = $session->mentor_id === $request->user()->id ? $session->student : $session->mentor;
        $recipient->notify(new PlatformAlertNotification(
            category: 'messages',
            title: 'Nuevo mensaje de mentoría',
            body: $request->user()->name.' te envió un mensaje sobre “'.$session->topic.'”.',
            url: route('mentorship.sessions.show', $session),
            actionLabel: 'Leer mensaje',
            allowEmail: false,
        ));

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $message->id,
                'message' => $message->body,
                'created_at' => $message->created_at->format('d/m H:i'),
                'is_automatic' => false,
            ], 201);
        }

        return back()->with('success', 'Mensaje enviado.');
    }

    public function finish(Request $request, MentorshipSession $session, MentorshipCompletionService $completion): RedirectResponse
    {
        $this->authorize('participate', $session);

        if (! $completion->complete($session, $request->user())) {
            return redirect()->route('mentorship.sessions.show', $session)->with('error', 'Esta sesión ya no estaba abierta.');
        }

        dispatch(fn () => $completion->sendTranscripts($session))->afterResponse();

        return redirect()->route('mentorship.sessions.show', $session)->with('success', 'Sesión finalizada. Ambas personas recibirán la conversación completa por correo.');
    }

    public function messages(Request $request, MentorshipSession $session): JsonResponse
    {
        $this->authorize('participate', $session);
        abort_unless(in_array($session->status, ['accepted', 'scheduled'], true), 403);

        $afterId = max(0, $request->integer('after_id', 0));
        $messages = $session->messages()
            ->where('id', '>', $afterId)
            ->with('sender:id,name')
            ->oldest('id')
            ->get();

        if ($messages->isNotEmpty()) {
            $messages->where('sender_id', '!=', $request->user()->id)
                ->whereNull('read_at')
                ->each(fn ($message) => $message->update(['read_at' => now()]));
        }

        return response()->json([
            'messages' => $messages->map(fn ($message) => [
                'id' => $message->id,
                'body' => $message->body,
                'created_at' => $message->created_at->format('d/m H:i'),
                'is_automatic' => $message->is_automatic,
                'mine' => $message->sender_id === $request->user()->id,
            ])->values(),
        ]);
    }

    public function widget(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $sessions = MentorshipSession::query()
            ->whereIn('status', ['accepted', 'scheduled'])
            ->where(function ($query) use ($userId): void {
                $query->where('student_id', $userId)->orWhere('mentor_id', $userId);
            })
            ->with([
                'student:id,name',
                'mentor:id,name',
                'latestMessage.sender:id,name',
            ])
            ->withCount(['messages as unread_messages_count' => fn ($query) => $query
                ->whereNull('read_at')
                ->where('sender_id', '!=', $userId)])
            ->latest('updated_at')
            ->limit(8)
            ->get();

        return response()->json([
            'sessions' => $sessions->map(function (MentorshipSession $session) use ($userId): array {
                $latest = $session->latestMessage;
                $counterpart = $session->student_id === $userId ? $session->mentor : $session->student;

                return [
                    'id' => $session->id,
                    'topic' => $session->topic,
                    'counterpart' => $counterpart?->name ?? 'Participante',
                    'latest' => $latest ? [
                        'id' => $latest->id,
                        'body' => $latest->body,
                        'created_at' => $latest->created_at->format('d/m H:i'),
                        'mine' => $latest->sender_id === $userId,
                    ] : null,
                    'unread' => (int) $session->unread_messages_count,
                    'send_url' => route('mentorship.sessions.messages.send', $session),
                    'history_url' => $session->mentor_id === $userId
                        ? route('mentor.sessions')
                        : route('adaptive.mentorship'),
                ];
            })->values(),
        ]);
    }

    public function markWidgetRead(Request $request, MentorshipSession $session): JsonResponse
    {
        $this->authorize('participate', $session);
        abort_unless(in_array($session->status, ['accepted', 'scheduled'], true), 403);

        $session->messages()
            ->where('sender_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
