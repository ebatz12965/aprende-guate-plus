<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
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

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'professional_goal' => ['nullable', 'string', 'max:120'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'bio_visibility' => ['required', 'in:public,private'],
            'education' => ['nullable', 'array', 'max:10'],
            'education.*.institution' => ['nullable', 'string', 'max:160'],
            'education.*.program' => ['nullable', 'string', 'max:160'],
            'education.*.period' => ['nullable', 'string', 'max:80'],
            'education_visibility' => ['required', 'in:public,private'],
            'work_experience' => ['nullable', 'array', 'max:10'],
            'work_experience.*.role' => ['nullable', 'string', 'max:160'],
            'work_experience.*.company' => ['nullable', 'string', 'max:160'],
            'work_experience.*.period' => ['nullable', 'string', 'max:80'],
            'work_experience.*.description' => ['nullable', 'string', 'max:800'],
            'work_experience_visibility' => ['required', 'in:public,private'],
            'cv' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        foreach (['education', 'work_experience'] as $section) {
            $data[$section] = collect($data[$section] ?? [])
                ->filter(fn (array $entry) => collect($entry)->filter()->isNotEmpty())
                ->values()
                ->all();
        }

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

        unset($data['cv'], $data['profile_photo'], $data['remove_photo']);
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
