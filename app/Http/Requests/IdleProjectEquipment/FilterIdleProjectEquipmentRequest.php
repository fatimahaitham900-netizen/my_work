<?php

declare(strict_types=1);

namespace App\Http\Requests\IdleProjectEquipment;

use App\Models\IdleProjectEquipment;
use Illuminate\Foundation\Http\FormRequest;

class FilterIdleProjectEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', IdleProjectEquipment::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'branch' => ['nullable', 'string', 'max:100'],
            'site_location' => ['nullable', 'string', 'max:150'],
            'equipment_type' => ['nullable', 'string', 'max:120'],
            'idle_band' => ['nullable', 'in:high,medium,low'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return $this->safe()->only([
            'search',
            'branch',
            'site_location',
            'equipment_type',
            'idle_band',
        ]);
    }
}
