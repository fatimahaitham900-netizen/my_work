<?php

declare(strict_types=1);

namespace App\Http\Requests\MaintenanceDeviations;

use App\Models\MaintenanceDeviation;
use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceDeviationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', MaintenanceDeviation::class);
    }

    protected function prepareForValidation(): void
    {
        $rowType = $this->filled('row_type') ? (string) $this->input('row_type') : 'active';
        $isActive = $rowType === 'active';

        $dailyRate = $isActive && $this->filled('daily_maintenance_rate')
            ? (float) $this->input('daily_maintenance_rate')
            : null;
        $preventiveRate = $isActive && $this->filled('preventive_maintenance_rate')
            ? (float) $this->input('preventive_maintenance_rate')
            : null;

        $dailyDeviation = $isActive && $this->filled('daily_deviation_rate')
            ? (float) $this->input('daily_deviation_rate')
            : ($dailyRate !== null ? max(0, 100 - $dailyRate) : null);

        $preventiveDeviation = $isActive && $this->filled('preventive_deviation_rate')
            ? (float) $this->input('preventive_deviation_rate')
            : ($preventiveRate !== null ? max(0, 100 - $preventiveRate) : null);

        $this->merge([
            'day_number' => $this->filled('day_number') ? (int) $this->input('day_number') : null,
            'row_type' => $rowType,
            'daily_maintenance_rate' => $dailyRate,
            'preventive_maintenance_rate' => $preventiveRate,
            'daily_deviation_rate' => $dailyDeviation,
            'preventive_deviation_rate' => $preventiveDeviation,
            'status_note' => $this->filled('status_note')
                ? trim((string) $this->input('status_note'))
                : ($rowType === 'on_leave' ? 'مسؤول الصيانة مجاز' : null),
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'day_number' => ['nullable', 'integer', 'min:1', 'max:366'],
            'row_type' => ['nullable', 'in:active,on_leave,day_off'],
            'daily_maintenance_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'preventive_maintenance_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'daily_deviation_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'preventive_deviation_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status_note' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
