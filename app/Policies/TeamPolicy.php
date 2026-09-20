<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function view(User $user, Team $team): bool
    {
        return $team->owner_id === $user->id
            || $team->users()->whereKey($user->id)->exists();
    }

    public function manageMembers(User $user, Team $team): bool
    {
        if ($team->owner_id === $user->id) {
            return true;
        }

        return $team->users()
            ->whereKey($user->id)
            ->wherePivot('role', 'admin')
            ->exists();
    }

    public function updateRoles(User $user, Team $team): bool
    {
        return $team->owner_id === $user->id;
    }
}
