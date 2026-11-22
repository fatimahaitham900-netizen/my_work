<?php

declare(strict_types=1);

namespace App\Http\Requests\EquipmentBreakdowns;

use App\Enums\BreakdownCategory;
use App\Enums\BreakdownSeverity;
use App\Enums\RepairStatus;
use App\Http\Requests\BaseModuleFilterRequest;
use App\Models\EquipmentBreakdown;
use Illuminate\Validation\Rule;

class FilterEquipmentBreakdownsRequest extends BaseModuleFilterRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', EquipmentBreakdown::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            ...$this->baseFilterRules(),
            'workshop_name' => ['nullable', 'string', 'max:120'],
            'workshop_status' => ['nullable', 'string', 'max:100'],
            'equipment_name' => ['nullable', 'string', 'max:120'],
            'brand' => ['nullable', 'string', 'max:120'],
            'view_mode' => ['nullable', 'in:list,by_brand,by_equipment_name'],
            'category' => ['nullable', Rule::enum(BreakdownCategory::class)],
            'severity' => ['nullable', Rule::enum(BreakdownSeverity::class)],
            'repair_status' => ['nullable', Rule::enum(RepairStatus::class)],
            'spare_parts_delay' => ['nullable', 'in:0,1'],
        ];
    }
}
