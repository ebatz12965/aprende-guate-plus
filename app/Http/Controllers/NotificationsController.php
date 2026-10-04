<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationsController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->latest()->paginate(20),
            'preferences' => NotificationPreference::forUser($request->user()),
            'twoFactorPending' => ! $request->user()->hasTwoFactorEnabled(),
        ]);
    }
    public function updatePreferences(Request $request): RedirectResponse
    {
        $fields = ['in_app_messages', 'email_messages', 'in_app_mentorship', 'email_mentorship', 'in_app_platform', 'email_platform'];
        $data = $request->validate(array_fill_keys($fields, ['nullable', 'boolean']));
        $preferences = NotificationPreference::forUser($request->user());
        foreach ($fields as $field) { $data[$field] = $request->boolean($field); }
        $preferences->update($data);
        return back()->with('success', 'Tus preferencias de notificaciones fueron actualizadas.');
    }
    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();
        return data_get($item->data, 'url') ? redirect(data_get($item->data, 'url')) : back();
    }
    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();
        return back()->with('success', 'Todas las notificaciones fueron marcadas como leídas.');
    }
}
