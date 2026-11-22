<?php

declare(strict_types=1);

namespace App\Http\Requests\FinancialSupport;

use App\Models\FinancialSupportMonth;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFinancialSupportMonthRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var FinancialSupportMonth|null $month */
        $month = $this->route('financialSupportMonth');

        return $month !== null && (bool) $this->user()?->can('update', $month);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'repair_support_amount' => $this->filled('repair_support_amount') ? $this->input('repair_support_amount') : 0,
            'custody_amount' => $this->filled('custody_amount') ? $this->input('custody_amount') : 0,
            'not_transferred_amount' => $this->filled('not_transferred_amount') ? $this->input('not_transferred_amount') : null,
            'total_equipment_count' => $this->filled('total_equipment_count') ? $this->input('total_equipment_count') : 0,
            'repaired_count' => $this->filled('repaired_count') ? $this->input('repaired_count') : 0,
            'under_repair_count' => $this->filled('under_repair_count') ? $this->input('under_repair_count') : 0,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'repair_support_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'custody_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'not_transferred_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'total_equipment_count' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'repaired_count' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'under_repair_count' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
