<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Device;
use App\Models\User;

class DevicePolicy
{
    /**
     * Every authenticated user may consult the device list.
     */
    public function viewAny(User $actor): bool
    {
        return true;
    }

    public function view(User $actor, Device $device): bool
    {
        return true;
    }

    public function create(User $actor): bool
    {
        return $actor->role->canManageHardware();
    }

    public function update(User $actor, Device $device): bool
    {
        return $actor->role->canManageHardware();
    }

    public function delete(User $actor, Device $device): bool
    {
        return $actor->role->isAdmin();
    }
}
