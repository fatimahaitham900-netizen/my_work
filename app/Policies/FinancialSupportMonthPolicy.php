<?php

declare(strict_types=1);

namespace App\Policies;

use App\Policies\Concerns\AuthorizesFleetDeviations;

class FinancialSupportMonthPolicy
{
    use AuthorizesFleetDeviations;
}
