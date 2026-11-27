<?php

declare(strict_types=1);

namespace App\Http\Requests\CarRegulatoryDocuments;

use App\Models\CarRegulatoryDocument;
use Illuminate\Foundation\Http\FormRequest;

class FilterCarRegulatoryDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', CarRegulatoryDocument::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'document_type' => ['nullable', 'string', 'max:40'],
            'branch' => ['nullable', 'string', 'max:120'],
            'project_location' => ['nullable', 'string', 'max:180'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return $this->validated();
    }
}
