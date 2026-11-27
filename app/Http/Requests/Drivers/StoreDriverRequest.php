<?php

declare(strict_types=1);

namespace App\Http\Requests\Drivers;

use App\Models\Driver;
use Illuminate\Foundation\Http\FormRequest;

class StoreDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Driver::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'staff_category' => ['nullable', 'string', 'in:driver,workshop_staff'],
            'equipment_code' => ['nullable', 'string', 'max:80'],
            'branch' => ['nullable', 'string', 'max:120'],
            'site_location' => ['nullable', 'string', 'max:200'],
            'driver_name' => ['required', 'string', 'max:180'],
            'employee_id' => ['nullable', 'string', 'max:60'],
            'job_title' => ['nullable', 'string', 'max:150'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'sponsor' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
