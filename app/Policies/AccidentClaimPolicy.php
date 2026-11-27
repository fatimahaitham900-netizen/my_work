<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AccidentClaim;
use App\Models\User;

class AccidentClaimPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, AccidentClaim $accidentClaim): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->canManageDeviations();
    }

    public function update(User $user, AccidentClaim $accidentClaim): bool
    {
        return $user->canManageDeviations();
    }

    public function delete(User $user, AccidentClaim $accidentClaim): bool
    {
        return $user->canDeleteDeviations();
    }
}
