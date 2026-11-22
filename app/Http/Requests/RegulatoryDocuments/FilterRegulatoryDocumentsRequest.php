<?php

declare(strict_types=1);

namespace App\Http\Requests\RegulatoryDocuments;

use App\Enums\ComplianceStatus;
use App\Enums\RegulatoryEntityType;
use App\Http\Requests\BaseModuleFilterRequest;
use App\Models\RegulatoryDocument;
use Illuminate\Validation\Rule;

class FilterRegulatoryDocumentsRequest extends BaseModuleFilterRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', RegulatoryDocument::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            ...$this->baseFilterRules(),
            'entity_type' => ['nullable', Rule::enum(RegulatoryEntityType::class)],
            'compliance_status' => ['nullable', Rule::enum(ComplianceStatus::class)],
            'document_type' => ['nullable', 'string', 'max:100'],
            'branch' => ['nullable', 'string', 'max:100'],
            'transport_type' => ['nullable', 'string', 'max:60'],
            'equipment_type' => ['nullable', 'string', 'max:120'],
            'renewal_status' => ['nullable', 'string', 'max:60'],
        ];
    }
}
