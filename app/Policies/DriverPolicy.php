<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Driver;
use App\Models\User;

class DriverPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Driver $driver): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->canManageDeviations();
    }

    public function update(User $user, Driver $driver): bool
    {
        return $user->canManageDeviations();
    }

    public function delete(User $user, Driver $driver): bool
    {
        return $user->canDeleteDeviations();
    }
}
