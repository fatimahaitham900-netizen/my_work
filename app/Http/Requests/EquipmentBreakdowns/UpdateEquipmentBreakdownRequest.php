<?php

declare(strict_types=1);

namespace App\Http\Requests\EquipmentBreakdowns;

use App\Enums\BreakdownCategory;
use App\Enums\BreakdownSeverity;
use App\Enums\DriverStoppageStatus;
use App\Enums\RepairStatus;
use App\Models\EquipmentBreakdown;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEquipmentBreakdownRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var EquipmentBreakdown|null $breakdown */
        $breakdown = $this->route('equipmentBreakdown');

        return $breakdown !== null && (bool) $this->user()?->can('update', $breakdown);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'equipment_code' => $this->filled('equipment_code') ? trim((string) $this->input('equipment_code')) : null,
            'equipment_name' => $this->filled('equipment_name') ? trim((string) $this->input('equipment_name')) : null,
            'brand' => $this->filled('brand') ? trim((string) $this->input('brand')) : null,
            'workshop_name' => $this->filled('workshop_name') ? trim((string) $this->input('workshop_name')) : null,
            'category' => $this->filled('category') ? $this->input('category') : BreakdownCategory::Mechanical->value,
            'severity' => $this->filled('severity') ? $this->input('severity') : BreakdownSeverity::Medium->value,
            'breakdown_date' => $this->filled('breakdown_date') ? $this->input('breakdown_date') : null,
            'workshop_entry_date' => $this->filled('workshop_entry_date') ? $this->input('workshop_entry_date') : null,
            'expected_repair_date' => $this->filled('expected_repair_date') ? $this->input('expected_repair_date') : null,
            'fault_description' => $this->filled('fault_description') ? trim((string) $this->input('fault_description')) : null,
            'repair_status' => $this->filled('repair_status') ? $this->input('repair_status') : RepairStatus::InWorkshop->value,
            'workshop_status' => $this->filled('workshop_status') ? trim((string) $this->input('workshop_status')) : null,
            'spare_parts_delay' => $this->boolean('spare_parts_delay'),
            'resolve_linked_stoppages' => $this->boolean('resolve_linked_stoppages'),
            'linked_stoppage_status' => $this->filled('linked_stoppage_status')
                ? $this->input('linked_stoppage_status')
                : DriverStoppageStatus::Resolved->value,
            'estimated_cost' => $this->filled('estimated_cost') ? $this->input('estimated_cost') : 0,
            'cost_note' => $this->filled('cost_note') ? trim((string) $this->input('cost_note')) : null,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $afterBreakdownDate = $this->filled('breakdown_date') ? ['after_or_equal:breakdown_date'] : [];

        return [
            'equipment_code' => ['nullable', 'string', 'max:50'],
            'equipment_name' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:80'],
            'workshop_name' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', Rule::enum(BreakdownCategory::class)],
            'severity' => ['nullable', Rule::enum(BreakdownSeverity::class)],
            'breakdown_date' => ['nullable', 'date'],
            'workshop_entry_date' => ['nullable', 'date', ...$afterBreakdownDate],
            'expected_repair_date' => ['nullable', 'date', ...$afterBreakdownDate],
            'fault_description' => ['nullable', 'string', 'max:2000'],
            'repair_status' => ['nullable', Rule::enum(RepairStatus::class)],
            'workshop_status' => ['nullable', 'string', 'max:100'],
            'spare_parts_delay' => ['nullable', 'boolean'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'cost_note' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'resolve_linked_stoppages' => ['nullable', 'boolean'],
            'linked_stoppage_status' => ['nullable', Rule::in([
                DriverStoppageStatus::Resolved->value,
                DriverStoppageStatus::Reassigned->value,
            ])],
        ];
    }
}
