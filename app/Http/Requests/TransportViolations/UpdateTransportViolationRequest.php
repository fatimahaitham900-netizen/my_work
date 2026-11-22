<?php

declare(strict_types=1);

namespace App\Http\Requests\TransportViolations;

use App\Models\TransportViolation;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTransportViolationRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var TransportViolation|null $violation */
        $violation = $this->route('transportViolation');

        return $violation !== null && (bool) $this->user()?->can('update', $violation);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'violation_number' => $this->filled('violation_number') ? trim((string) $this->input('violation_number')) : null,
            'record_holder' => $this->filled('record_holder') ? trim((string) $this->input('record_holder')) : null,
            'violation_date' => $this->filled('violation_date') ? $this->input('violation_date') : null,
            'amount' => $this->filled('amount') ? $this->input('amount') : 0,
            'department' => $this->filled('department') ? trim((string) $this->input('department')) : null,
            'city' => $this->filled('city') ? trim((string) $this->input('city')) : null,
            'violation_description' => $this->filled('violation_description') ? trim((string) $this->input('violation_description')) : null,
            'objection_status' => $this->filled('objection_status') ? trim((string) $this->input('objection_status')) : null,
            'plate_number' => $this->filled('plate_number') ? trim((string) $this->input('plate_number')) : null,
            'equipment_code' => $this->filled('equipment_code') ? trim((string) $this->input('equipment_code')) : null,
            'driver_name' => $this->filled('driver_name') ? trim((string) $this->input('driver_name')) : null,
            'employee_id' => $this->filled('employee_id') ? trim((string) $this->input('employee_id')) : null,
            'employee_status' => $this->filled('employee_status') ? trim((string) $this->input('employee_status')) : null,
            'liability' => $this->filled('liability') ? trim((string) $this->input('liability')) : null,
            'permit_status' => $this->filled('permit_status') ? trim((string) $this->input('permit_status')) : null,
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'violation_number' => ['nullable', 'string', 'max:60'],
            'record_holder' => ['nullable', 'string', 'max:120'],
            'violation_date' => ['nullable', 'date'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'department' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:100'],
            'violation_description' => ['nullable', 'string', 'max:1000'],
            'objection_status' => ['nullable', 'string', 'max:200'],
            'plate_number' => ['nullable', 'string', 'max:60'],
            'equipment_code' => ['nullable', 'string', 'max:50'],
            'driver_name' => ['nullable', 'string', 'max:150'],
            'employee_id' => ['nullable', 'string', 'max:50'],
            'employee_status' => ['nullable', 'string', 'max:100'],
            'liability' => ['nullable', 'string', 'max:120'],
            'permit_status' => ['nullable', 'string', 'max:200'],
        ];
    }
}
