<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Database\Factories\CarBreakdownFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'car_code',
    'car_type',
    'plate_number',
    'model_year',
    'previous_receiver',
    'employee_id',
    'department',
    'job_title',
    'fuel_type',
    'branch',
    'condition_status',
])]
class CarBreakdown extends Model
{
    /** @use HasFactory<CarBreakdownFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @param  Builder<CarBreakdown>  $query
     * @return Builder<CarBreakdown>
     */
    public function scopeMajorMechanical(Builder $query): Builder
    {
        return $query->where(function (Builder $sub): void {
            $sub->where('condition_status', 'like', '%ماكينة%')
                ->orWhere('condition_status', 'like', '%شاسية%')
                ->orWhere('condition_status', 'like', '%بخاخات%')
                ->orWhere('condition_status', 'like', '%تربو%');
        });
    }

    /**
     * @param  Builder<CarBreakdown>  $query
     * @return Builder<CarBreakdown>
     */
    public function scopeNeedsInspection(Builder $query): Builder
    {
        return $query->where('condition_status', 'like', '%فحص%');
    }
}
