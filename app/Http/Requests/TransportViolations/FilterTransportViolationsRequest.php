<?php

declare(strict_types=1);

namespace App\Http\Requests\TransportViolations;

use App\Http\Requests\BaseModuleFilterRequest;
use App\Models\TransportViolation;

class FilterTransportViolationsRequest extends BaseModuleFilterRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', TransportViolation::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            ...$this->baseFilterRules(),
            'year' => ['nullable', 'string', 'max:20'],
            'record_holder' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:100'],
            'objection_status' => ['nullable', 'string', 'max:200'],
            'employee_status' => ['nullable', 'string', 'max:100'],
        ];
    }
}
