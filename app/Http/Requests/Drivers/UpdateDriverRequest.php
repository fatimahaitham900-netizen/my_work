<?php

declare(strict_types=1);

namespace App\Http\Requests\Drivers;

use App\Models\Driver;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Driver|null $record */
        $record = $this->route('driver');

        return $record !== null && (bool) $this->user()?->can('update', $record);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'equipment_code' => ['nullable', 'string', 'max:80'],
            'branch' => ['nullable', 'string', 'max:120'],
            'site_location' => ['nullable', 'string', 'max:200'],
            'driver_name' => ['required', 'string', 'max:180'],
            'employee_id' => ['nullable', 'string', 'max:60'],
            'job_title' => ['nullable', 'string', 'max:150'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'sponsor' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
