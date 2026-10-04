<?php

namespace App\Policies;

use App\Models\DiagnosticAttempt;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DiagnosticAttemptPolicy
{
    public function view(User $user, DiagnosticAttempt $attempt): Response
    {
        return $attempt->user_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }
}
