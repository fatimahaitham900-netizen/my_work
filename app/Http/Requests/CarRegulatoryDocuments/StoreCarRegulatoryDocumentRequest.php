<?php

declare(strict_types=1);

namespace App\Http\Requests\CarRegulatoryDocuments;

use App\Models\CarRegulatoryDocument;
use Illuminate\Foundation\Http\FormRequest;

class StoreCarRegulatoryDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', CarRegulatoryDocument::class);
    }

    protected function prepareForValidation(): void
    {
        $docType = trim((string) $this->input('document_type', ''));

        $this->merge([
            'document_type' => $docType !== '' ? $docType : CarRegulatoryDocument::TYPE_INSPECTION,
            'car_code' => $this->filled('car_code') ? trim((string) $this->input('car_code')) : null,
            'plate_number' => $this->filled('plate_number') ? trim((string) $this->input('plate_number')) : null,
            'car_type_model' => $this->filled('car_type_model') ? trim((string) $this->input('car_type_model')) : null,
            'branch' => $this->filled('branch') ? trim((string) $this->input('branch')) : null,
            'project_location' => $this->filled('project_location') ? trim((string) $this->input('project_location')) : null,
            'hijri_expiry_date' => $this->filled('hijri_expiry_date') ? trim((string) $this->input('hijri_expiry_date')) : null,
            'expiry_date' => $this->filled('expiry_date') ? trim((string) $this->input('expiry_date')) : null,
            'status_label' => $this->filled('status_label') ? trim((string) $this->input('status_label')) : null,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'document_type' => ['nullable', 'string', 'max:40'],
            'car_code' => ['nullable', 'string', 'max:60'],
            'plate_number' => ['nullable', 'string', 'max:60'],
            'car_type_model' => ['nullable', 'string', 'max:180'],
            'branch' => ['nullable', 'string', 'max:120'],
            'project_location' => ['nullable', 'string', 'max:180'],
            'hijri_expiry_date' => ['nullable', 'string', 'max:40'],
            'expiry_date' => ['nullable', 'date'],
            'status_label' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
