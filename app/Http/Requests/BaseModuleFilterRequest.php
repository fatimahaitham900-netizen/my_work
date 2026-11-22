<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseModuleFilterRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    protected function baseFilterRules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'focus' => ['nullable', 'string', 'in:critical,active'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
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
