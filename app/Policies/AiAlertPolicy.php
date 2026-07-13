<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AiAlert;
use App\Models\User;

class AiAlertPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->role->canUseAiBot();
    }

    public function view(User $actor, AiAlert $alert): bool
    {
        return $actor->role->canUseAiBot();
    }

    /**
     * Security Operators monitor read-only; only admins modify.
     */
    public function update(User $actor, AiAlert $alert): bool
    {
        return $actor->role->canManageAlerts();
    }

    public function delete(User $actor, AiAlert $alert): bool
    {
        return $actor->role->isAdmin();
    }
}
