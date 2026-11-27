<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EquipmentDistribution;
use App\Models\User;

class EquipmentDistributionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, EquipmentDistribution $equipmentDistribution): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->canManageDeviations();
    }

    public function update(User $user, EquipmentDistribution $equipmentDistribution): bool
    {
        return $user->canManageDeviations();
    }

    public function delete(User $user, EquipmentDistribution $equipmentDistribution): bool
    {
        return $user->canDeleteDeviations();
    }
}
