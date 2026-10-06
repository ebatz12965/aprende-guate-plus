<?php

namespace App\Policies;

use App\Models\InterviewSimulation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/** Cada simulación es solo de quien la creó; para cualquier otra persona no existe (404). */
class InterviewSimulationPolicy
{
    public function view(User $user, InterviewSimulation $simulation): Response
    {
        return $simulation->user_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }

    public function delete(User $user, InterviewSimulation $simulation): Response
    {
        return $this->view($user, $simulation);
    }
}
