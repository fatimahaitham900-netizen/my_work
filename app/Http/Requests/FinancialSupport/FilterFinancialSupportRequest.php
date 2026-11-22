<?php

declare(strict_types=1);

namespace App\Http\Requests\FinancialSupport;

use App\Models\FinancialSupportTransfer;
use Illuminate\Foundation\Http\FormRequest;

class FilterFinancialSupportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', FinancialSupportTransfer::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'month' => ['nullable', 'string', 'max:10'],
            'search' => ['nullable', 'string', 'max:150'],
            'branch' => ['nullable', 'string', 'max:100'],
            'transfer_stage' => ['nullable', 'string', 'max:120'],
            'repair_status' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return $this->safe()->only([
            'month',
            'search',
            'branch',
            'transfer_stage',
            'repair_status',
        ]);
    }
}
