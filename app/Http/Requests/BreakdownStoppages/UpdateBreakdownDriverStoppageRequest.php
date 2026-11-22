<?php

declare(strict_types=1);

namespace App\Http\Requests\BreakdownStoppages;

use App\Enums\DriverStoppageStatus;
use App\Models\BreakdownDriverStoppage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBreakdownDriverStoppageRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var BreakdownDriverStoppage|null $stoppage */
        $stoppage = $this->route('breakdownStoppage');

        return $stoppage !== null && (bool) $this->user()?->can('update', $stoppage);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'driver_name' => $this->filled('driver_name') ? trim((string) $this->input('driver_name')) : null,
            'employee_id' => $this->filled('employee_id') ? trim((string) $this->input('employee_id')) : null,
            'job_title' => $this->filled('job_title') ? trim((string) $this->input('job_title')) : null,
            'branch' => $this->filled('branch') ? trim((string) $this->input('branch')) : null,
            'equipment_code' => $this->filled('equipment_code') ? trim((string) $this->input('equipment_code')) : null,
            'equipment_type' => $this->filled('equipment_type') ? trim((string) $this->input('equipment_type')) : null,
            'breakdown_id' => $this->filled('breakdown_id') ? (int) $this->input('breakdown_id') : null,
            'stoppage_start_date' => $this->filled('stoppage_start_date') ? $this->input('stoppage_start_date') : null,
            'expected_resume_date' => $this->filled('expected_resume_date') ? $this->input('expected_resume_date') : null,
            'fault_diagnosis' => $this->filled('fault_diagnosis') ? trim((string) $this->input('fault_diagnosis')) : null,
            'workshop_status' => $this->filled('workshop_status') ? trim((string) $this->input('workshop_status')) : null,
            'action_taken' => $this->filled('action_taken') ? trim((string) $this->input('action_taken')) : null,
            'phone' => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
            'status' => $this->filled('status') ? $this->input('status') : DriverStoppageStatus::Waiting->value,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $afterStartDate = $this->filled('stoppage_start_date') ? ['after_or_equal:stoppage_start_date'] : [];

        return [
            'driver_name' => ['nullable', 'string', 'max:150'],
            'employee_id' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'branch' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'equipment_code' => ['nullable', 'string', 'max:50'],
            'equipment_type' => ['nullable', 'string', 'max:100'],
            'breakdown_id' => ['nullable', 'integer', Rule::exists('equipment_breakdowns', 'id')->whereNull('deleted_at')],
            'stoppage_start_date' => ['nullable', 'date'],
            'expected_resume_date' => ['nullable', 'date', ...$afterStartDate],
            'fault_diagnosis' => ['nullable', 'string', 'max:2000'],
            'workshop_status' => ['nullable', 'string', 'max:120'],
            'action_taken' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', Rule::enum(DriverStoppageStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
