<?php

namespace App\Policies;

use App\Models\MentorProfile;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MentorProfilePolicy
{
    /** Solo se pueden solicitar sesiones a perfiles cuyo usuario conserva el rol Instructor. */
    public function requestSession(User $user, MentorProfile $profile): Response
    {
        return $profile->user?->hasRole('Instructor') ? Response::allow() : Response::denyAsNotFound();
    }
}
