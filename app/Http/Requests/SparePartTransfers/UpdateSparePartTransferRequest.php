<?php

declare(strict_types=1);

namespace App\Http\Requests\SparePartTransfers;

use App\Models\SparePartTransfer;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSparePartTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var SparePartTransfer|null $transfer */
        $transfer = $this->route('sparePartTransfer');

        return $transfer !== null && (bool) $this->user()?->can('update', $transfer);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'item_description' => $this->filled('item_description') ? trim((string) $this->input('item_description')) : null,
            'from_equipment' => $this->filled('from_equipment') ? trim((string) $this->input('from_equipment')) : null,
            'to_equipment' => $this->filled('to_equipment') ? trim((string) $this->input('to_equipment')) : null,
            'transfer_date' => $this->filled('transfer_date') ? $this->input('transfer_date') : null,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'item_description' => ['nullable', 'string', 'max:1000'],
            'from_equipment' => ['nullable', 'string', 'max:120'],
            'to_equipment' => ['nullable', 'string', 'max:120'],
            'transfer_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
