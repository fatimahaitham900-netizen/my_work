<?php

declare(strict_types=1);

namespace App\Http\Requests\UnassignedDrivers;

use App\Enums\UnassignedDriverStatus;
use App\Models\UnassignedDriver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnassignedDriverStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var UnassignedDriver|null $driver */
        $driver = $this->route('unassignedDriver');

        return $driver !== null && (bool) $this->user()?->can('update', $driver);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(UnassignedDriverStatus::class)],
        ];
    }
}
