<?php

namespace Tests\Feature;

use App\Models\MentorshipSession;
use App\Models\User;
use App\Notifications\MentorshipTranscriptNotification;
use App\Notifications\PlatformAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MentorshipSessionCompletionTest extends TestCase
{
    use RefreshDatabase;

    private function makeSession(array $attributes = []): MentorshipSession
    {
        $student = User::factory()->create(['name' => 'Estudiante Prueba']);
        $mentor = User::factory()->create(['name' => 'Mentora Prueba']);

        return MentorshipSession::create([
            'student_id' => $student->id,
            'mentor_id' => $mentor->id,
            'topic' => 'Tablas dinámicas en Excel',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
            'duration_minutes' => 30,
            ...$attributes,
        ]);
    }

    public function test_chat_messages_no_longer_send_an_email_per_message(): void
    {
        Notification::fake();
        $session = $this->makeSession();

        $this->actingAs($session->student)->postJson(route('mentorship.sessions.messages.send', $session), ['body' => 'Hola, ¿cómo agrupo por mes?'])->assertCreated();

        Notification::assertSentTo($session->mentor, PlatformAlertNotification::class, fn ($notification, array $channels) => $channels === ['database']);
        Notification::assertNotSentTo($session->mentor, MentorshipTranscriptNotification::class);
    }

    public function test_finishing_the_session_closes_the_chat_and_emails_the_whole_conversation_to_both(): void
    {
        Notification::fake();
        $session = $this->makeSession();
        $session->messages()->create(['sender_id' => $session->student_id, 'body' => 'Hola, ¿cómo agrupo por mes?']);
        $session->messages()->create(['sender_id' => $session->mentor_id, 'body' => "Clic derecho → Agrupar → Meses.\n¡Listo!"]);

        $this->actingAs($session->mentor)->post(route('mentorship.sessions.finish', $session))
            ->assertRedirect(route('mentorship.sessions.show', $session))->assertSessionHas('success');

        $session->refresh();
        $this->assertSame('completed', $session->status);
        $this->assertSame($session->mentor_id, $session->completed_by);
        $this->assertNotNull($session->completed_at);
        $this->assertNotNull($session->transcript_sent_at);

        foreach ([$session->student, $session->mentor] as $participant) {
            Notification::assertSentTo($participant, MentorshipTranscriptNotification::class, function (MentorshipTranscriptNotification $notification) use ($participant) {
                $html = (string) $notification->toMail($participant)->render();

                return str_contains($html, 'Hola, ¿cómo agrupo por mes?') && str_contains($html, 'Clic derecho → Agrupar → Meses.<br>¡Listo!')
                    && str_contains($html, 'Estudiante Prueba') && str_contains($html, 'Mentora Prueba') && str_contains($html, '2 mensajes');
            });
        }
        // La otra persona también ve el aviso dentro de la plataforma.
        Notification::assertSentTo($session->student, PlatformAlertNotification::class);

        // El chat queda en solo lectura para ambas personas.
        $this->actingAs($session->student)->get(route('mentorship.sessions.show', $session))->assertOk()
            ->assertSee('Sesión finalizada')->assertSee('Hola, ¿cómo agrupo por mes?')->assertDontSee('Enviar mensaje');
        $this->actingAs($session->student)->postJson(route('mentorship.sessions.messages.send', $session), ['body' => 'Otra pregunta'])
            ->assertForbidden()->assertJsonPath('message', 'Esta sesión ya finalizó; la conversación quedó cerrada.');

        // Finalizarla otra vez no reenvía la conversación.
        $this->actingAs($session->student)->post(route('mentorship.sessions.finish', $session))->assertSessionHas('error');
        Notification::assertSentToTimes($session->student, MentorshipTranscriptNotification::class, 1);
    }

    public function test_people_outside_the_session_cannot_finish_it(): void
    {
        $session = $this->makeSession();

        $this->actingAs(User::factory()->create())->post(route('mentorship.sessions.finish', $session))->assertForbidden();
        $this->assertSame('scheduled', $session->fresh()->status);
    }

    public function test_sessions_nobody_finished_close_automatically_after_their_time(): void
    {
        Notification::fake();
        $old = $this->makeSession(['scheduled_at' => now()->subDays(2)]);
        $recent = $this->makeSession(['scheduled_at' => now()->subHours(2)]);

        $this->artisan('mentorship:close-finished')->assertSuccessful();

        $this->assertSame('completed', $old->fresh()->status);
        $this->assertNull($old->fresh()->completed_by);
        $this->assertSame('scheduled', $recent->fresh()->status);
        Notification::assertSentTo($old->student, MentorshipTranscriptNotification::class);
        Notification::assertSentTo($old->mentor, MentorshipTranscriptNotification::class);
        Notification::assertNotSentTo($recent->student, MentorshipTranscriptNotification::class);
    }
}
