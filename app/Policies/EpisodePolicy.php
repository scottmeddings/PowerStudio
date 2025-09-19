<?php

// app/Policies/EpisodePolicy.php
namespace App\Policies;

use App\Models\Episode;
use App\Models\User;

class EpisodePolicy
{
    public function viewAny(User $user): bool
    {
        return true; // listing is allowed; we’ll filter by owner in controller
    }

    public function view(User $user, Episode $ep): bool
    {
        return $user->isAdmin() || $ep->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true; // any logged-in user can create their own
    }

    public function update(User $user, Episode $ep): bool
    {
        return $user->isAdmin() || $ep->user_id === $user->id;
    }

    public function delete(User $user, Episode $ep): bool
    {
        return $user->isAdmin() || $ep->user_id === $user->id;
    }
}

