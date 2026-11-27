<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasExtraColumns;
use App\Traits\Auditable;
use Database\Factories\AccidentClaimFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'record_type',
    'vehicle_code',
    'user_code',
    'plate_number',
    'accident_date',
    'accident_location',
    'branch',
    'record_holder',
    'driver_name',
    'employee_id',
    'najm_report_number',
    'claim_number',
    'insurance_company',
    'fault_percentage',
    'estimated_cost',
    'compensation_amount',
    'claim_status',
    'notes',
])]
class AccidentClaim extends Model
{
    /** @use HasFactory<AccidentClaimFactory> */
    use Auditable, HasExtraColumns, HasFactory, SoftDeletes;

    public const TYPE_ACCIDENT = 'accident';

    public const TYPE_INSURANCE_CLAIM = 'insurance_claim';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accident_date' => 'date',
            'fault_percentage' => 'integer',
            'estimated_cost' => 'decimal:2',
            'compensation_amount' => 'decimal:2',
        ];
    }
}
