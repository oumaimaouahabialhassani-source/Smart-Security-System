<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;

class UserPolicy
{
    /**
     * Only admins (Super Admin passes through Gate::before) may open
     * the Users module.
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

    /**
     * The Super Admin account is untouchable by everyone else; the
     * Super Admin edits it via the Profile page or this module.
     */
    public function update(User $actor, User $target): bool
    {
        return $actor->role->canManageUsers()
            && ($target->role !== UserRole::SuperAdmin || $actor->is($target));
    }

    /**
     * Deleting yourself or the last active Super Admin is refused —
     * even for the Super Admin.
     */
    public function delete(User $actor, User $target): bool
    {
        return $actor->role->canManageUsers()
            && ! $target->is($actor)
            && ! self::isLastActiveAdministrator($target);
    }

    /**
     * Promoting/demoting is the Super Admin's exclusive right — and
     * never on themselves. Demoting the last Super Admin is blocked
     * in the controller (it depends on the submitted role).
     */
    public function changeRole(User $actor, User $target): bool
    {
        return $actor->role->canAssignRoles()
            && ! $target->is($actor);
    }

    /**
     * True when this user is the only admin-level account still able
     * to sign in — the account the system must never lose.
     */
    public static function isLastActiveAdministrator(User $user): bool
    {
        return $user->role === UserRole::SuperAdmin
            && $user->status === UserStatus::Active
            && User::where('role', UserRole::SuperAdmin)
                ->where('status', UserStatus::Active)
                ->whereKeyNot($user->getKey())
                ->doesntExist();
    }
}
