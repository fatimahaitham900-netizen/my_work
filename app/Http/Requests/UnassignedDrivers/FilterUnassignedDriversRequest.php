<?php

declare(strict_types=1);

namespace App\Http\Requests\UnassignedDrivers;

use App\Enums\UnassignedDriverStatus;
use App\Http\Requests\BaseModuleFilterRequest;
use App\Models\UnassignedDriver;
use Illuminate\Validation\Rule;

class FilterUnassignedDriversRequest extends BaseModuleFilterRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', UnassignedDriver::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            ...$this->baseFilterRules(),
            'status' => ['nullable', Rule::enum(UnassignedDriverStatus::class)],
            'location' => ['nullable', 'string', 'max:100'],
            'license_state' => ['nullable', 'string', 'in:expired,expiring_soon,valid'],
        ];
    }
}
