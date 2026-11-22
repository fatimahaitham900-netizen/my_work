<?php

declare(strict_types=1);

namespace App\Http\Requests\CarBreakdowns;

use App\Models\CarBreakdown;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCarBreakdownRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var CarBreakdown|null $carBreakdown */
        $carBreakdown = $this->route('carBreakdown');

        return $carBreakdown !== null && (bool) $this->user()?->can('update', $carBreakdown);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'car_code' => $this->filled('car_code') ? trim((string) $this->input('car_code')) : null,
            'car_type' => $this->filled('car_type') ? trim((string) $this->input('car_type')) : null,
            'plate_number' => $this->filled('plate_number') ? trim((string) $this->input('plate_number')) : null,
            'model_year' => $this->filled('model_year') ? trim((string) $this->input('model_year')) : null,
            'previous_receiver' => $this->filled('previous_receiver') ? trim((string) $this->input('previous_receiver')) : null,
            'employee_id' => $this->filled('employee_id') ? trim((string) $this->input('employee_id')) : null,
            'department' => $this->filled('department') ? trim((string) $this->input('department')) : null,
            'job_title' => $this->filled('job_title') ? trim((string) $this->input('job_title')) : null,
            'fuel_type' => $this->filled('fuel_type') ? trim((string) $this->input('fuel_type')) : null,
            'branch' => $this->filled('branch') ? trim((string) $this->input('branch')) : null,
            'condition_status' => $this->filled('condition_status') ? trim((string) $this->input('condition_status')) : null,
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'car_code' => ['nullable', 'string', 'max:50'],
            'car_type' => ['nullable', 'string', 'max:150'],
            'plate_number' => ['nullable', 'string', 'max:60'],
            'model_year' => ['nullable', 'string', 'max:20'],
            'previous_receiver' => ['nullable', 'string', 'max:150'],
            'employee_id' => ['nullable', 'string', 'max:50'],
            'department' => ['nullable', 'string', 'max:120'],
            'job_title' => ['nullable', 'string', 'max:150'],
            'fuel_type' => ['nullable', 'string', 'max:40'],
            'branch' => ['nullable', 'string', 'max:100'],
            'condition_status' => ['nullable', 'string', 'max:500'],
        ];
    }
}
