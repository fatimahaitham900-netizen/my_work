<?php

declare(strict_types=1);

namespace App\Http\Requests\AccidentClaims;

use App\Models\AccidentClaim;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAccidentClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var AccidentClaim|null $record */
        $record = $this->route('accidentClaim');

        return $record !== null && (bool) $this->user()?->can('update', $record);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'record_type' => ['nullable', 'string', 'in:accident,insurance_claim'],
            'vehicle_code' => ['nullable', 'string', 'max:80'],
            'user_code' => ['nullable', 'string', 'max:80'],
            'plate_number' => ['nullable', 'string', 'max:80'],
            'accident_date' => ['nullable', 'date'],
            'accident_location' => ['nullable', 'string', 'max:200'],
            'branch' => ['nullable', 'string', 'max:120'],
            'record_holder' => ['nullable', 'string', 'max:150'],
            'driver_name' => ['nullable', 'string', 'max:180'],
            'employee_id' => ['nullable', 'string', 'max:60'],
            'najm_report_number' => ['nullable', 'string', 'max:100'],
            'claim_number' => ['nullable', 'string', 'max:100'],
            'insurance_company' => ['nullable', 'string', 'max:150'],
            'fault_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'compensation_amount' => ['nullable', 'numeric', 'min:0'],
            'claim_status' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
