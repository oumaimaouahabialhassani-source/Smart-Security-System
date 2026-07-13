<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Camera;
use App\Models\User;

class CameraPolicy
{
    /**
     * Every authenticated user may consult the camera list.
     */
    public function viewAny(User $actor): bool
    {
        return true;
    }

    public function view(User $actor, Camera $camera): bool
    {
        return true;
    }

    public function create(User $actor): bool
    {
        return $actor->role->canManageHardware();
    }

    public function update(User $actor, Camera $camera): bool
    {
        return $actor->role->canManageHardware();
    }

    public function delete(User $actor, Camera $camera): bool
    {
        return $actor->role->isAdmin();
    }
}
