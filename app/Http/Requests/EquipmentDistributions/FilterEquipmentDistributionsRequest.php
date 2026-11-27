<?php

declare(strict_types=1);

namespace App\Http\Requests\EquipmentDistributions;

use App\Http\Requests\BaseModuleFilterRequest;
use App\Models\EquipmentDistribution;

class FilterEquipmentDistributionsRequest extends BaseModuleFilterRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', EquipmentDistribution::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            ...$this->baseFilterRules(),
            'view' => ['nullable', 'string', 'in:table,summary,site_summary,equipment_drivers'],
            'equipment_type' => ['nullable', 'string', 'max:150'],
            'branch' => ['nullable', 'string', 'max:120'],
            'site_location' => ['nullable', 'string', 'max:200'],
            'equipment_status' => ['nullable', 'string', 'max:150'],
        ];
    }
}
