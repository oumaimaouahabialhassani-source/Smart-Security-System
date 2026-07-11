<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;

class UserPolicy
{
    /**
     * Only administrators may open the Users module.
     */
    public function viewAny(User $actor): bool
    {
        return $actor->role->canManageUsers();
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->role->canManageUsers();
    }

    public function create(User $actor): bool
    {
        return $actor->role->canManageUsers();
    }

    public function update(User $actor, User $target): bool
    {
        return $actor->role->canManageUsers();
    }

    /**
     * Deleting yourself or the last active administrator is refused.
     */
    public function delete(User $actor, User $target): bool
    {
        return $actor->role->canManageUsers()
            && ! $target->is($actor)
            && ! self::isLastActiveAdministrator($target);
    }

    /**
     * True when this user is the only administrator still able
     * to sign in — the account the system must never lose.
     */
    public static function isLastActiveAdministrator(User $user): bool
    {
        return $user->role === UserRole::Administrator
            && $user->status === UserStatus::Active
            && User::where('role', UserRole::Administrator)
                ->where('status', UserStatus::Active)
                ->whereKeyNot($user->getKey())
                ->doesntExist();
    }
}
