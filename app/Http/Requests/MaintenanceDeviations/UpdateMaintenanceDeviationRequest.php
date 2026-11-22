<?php

declare(strict_types=1);

namespace App\Http\Requests\MaintenanceDeviations;

use App\Models\MaintenanceDeviation;

class UpdateMaintenanceDeviationRequest extends StoreMaintenanceDeviationRequest
{
    public function authorize(): bool
    {
        /** @var MaintenanceDeviation|null $record */
        $record = $this->route('maintenanceDeviation');

        return $record !== null && (bool) $this->user()?->can('update', $record);
    }
}
