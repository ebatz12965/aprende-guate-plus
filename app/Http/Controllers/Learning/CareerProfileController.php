<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\UpdateCareerProfileRequest;
use App\Models\StudentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CareerProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('adaptive.career', [
            'profile' => StudentProfile::firstOrCreate(['user_id' => $user->id]),
            'completedPaths' => $user->learningPaths()->where('status', 'completed')->latest()->get(),
        ]);
    }

    public function update(UpdateCareerProfileRequest $request): RedirectResponse
    {
        $data = $request->profileData();

        $profile = StudentProfile::firstOrCreate(['user_id' => $request->user()->id]);

        if ($request->hasFile('cv')) {
            if ($profile->cv_path) {
                // Los CV anteriores a este cambio estaban en el disco público.
                Storage::disk('local')->delete($profile->cv_path);
                Storage::disk('public')->delete($profile->cv_path);
            }
            // El CV tiene datos personales: va al disco privado y solo lo descarga su dueño.
            $data['cv_path'] = $request->file('cv')->store('cvs/'.$request->user()->id, 'local');
        }

        if ($request->boolean('remove_photo') && ! $request->hasFile('profile_photo') && $profile->photo_path) {
            Storage::disk('public')->delete($profile->photo_path);
            $data['photo_path'] = null;
        }

        if ($request->hasFile('profile_photo')) {
            if ($profile->photo_path) {
                Storage::disk('public')->delete($profile->photo_path);
            }
            $data['photo_path'] = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        $profile->update($data);

        return back()->with('success', 'Perfil profesional actualizado.');
    }

    public function cv(Request $request): StreamedResponse
    {
        $profile = StudentProfile::where('user_id', $request->user()->id)->first();
        abort_unless($profile?->cv_path && Storage::disk('local')->exists($profile->cv_path), 404);

        return Storage::disk('local')->response($profile->cv_path, 'CV.pdf', [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function regeneratePublicLink(Request $request): RedirectResponse
    {
        StudentProfile::firstOrCreate(['user_id' => $request->user()->id])->regeneratePublicSlug();

        return back()->with('success', 'Se creó un nuevo enlace para tu perfil público. El enlace anterior ya no funciona.');
    }

    /** Perfil público por enlace no adivinable; 404 si no existe o la persona no lo ha activado. */
    public function show(string $slug): View
    {
        $profile = StudentProfile::with('user')->where('public_slug', $slug)->where('is_public', true)->firstOrFail();
        $user = $profile->user;
        // El perfil de una cuenta suspendida no se muestra mientras dure la suspensión.
        abort_if($user->isSuspended(), 404);

        return view('adaptive.public-profile', [
            'user' => $user,
            'profile' => $profile,
            'completedPaths' => $user->learningPaths()->where('status', 'completed')->latest()->get(),
        ]);
    }
}
