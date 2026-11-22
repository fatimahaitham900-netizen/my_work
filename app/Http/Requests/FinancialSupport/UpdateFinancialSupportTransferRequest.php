<?php

declare(strict_types=1);

namespace App\Http\Requests\FinancialSupport;

use App\Models\FinancialSupportTransfer;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFinancialSupportTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var FinancialSupportTransfer|null $transfer */
        $transfer = $this->route('financialSupportTransfer');

        return $transfer !== null && (bool) $this->user()?->can('update', $transfer);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'support_year' => $this->filled('support_year') ? $this->input('support_year') : 2026,
            'support_month' => $this->filled('support_month') ? $this->input('support_month') : 8,
            'equipment_type' => $this->filled('equipment_type') ? trim((string) $this->input('equipment_type')) : null,
            'equipment_code' => $this->filled('equipment_code') ? trim((string) $this->input('equipment_code')) : null,
            'amount' => $this->filled('amount') ? $this->input('amount') : 0,
            'beneficiary_name' => $this->filled('beneficiary_name') ? trim((string) $this->input('beneficiary_name')) : null,
            'fault_type' => $this->filled('fault_type') ? trim((string) $this->input('fault_type')) : null,
            'branch' => $this->filled('branch') ? trim((string) $this->input('branch')) : null,
            'case_status' => $this->filled('case_status') ? trim((string) $this->input('case_status')) : null,
            'po_number' => $this->filled('po_number') ? trim((string) $this->input('po_number')) : null,
            'transfer_stage' => $this->filled('transfer_stage') ? trim((string) $this->input('transfer_stage')) : null,
            'transfer_date' => $this->filled('transfer_date') ? trim((string) $this->input('transfer_date')) : null,
            'supply_date' => $this->filled('supply_date') ? trim((string) $this->input('supply_date')) : null,
            'repair_date' => $this->filled('repair_date') ? trim((string) $this->input('repair_date')) : null,
            'repair_status' => $this->filled('repair_status') ? trim((string) $this->input('repair_status')) : null,
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'support_year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'support_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'equipment_type' => ['nullable', 'string', 'max:120'],
            'equipment_code' => ['nullable', 'string', 'max:120'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'beneficiary_name' => ['nullable', 'string', 'max:200'],
            'fault_type' => ['nullable', 'string', 'max:1000'],
            'branch' => ['nullable', 'string', 'max:100'],
            'case_status' => ['nullable', 'string', 'max:120'],
            'po_number' => ['nullable', 'string', 'max:120'],
            'transfer_stage' => ['nullable', 'string', 'max:120'],
            'transfer_date' => ['nullable', 'string', 'max:60'],
            'supply_date' => ['nullable', 'string', 'max:60'],
            'repair_date' => ['nullable', 'string', 'max:60'],
            'repair_status' => ['nullable', 'string', 'max:200'],
        ];
    }
}
