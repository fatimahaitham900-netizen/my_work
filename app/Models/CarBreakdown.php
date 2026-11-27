<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasExtraColumns;
use App\Traits\Auditable;
use Database\Factories\CarBreakdownFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'car_code',
    'vehicle_category',
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
    use Auditable, HasExtraColumns, HasFactory, SoftDeletes;

    public const string CATEGORY_CAR = 'سيارة';

    public const string CATEGORY_BUS = 'باص';

    public static function resolveCategory(?string $explicit, ?string $carCode, ?string $carType): string
    {
        $clean = trim((string) $explicit);
        if ($clean !== '') {
            return str_contains($clean, 'باص') || str_contains($clean, 'حافلة') || str_contains(strtolower($clean), 'bus')
                ? self::CATEGORY_BUS
                : self::CATEGORY_CAR;
        }

        $code = strtoupper(trim((string) $carCode));
        $type = trim((string) $carType);

        if (str_starts_with($code, 'BUS') || str_contains($type, 'باص') || str_contains($type, 'حافلة') || str_contains(strtolower($type), 'bus')) {
            return self::CATEGORY_BUS;
        }

        return self::CATEGORY_CAR;
    }

    /**
     * @param  Builder<CarBreakdown>  $query
     * @return Builder<CarBreakdown>
     */
    public function scopeCars(Builder $query): Builder
    {
        return $query->where(function (Builder $sub): void {
            $sub->where('vehicle_category', self::CATEGORY_CAR)
                ->orWhereNull('vehicle_category')
                ->orWhere('vehicle_category', '');
        });
    }

    /**
     * @param  Builder<CarBreakdown>  $query
     * @return Builder<CarBreakdown>
     */
    public function scopeBuses(Builder $query): Builder
    {
        return $query->where('vehicle_category', self::CATEGORY_BUS);
    }

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
