<?php

declare(strict_types=1);

namespace App\Http\Requests\RegistrationAmendments;

use App\Models\RegistrationAmendment;
use Illuminate\Foundation\Http\FormRequest;

class FilterRegistrationAmendmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', RegistrationAmendment::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'equipment_type' => ['nullable', 'string', 'max:150'],
            'branch' => ['nullable', 'string', 'max:120'],
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

