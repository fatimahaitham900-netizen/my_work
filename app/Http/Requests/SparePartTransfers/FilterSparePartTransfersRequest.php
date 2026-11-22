<?php

declare(strict_types=1);

namespace App\Http\Requests\SparePartTransfers;

use App\Models\SparePartTransfer;
use Illuminate\Foundation\Http\FormRequest;

class FilterSparePartTransfersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', SparePartTransfer::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'from_equipment' => ['nullable', 'string', 'max:120'],
            'to_equipment' => ['nullable', 'string', 'max:120'],
            'source_status' => ['nullable', 'in:known,unknown'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return $this->safe()->only([
            'search',
            'from_equipment',
            'to_equipment',
            'source_status',
            'date_from',
            'date_to',
        ]);
    }
}
