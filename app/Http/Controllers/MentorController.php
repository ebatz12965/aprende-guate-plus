<?php

namespace App\Http\Controllers;

use App\Models\MentorProfile;
use App\Models\MentorshipSession;
use App\Notifications\PlatformAlertNotification;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MentorController extends Controller
{
    public function dashboard(Request $request): View
    {
        $mentor = $request->user();
        $profile = $this->profileFor($mentor);

        $allSessions = MentorshipSession::query()
            ->where('mentor_id', $mentor->id)
            ->with('student.studentProfile')
            ->latest()
            ->get();
        $availableStatuses = ['requested', 'accepted', 'scheduled', 'cancelled', 'rejected'];
        $defaultStatuses = ['requested', 'accepted', 'scheduled', 'cancelled'];
        $selectedStatuses = $request->has('status')
            ? array_values(array_intersect((array) $request->input('status'), $availableStatuses))
            : $defaultStatuses;
        $sessions = $allSessions->whereIn('status', $selectedStatuses)->values();

        return view('mentor.dashboard', compact('profile', 'sessions', 'allSessions', 'selectedStatuses', 'availableStatuses'));
    }

    public function calendar(Request $request): View
    {
        $sessions = MentorshipSession::query()
            ->where('mentor_id', $request->user()->id)
            ->whereIn('status', ['scheduled', 'accepted'])
            ->whereNotNull('scheduled_at')
            ->with('student')
            ->orderBy('scheduled_at')
            ->get();

        return view('mentor.calendar-interactive', compact('sessions'));
    }

    public function sessions(Request $request): View
    {
        $sessions = MentorshipSession::query()
            ->where('mentor_id', $request->user()->id)
            ->whereIn('status', ['accepted', 'scheduled'])
            ->with(['student', 'messages' => fn ($query) => $query->latest()->limit(1)])
            ->latest('scheduled_at')
            ->get();

        return view('mentor.sessions', compact('sessions'));
    }

    public function settings(Request $request): View
    {
        return view('mentor.settings', ['profile' => $this->profileFor($request->user())]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'headline' => ['nullable', 'string', 'max:160'],
            'specialties' => ['nullable', 'string', 'max:500'],
            'about' => ['nullable', 'string', 'max:1500'],
            'acceptance_message' => ['required', 'string', 'max:2000'],
            'rejection_message' => ['required', 'string', 'max:2000'],
            'session_duration_minutes' => ['required', 'integer', 'min:5', 'max:240'],
            'availability' => ['nullable', 'array'],
        ]);

        $data['availability'] = collect($data['availability'] ?? [])->mapWithKeys(function ($slot, $day) {
            return [$day => is_array($slot) ? array_values(array_filter($slot, 'is_string')) : []];
        })->all();

        MentorProfile::updateOrCreate(['user_id' => $request->user()->id], $data);

        return back()->with('success', 'Tu perfil y mensajes automáticos de mentoría fueron actualizados.');
    }

    public function accept(Request $request, MentorshipSession $session): RedirectResponse
    {
        $this->ensurePendingSession($request, $session);
        $profile = $this->profileFor($request->user());
        $data = $request->validate([
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ]);
        $scheduledAt = $data['scheduled_at'] ?? optional($session->preferred_at)->format('Y-m-d\\TH:i') ?? now()->addHour()->format('Y-m-d H:i:s');
        $hasExplicitSchedule = filled($data['scheduled_at'] ?? null) || $session->preferred_at !== null;
        $scheduled = now()->parse($scheduledAt);
        if ($hasExplicitSchedule) {
            $this->assertSlotAvailable($session, $profile, $scheduled);
        }
        $message = $this->composeMessage($profile->acceptance_message, $session, $request->user()->name);

        $session->update([
            'status' => $hasExplicitSchedule ? 'scheduled' : 'accepted',
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $profile->session_duration_minutes ?: 30,
            'responded_at' => now(),
            'decision_message' => $message,
        ]);
        $session->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $message,
            'is_automatic' => true,
        ]);

        $session->student->notify(new PlatformAlertNotification(
            category: 'mentorship',
            title: 'Tu solicitud de mentoría fue aceptada',
            body: $request->user()->name.' aceptó tu solicitud sobre “'.$session->topic.'”. Sesión: '.$session->scheduled_at->format('d/m/Y H:i').' ('.$session->duration_minutes.' minutos).',
            url: route('mentorship.sessions.show', $session),
            actionLabel: 'Abrir conversación',
        ));

        return redirect()->route('mentor.sessions.show', $session)->with('success', 'Sesión programada y mensaje automático enviado al estudiante.');
    }

    public function reject(Request $request, MentorshipSession $session): RedirectResponse
    {
        $this->ensurePendingSession($request, $session);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:800']]);
        $profile = $this->profileFor($request->user());
        $message = $this->composeMessage($profile->rejection_message, $session, $request->user()->name, $data['reason'] ?? null);

        $session->update([
            'status' => 'rejected',
            'responded_at' => now(),
            'decision_message' => $message,
        ]);

        $session->student->notify(new PlatformAlertNotification(
            category: 'mentorship',
            title: 'Actualización de tu solicitud de mentoría',
            body: $request->user()->name.' no puede acompañarte en este tema por el momento. Revisa el detalle y considera otro mentor.',
            url: route('adaptive.mentorship'),
            actionLabel: 'Ver mentorías',
        ));

        return redirect()->route('mentor.dashboard')->with('success', 'Solicitud rechazada con un mensaje respetuoso para el estudiante.');
    }

    public function cancel(Request $request, MentorshipSession $session): RedirectResponse
    {
        abort_unless(in_array($request->user()->id, [$session->student_id, $session->mentor_id], true), 403);
        abort_unless(in_array($session->status, ['accepted', 'scheduled'], true), 422);
        abort_unless($session->scheduled_at?->isFuture() ?? true, 422, 'La sesión ya inició y no puede cancelarse desde aquí.');
        $data = $request->validate(['message' => ['nullable', 'string', 'max:800']]);
        $message = trim($data['message'] ?? '') ?: ($request->user()->id === $session->mentor_id
            ? 'El mentor canceló la sesión programada. Puedes solicitar otra mentoría cuando lo necesites.'
            : 'El estudiante canceló la sesión programada. Puedes revisar nuevas solicitudes desde mentoría.');

        $session->update([
            'status' => 'cancelled',
            'cancelled_by' => $request->user()->id,
            'cancelled_at' => now(),
            'cancellation_message' => $message,
        ]);

        $recipient = $request->user()->id === $session->mentor_id ? $session->student : $session->mentor;
        $recipient->notify(new PlatformAlertNotification(
            category: 'mentorship',
            title: 'Sesión de mentoría cancelada',
            body: $message,
            url: $request->user()->id === $session->mentor_id ? route('adaptive.mentorship') : route('mentor.dashboard'),
            actionLabel: 'Ver mentorías',
        ));

        return redirect()->route($request->user()->id === $session->mentor_id ? 'mentor.dashboard' : 'adaptive.mentorship')->with('success', 'La sesión fue cancelada y la otra persona recibió una notificación.');
    }

    private function ensurePendingSession(Request $request, MentorshipSession $session): void
    {
        abort_unless($session->mentor_id === $request->user()->id, 403);
        abort_unless($session->status === 'requested', 422);
    }

    private function profileFor($mentor): MentorProfile
    {
        // Un administrador puede consultar el panel mentor sin convertirse en mentor:
        // el perfil solo se persiste automáticamente para usuarios con rol Instructor.
        $profile = MentorProfile::firstOrNew(['user_id' => $mentor->id], $this->defaultProfileSettings());
        if (! $profile->exists) {
            if ($mentor->hasRole('Instructor')) {
                $profile->save();
            }

            return $profile;
        }
        $defaults = $this->defaultProfileSettings();
        $changed = false;

        foreach (['acceptance_message', 'rejection_message'] as $field) {
            if (blank($profile->{$field})) {
                $profile->{$field} = $defaults[$field];
                $changed = true;
            }
        }

        if ($changed) {
            $profile->save();
        }

        return $profile;
    }

    private function defaultProfileSettings(): array
    {
        return [
            'acceptance_message' => 'Hola, {estudiante}. Gracias por confiar en mí para orientarte sobre {tema}. Acepté tu solicitud; cuéntame qué resultado quieres lograr y revisaremos los siguientes pasos juntos.',
            'rejection_message' => 'Hola, {estudiante}. Gracias por considerar mi mentoría para {tema}. En este momento no soy la persona más adecuada para acompañarte en este tema. Te recomiendo explorar otro mentor con experiencia más cercana a tu necesidad.{motivo}',
            'session_duration_minutes' => 30,
            'availability' => [],
        ];
    }

    private function composeMessage(?string $template, MentorshipSession $session, string $mentorName, ?string $reason = null): string
    {
        $message = strtr((string) $template, [
            '{estudiante}' => $session->student->name,
            '{mentor}' => $mentorName,
            '{tema}' => $session->topic,
            '{motivo}' => $reason ? ' Comentario del mentor: '.$reason : '',
        ]);

        return trim($message);
    }

    private function assertSlotAvailable(MentorshipSession $session, MentorProfile $profile, Carbon $scheduled): void
    {
        $duration = (int) ($profile->session_duration_minutes ?: 30);
        $dayNames = [1 => 'lunes', 2 => 'martes', 3 => 'miercoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sabado', 7 => 'domingo'];
        $day = $dayNames[$scheduled->dayOfWeekIso];
        $slots = data_get($profile->availability, $day, []);
        if ($slots) {
            $fits = collect($slots)->contains(function (string $slot) use ($scheduled, $duration): bool {
                [$from, $to] = array_pad(explode('-', $slot, 2), 2, null);
                if (! $from || ! $to) {
                    return false;
                }
                $start = $scheduled->copy()->setTimeFromTimeString($from);
                $end = $scheduled->copy()->setTimeFromTimeString($to);

                return $scheduled->greaterThanOrEqualTo($start) && $scheduled->copy()->addMinutes($duration)->lessThanOrEqualTo($end);
            });
            if (! $fits) {
                throw ValidationException::withMessages(['scheduled_at' => 'La hora seleccionada está fuera de tu disponibilidad configurada.']);
            }
        }

        $windowStart = $scheduled->copy()->subMinutes($duration);
        $windowEnd = $scheduled->copy()->addMinutes($duration);
        $conflict = MentorshipSession::query()
            ->where('id', '!=', $session->id)
            ->whereIn('status', ['accepted', 'scheduled'])
            ->where(function ($query) use ($session) {
                $query->whereIn('mentor_id', [$session->mentor_id, $session->student_id])
                    ->orWhereIn('student_id', [$session->mentor_id, $session->student_id]);
            })
            ->whereBetween('scheduled_at', [$windowStart, $windowEnd])
            ->exists();
        if ($conflict) {
            throw ValidationException::withMessages(['scheduled_at' => 'Ese horario se cruza con otra sesión existente. Elige otro momento.']);
        }
    }
}
