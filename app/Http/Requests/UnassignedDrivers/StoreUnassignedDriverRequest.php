<?php

declare(strict_types=1);

namespace App\Http\Requests\UnassignedDrivers;

use App\Enums\UnassignedDriverStatus;
use App\Models\UnassignedDriver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnassignedDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', UnassignedDriver::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'driver_name' => $this->filled('driver_name') ? trim((string) $this->input('driver_name')) : null,
            'employee_id' => $this->filled('employee_id') ? trim((string) $this->input('employee_id')) : null,
            'job_title' => $this->filled('job_title') ? trim((string) $this->input('job_title')) : null,
            'location' => $this->filled('location') ? trim((string) $this->input('location')) : null,
            'license_type' => $this->filled('license_type') ? trim((string) $this->input('license_type')) : null,
            'license_expiry' => $this->filled('license_expiry') ? $this->input('license_expiry') : null,
            'unassigned_since' => $this->filled('unassigned_since') ? $this->input('unassigned_since') : null,
            'status' => $this->filled('status') ? $this->input('status') : UnassignedDriverStatus::Ready->value,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'driver_name' => ['nullable', 'string', 'max:150'],
            'employee_id' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:100'],
            'license_type' => ['nullable', 'string', 'max:80'],
            'license_expiry' => ['nullable', 'date'],
            'unassigned_since' => ['nullable', 'date'],
            'status' => ['nullable', Rule::enum(UnassignedDriverStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
