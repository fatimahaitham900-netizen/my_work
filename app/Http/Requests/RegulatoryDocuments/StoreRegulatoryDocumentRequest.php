<?php

declare(strict_types=1);

namespace App\Http\Requests\RegulatoryDocuments;

use App\Enums\ComplianceStatus;
use App\Enums\RegulatoryEntityType;
use App\Models\RegulatoryDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegulatoryDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', RegulatoryDocument::class);
    }

    protected function prepareForValidation(): void
    {
        $isMissing = $this->input('compliance_status') === ComplianceStatus::Missing->value;

        $this->merge([
            'entity_type' => $this->filled('entity_type') ? $this->input('entity_type') : RegulatoryEntityType::Equipment->value,
            'entity_identifier' => $this->filled('entity_identifier') ? trim((string) $this->input('entity_identifier')) : null,
            'equipment_type' => $this->filled('equipment_type') ? trim((string) $this->input('equipment_type')) : null,
            'plate_number' => $this->filled('plate_number') ? trim((string) $this->input('plate_number')) : null,
            'transport_type' => $this->filled('transport_type') ? trim((string) $this->input('transport_type')) : null,
            'branch' => $this->filled('branch') ? trim((string) $this->input('branch')) : null,
            'document_type' => $this->filled('document_type') ? trim((string) $this->input('document_type')) : null,
            'document_number' => $this->filled('document_number') ? trim((string) $this->input('document_number')) : null,
            'issue_date' => (! $isMissing && $this->filled('issue_date')) ? $this->input('issue_date') : null,
            'hijri_expiry_date' => (! $isMissing && $this->filled('hijri_expiry_date')) ? trim((string) $this->input('hijri_expiry_date')) : null,
            'expiry_date' => (! $isMissing && $this->filled('expiry_date')) ? $this->input('expiry_date') : null,
            'renewal_status' => $this->filled('renewal_status') ? trim((string) $this->input('renewal_status')) : null,
            'renewal_cost' => $this->filled('renewal_cost') ? $this->input('renewal_cost') : 0,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $afterIssueDate = $this->filled('issue_date') ? ['after_or_equal:issue_date'] : [];

        return [
            'entity_type' => ['nullable', Rule::enum(RegulatoryEntityType::class)],
            'entity_identifier' => ['nullable', 'string', 'max:100'],
            'equipment_type' => ['nullable', 'string', 'max:120'],
            'plate_number' => ['nullable', 'string', 'max:60'],
            'transport_type' => ['nullable', 'string', 'max:60'],
            'branch' => ['nullable', 'string', 'max:100'],
            'document_type' => ['nullable', 'string', 'max:100'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'issue_date' => ['nullable', 'date'],
            'hijri_expiry_date' => ['nullable', 'string', 'max:30'],
            'expiry_date' => ['nullable', 'date', ...$afterIssueDate],
            'compliance_status' => ['nullable', Rule::enum(ComplianceStatus::class)],
            'renewal_status' => ['nullable', 'string', 'max:60'],
            'renewal_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
