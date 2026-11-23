<?php

declare(strict_types=1);

namespace App\Http\Requests\RentalEquipment;

use App\Models\RentalEquipment;
use Illuminate\Foundation\Http\FormRequest;

class StoreRentalEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', RentalEquipment::class);
    }

    protected function prepareForValidation(): void
    {
        $rawAmount = str_replace([',', ' '], '', (string) $this->input('rental_amount', '0'));

        $this->merge([
            'contractor_name' => $this->filled('contractor_name') ? trim((string) $this->input('contractor_name')) : null,
            'equipment_type' => $this->filled('equipment_type') ? trim((string) $this->input('equipment_type')) : null,
            'rental_amount' => $rawAmount !== '' ? $rawAmount : 0,
            'rental_type' => $this->filled('rental_type') ? trim((string) $this->input('rental_type')) : null,
            'branch' => $this->filled('branch') ? trim((string) $this->input('branch')) : null,
            'project' => $this->filled('project') ? trim((string) $this->input('project')) : null,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'contractor_name' => ['nullable', 'string', 'max:180'],
            'equipment_type' => ['nullable', 'string', 'max:120'],
            'rental_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'rental_type' => ['nullable', 'string', 'max:80'],
            'branch' => ['nullable', 'string', 'max:120'],
            'project' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
