<?php

declare(strict_types=1);

namespace App\Http\Requests\IdleProjectEquipment;

use App\Models\IdleProjectEquipment;
use Illuminate\Foundation\Http\FormRequest;

class UpdateIdleProjectEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var IdleProjectEquipment|null $item */
        $item = $this->route('idleProjectEquipment');

        return $item !== null && (bool) $this->user()?->can('update', $item);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'equipment_code' => $this->filled('equipment_code') ? trim((string) $this->input('equipment_code')) : null,
            'equipment_type' => $this->filled('equipment_type') ? trim((string) $this->input('equipment_type')) : null,
            'driver_name' => $this->filled('driver_name') ? trim((string) $this->input('driver_name')) : null,
            'employee_id' => $this->filled('employee_id') ? trim((string) $this->input('employee_id')) : null,
            'branch' => $this->filled('branch') ? trim((string) $this->input('branch')) : null,
            'site_location' => $this->filled('site_location') ? trim((string) $this->input('site_location')) : null,
            'equipment_status' => $this->filled('equipment_status') ? trim((string) $this->input('equipment_status')) : null,
            'total_idle_days' => $this->filled('total_idle_days') ? $this->input('total_idle_days') : 0,
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'equipment_code' => ['nullable', 'string', 'max:50'],
            'equipment_type' => ['nullable', 'string', 'max:120'],
            'driver_name' => ['nullable', 'string', 'max:150'],
            'employee_id' => ['nullable', 'string', 'max:50'],
            'branch' => ['nullable', 'string', 'max:100'],
            'site_location' => ['nullable', 'string', 'max:150'],
            'equipment_status' => ['nullable', 'string', 'max:120'],
            'total_idle_days' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ];
    }
}
