<?php

namespace App\Policies;

use App\Models\LearningPathTopic;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LearningPathTopicPolicy
{
    /** Solo se trabaja sobre temas de la ruta activa del propio estudiante. */
    public function view(User $user, LearningPathTopic $topic): Response
    {
        $path = $topic->module?->learningPath;

        return $path && $path->user_id === $user->id && $path->status === 'active'
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
