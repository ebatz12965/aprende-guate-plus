<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $space = $request->validate(['space' => ['required', 'in:learning,mentor,admin']])['space'];
        $user = $request->user();

        abort_unless(match ($space) {
            'learning' => true,
            'mentor' => $user->hasRole('Instructor') || $user->hasRole('Admin'),
            'admin' => $user->hasRole('Admin'),
        }, 403);

        $request->session()->put('active_space', $space);

        return redirect()->to(match ($space) {
            'admin' => route('admin.dashboard'),
            'mentor' => route('mentor.dashboard'),
            default => route('adaptive.dashboard'),
        });
    }
}
