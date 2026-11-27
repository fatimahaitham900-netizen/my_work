<?php

declare(strict_types=1);

namespace App\Http\Requests\EquipmentDistributions;

use App\Models\EquipmentDistribution;
use Illuminate\Foundation\Http\FormRequest;

class StoreEquipmentDistributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', EquipmentDistribution::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'equipment_code' => ['nullable', 'string', 'max:80'],
            'equipment_type' => ['required', 'string', 'max:150'],
            'branch' => ['nullable', 'string', 'max:120'],
            'site_location' => ['nullable', 'string', 'max:200'],
            'equipment_status' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
