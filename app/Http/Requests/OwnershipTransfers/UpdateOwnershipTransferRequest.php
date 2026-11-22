<?php

declare(strict_types=1);

namespace App\Http\Requests\OwnershipTransfers;

use App\Enums\OwnershipTransferStatus;
use App\Models\OwnershipTransfer;
use App\Services\OwnershipTransferService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOwnershipTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var OwnershipTransfer|null $transfer */
        $transfer = $this->route('ownershipTransfer');

        return $transfer !== null && (bool) $this->user()?->can('update', $transfer);
    }

    protected function prepareForValidation(): void
    {
        $resolved = OwnershipTransferService::applySmartStatus([
            'status' => $this->filled('status') ? $this->input('status') : OwnershipTransferStatus::Pending->value,
            'expected_date' => $this->filled('expected_date') ? $this->input('expected_date') : null,
            'actual_date' => $this->filled('actual_date') ? $this->input('actual_date') : null,
        ]);

        $this->merge([
            'equipment_code' => $this->filled('equipment_code') ? trim((string) $this->input('equipment_code')) : null,
            'equipment_type' => $this->filled('equipment_type') ? trim((string) $this->input('equipment_type')) : null,
            'branch' => $this->filled('branch') ? trim((string) $this->input('branch')) : null,
            'chassis_number' => $this->filled('chassis_number') ? trim((string) $this->input('chassis_number')) : null,
            'current_owner' => $this->filled('current_owner') ? trim((string) $this->input('current_owner')) : null,
            'target_owner' => $this->filled('target_owner') ? trim((string) $this->input('target_owner')) : null,
            'request_date' => $this->filled('request_date') ? $this->input('request_date') : null,
            'expected_date' => $this->filled('expected_date') ? $this->input('expected_date') : null,
            'actual_date' => $this->filled('actual_date') ? $this->input('actual_date') : null,
            'status' => $resolved['status'],
            'blocker_reason' => $this->filled('blocker_reason') ? trim((string) $this->input('blocker_reason')) : null,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $afterRequestDate = $this->filled('request_date') ? ['after_or_equal:request_date'] : [];

        return [
            'equipment_code' => ['nullable', 'string', 'max:50'],
            'equipment_type' => ['nullable', 'string', 'max:120'],
            'branch' => ['nullable', 'string', 'max:120'],
            'chassis_number' => ['nullable', 'string', 'max:100'],
            'current_owner' => ['nullable', 'string', 'max:150'],
            'target_owner' => ['nullable', 'string', 'max:150'],
            'request_date' => ['nullable', 'date'],
            'expected_date' => ['nullable', 'date', ...$afterRequestDate],
            'actual_date' => ['nullable', 'date', ...$afterRequestDate],
            'status' => ['nullable', Rule::enum(OwnershipTransferStatus::class)],
            'blocker_reason' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
