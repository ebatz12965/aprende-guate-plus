<?php

namespace App\Policies;

use App\Models\MentorshipSession;
use App\Models\User;

class MentorshipSessionPolicy
{
    /** Estudiante o mentor de la sesión: conversar, leer mensajes y cancelar. */
    public function participate(User $user, MentorshipSession $session): bool
    {
        return in_array($user->id, [$session->student_id, $session->mentor_id], true);
    }

    /** Solo el mentor asignado acepta o rechaza la solicitud. */
    public function respond(User $user, MentorshipSession $session): bool
    {
        return $session->mentor_id === $user->id;
    }
}
