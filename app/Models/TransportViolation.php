<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Database\Factories\TransportViolationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'violation_number',
    'record_holder',
    'violation_date',
    'amount',
    'department',
    'city',
    'violation_description',
    'objection_status',
    'plate_number',
    'equipment_code',
    'driver_name',
    'employee_id',
    'employee_status',
    'liability',
    'permit_status',
])]
class TransportViolation extends Model
{
    /** @use HasFactory<TransportViolationFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'violation_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * @param  Builder<TransportViolation>  $query
     * @return Builder<TransportViolation>
     */
    public function scopePendingObjection(Builder $query): Builder
    {
        return $query->where(function (Builder $sub): void {
            $sub->where('objection_status', 'like', '%انتظار%')
                ->orWhere('liability', 'like', '%انتظار%');
        });
    }

    /**
     * @param  Builder<TransportViolation>  $query
     * @return Builder<TransportViolation>
     */
    public function scopeRejectedOrMissed(Builder $query): Builder
    {
        return $query->where(function (Builder $sub): void {
            $sub->where('objection_status', 'like', '%الرفض%')
                ->orWhere('objection_status', 'like', '%لم يتم تقديمه%')
                ->orWhere('objection_status', 'like', '%لا يوجد دليل%');
        });
    }
}
