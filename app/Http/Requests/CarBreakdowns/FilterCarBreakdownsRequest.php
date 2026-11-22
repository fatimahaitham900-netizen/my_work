<?php

declare(strict_types=1);

namespace App\Http\Requests\CarBreakdowns;

use App\Models\CarBreakdown;
use Illuminate\Foundation\Http\FormRequest;

class FilterCarBreakdownsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', CarBreakdown::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'branch' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:120'],
            'fuel_type' => ['nullable', 'string', 'max:40'],
            'model_year' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return $this->safe()->only([
            'search',
            'branch',
            'department',
            'fuel_type',
            'model_year',
        ]);
    }
}
