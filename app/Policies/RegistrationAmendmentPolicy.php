<?php

declare(strict_types=1);

namespace App\Policies;

use App\Policies\Concerns\AuthorizesFleetDeviations;

class RegistrationAmendmentPolicy
{
    use AuthorizesFleetDeviations;
}

