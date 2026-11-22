<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait AuthorizesFleetDeviations
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Model $model): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->canManageDeviations();
    }

    public function update(User $user, Model $model): bool
    {
        return $user->canManageDeviations();
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->canDeleteDeviations();
    }

    public function restore(User $user, Model $model): bool
    {
        return $user->canDeleteDeviations();
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
