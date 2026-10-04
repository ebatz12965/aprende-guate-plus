<?php

namespace App\Policies;

use App\Models\Challenge;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ChallengePolicy
{
    /** Retos personales de una ruta que ya terminaron de generarse. */
    public function view(User $user, Challenge $challenge): Response
    {
        return $challenge->user_id === $user->id && $challenge->learning_path_id && $challenge->generation_status === 'completed'
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
