<?php

declare(strict_types=1);

namespace App\Http\Requests\RentalEquipment;

use App\Models\RentalEquipment;
use Illuminate\Foundation\Http\FormRequest;

class FilterRentalEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', RentalEquipment::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'view' => ['nullable', 'string', 'in:table,analysis'],
            'search' => ['nullable', 'string', 'max:120'],
            'contractor_name' => ['nullable', 'string', 'max:150'],
            'equipment_type' => ['nullable', 'string', 'max:120'],
            'rental_type' => ['nullable', 'string', 'max:80'],
            'branch' => ['nullable', 'string', 'max:120'],
            'project' => ['nullable', 'string', 'max:150'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return $this->validated();
    }
}
