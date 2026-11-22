<?php

declare(strict_types=1);

namespace App\Http\Requests\MaintenanceDeviations;

use App\Http\Requests\BaseModuleFilterRequest;
use App\Models\MaintenanceDeviation;

class FilterMaintenanceDeviationsRequest extends BaseModuleFilterRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', MaintenanceDeviation::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            ...$this->baseFilterRules(),
            'row_type' => ['nullable', 'in:active,on_leave,day_off'],
        ];
    }
}
