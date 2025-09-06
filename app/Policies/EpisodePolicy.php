<?php

namespace App\Policies;

use App\Models\Episode;
use App\Models\User;

class EpisodePolicy
{
    public function view(?User $user, Episode $episode): bool
    {
        // anyone logged in can view the show page
        return (bool) $user;
    }

    public function update(User $user, Episode $episode): bool
    {
        return $user->id === $episode->user_id || (bool)($user->is_admin ?? false);
    }

    public function delete(User $user, Episode $episode): bool
    {
        return $user->id === $episode->user_id || (bool)($user->is_admin ?? false);
    }
}
