<?php

declare(strict_types=1);

namespace App\Http\Requests\OwnershipTransfers;

use App\Enums\OwnershipTransferStatus;
use App\Http\Requests\BaseModuleFilterRequest;
use App\Models\OwnershipTransfer;
use Illuminate\Validation\Rule;

class FilterOwnershipTransfersRequest extends BaseModuleFilterRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', OwnershipTransfer::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            ...$this->baseFilterRules(),
            'status' => ['nullable', Rule::enum(OwnershipTransferStatus::class)],
            'branch' => ['nullable', 'string', 'max:120'],
        ];
    }
}
