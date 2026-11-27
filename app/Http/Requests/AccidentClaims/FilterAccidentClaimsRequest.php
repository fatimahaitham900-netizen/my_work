<?php

declare(strict_types=1);

namespace App\Http\Requests\AccidentClaims;

use App\Http\Requests\BaseModuleFilterRequest;
use App\Models\AccidentClaim;

class FilterAccidentClaimsRequest extends BaseModuleFilterRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', AccidentClaim::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            ...$this->baseFilterRules(),
            'record_type' => ['nullable', 'string', 'in:accident,insurance_claim'],
            'branch' => ['nullable', 'string', 'max:120'],
            'insurance_company' => ['nullable', 'string', 'max:150'],
            'claim_status' => ['nullable', 'string', 'max:100'],
        ];
    }
}
