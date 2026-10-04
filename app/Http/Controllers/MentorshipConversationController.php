<?php

namespace App\Http\Controllers;

use App\Models\MentorshipSession;
use App\Models\NotificationPreference;
use App\Notifications\PlatformAlertNotification;
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

    public function send(Request $request, MentorshipSession $session): RedirectResponse|JsonResponse
    {
        $this->authorize('participate', $session);
        abort_unless(in_array($session->status, ['accepted', 'scheduled'], true), 403);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $message = $session->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        $recipient = $session->mentor_id === $request->user()->id ? $session->student : $session->mentor;
        $title = 'Nuevo mensaje de mentoría';
        $body = $request->user()->name.' te envió un mensaje sobre “'.$session->topic.'”.';
        $recipient->notify(new PlatformAlertNotification(
            category: 'messages',
            title: $title,
            body: $body,
            url: route('mentorship.sessions.show', $session),
            actionLabel: 'Leer mensaje',
            allowEmail: false,
        ));

        if (NotificationPreference::forUser($recipient)->receives('mail', 'messages')) {
            dispatch(function () use ($recipient, $title, $body, $session): void {
                $recipient->notify(new PlatformAlertNotification(
                    category: 'messages',
                    title: $title,
                    body: $body,
                    url: route('mentorship.sessions.show', $session),
                    actionLabel: 'Leer mensaje',
                    allowEmail: true,
                    allowDatabase: false,
                ));
            })->afterResponse();
        }

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
