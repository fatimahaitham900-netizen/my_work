<?php

declare(strict_types=1);

namespace App\Http\Requests\BreakdownStoppages;

use App\Enums\DriverStoppageStatus;
use App\Http\Requests\BaseModuleFilterRequest;
use App\Models\BreakdownDriverStoppage;
use Illuminate\Validation\Rule;

class FilterBreakdownDriverStoppagesRequest extends BaseModuleFilterRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', BreakdownDriverStoppage::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            ...$this->baseFilterRules(),
            'status' => ['nullable', Rule::enum(DriverStoppageStatus::class)],
            'branch' => ['nullable', 'string', 'max:100'],
            'workshop_status' => ['nullable', 'string', 'max:120'],
            'idle_threshold' => ['nullable', 'string', 'in:over_3,over_7'],
        ];
    }
}
