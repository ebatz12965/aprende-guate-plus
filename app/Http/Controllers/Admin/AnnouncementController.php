<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformAnnouncement;
use App\Models\User;
use App\Notifications\PlatformAlertNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        return view('admin.announcements.index', [
            'announcements' => PlatformAnnouncement::with('author')->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:3000'],
            'audience' => ['required', 'in:all,students,mentors'],
            'send_email' => ['nullable', 'boolean'],
            'publish_now' => ['nullable', 'boolean'],
        ]);

        $announcement = PlatformAnnouncement::create([
            'created_by' => $request->user()->id,
            'title' => $data['title'],
            'body' => $data['body'],
            'audience' => $data['audience'],
            'send_email' => $request->boolean('send_email'),
        ]);

        if ($request->boolean('publish_now')) {
            $this->publishAnnouncement($announcement);

            return back()->with('success', 'Aviso publicado y notificaciones enviadas a la audiencia seleccionada.');
        }

        return back()->with('success', 'Aviso guardado como borrador. Podrás publicarlo cuando estés listo.');
    }

    public function publish(PlatformAnnouncement $announcement): RedirectResponse
    {
        abort_if($announcement->is_published, 422, 'Este aviso ya fue publicado.');
        $this->publishAnnouncement($announcement);

        return back()->with('success', 'Aviso publicado y notificaciones enviadas.');
    }

    public function destroy(PlatformAnnouncement $announcement): RedirectResponse
    {
        abort_if($announcement->is_published, 422, 'Los avisos publicados se conservan como historial de comunicación.');
        $announcement->delete();

        return back()->with('success', 'Borrador eliminado.');
    }

    private function publishAnnouncement(PlatformAnnouncement $announcement): void
    {
        $announcement->update(['is_published' => true, 'published_at' => now()]);

        $this->recipients($announcement->audience)->each(function (User $user) use ($announcement): void {
            $user->notify(new PlatformAlertNotification(
                category: 'platform',
                title: $announcement->title,
                body: $announcement->body,
                url: route('notifications.index'),
                actionLabel: 'Ver avisos',
                allowEmail: $announcement->send_email,
            ));
        });
    }

    private function recipients(string $audience): Collection
    {
        return match ($audience) {
            'students' => User::role('Student')->get(),
            'mentors' => User::role(['Instructor', 'Admin'])->get(),
            default => User::query()->get(),
        };
    }
}