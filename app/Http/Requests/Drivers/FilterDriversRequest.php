<?php

declare(strict_types=1);

namespace App\Http\Requests\Drivers;

use App\Http\Requests\BaseModuleFilterRequest;
use App\Models\Driver;

class FilterDriversRequest extends BaseModuleFilterRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', Driver::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            ...$this->baseFilterRules(),
            'view' => ['nullable', 'string', 'in:table,summary,equipment_type_summary,workshop_staff'],
            'equipment_type' => ['nullable', 'string', 'max:150'],
            'branch' => ['nullable', 'string', 'max:120'],
            'site_location' => ['nullable', 'string', 'max:200'],
            'job_title' => ['nullable', 'string', 'max:150'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'sponsor' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'string', 'max:60'],
        ];
    }
}
