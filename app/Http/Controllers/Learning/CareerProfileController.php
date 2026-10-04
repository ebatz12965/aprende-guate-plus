<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\UpdateCareerProfileRequest;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

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
                Storage::disk('public')->delete($profile->cv_path);
            }
            $data['cv_path'] = $request->file('cv')->store('cvs', 'public');
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

    public function show(User $user): View
    {
        $user->load('studentProfile');
        $profile = $user->studentProfile;
        abort_unless($profile, 404);

        return view('adaptive.public-profile', [
            'user' => $user,
            'profile' => $profile,
            'completedPaths' => $user->learningPaths()->where('status', 'completed')->latest()->get(),
        ]);
    }
}
