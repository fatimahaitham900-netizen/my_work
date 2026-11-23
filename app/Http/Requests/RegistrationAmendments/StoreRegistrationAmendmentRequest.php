<?php

declare(strict_types=1);

namespace App\Http\Requests\RegistrationAmendments;

use App\Models\RegistrationAmendment;
use Illuminate\Foundation\Http\FormRequest;

class StoreRegistrationAmendmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', RegistrationAmendment::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'equipment_code' => $this->filled('equipment_code') ? trim((string) $this->input('equipment_code')) : null,
            'equipment_type' => $this->filled('equipment_type') ? trim((string) $this->input('equipment_type')) : null,
            'branch' => $this->filled('branch') ? trim((string) $this->input('branch')) : null,
            'stoppage_date' => $this->filled('stoppage_date') ? trim((string) $this->input('stoppage_date')) : null,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'equipment_code' => ['nullable', 'string', 'max:60'],
            'equipment_type' => ['nullable', 'string', 'max:150'],
            'branch' => ['nullable', 'string', 'max:120'],
            'stoppage_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

