<?php

namespace App\Policies;

use App\Models\ChallengeSubmission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ChallengeSubmissionPolicy
{
    public function view(User $user, ChallengeSubmission $submission): Response
    {
        return $submission->user_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }
}
