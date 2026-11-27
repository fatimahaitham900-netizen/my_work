<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DriverStoppageStatus;
use App\Models\Concerns\HasExtraColumns;
use App\Traits\Auditable;
use Database\Factories\BreakdownDriverStoppageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'driver_name',
    'employee_id',
    'job_title',
    'branch',
    'phone',
    'equipment_code',
    'equipment_type',
    'breakdown_id',
    'stoppage_start_date',
    'expected_resume_date',
    'fault_diagnosis',
    'workshop_status',
    'action_taken',
    'status',
    'notes',
])]
class BreakdownDriverStoppage extends Model
{
    /** @use HasFactory<BreakdownDriverStoppageFactory> */
    use Auditable, HasExtraColumns, HasFactory, SoftDeletes;

    public const int CRITICAL_IDLE_DAYS_THRESHOLD = 7;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stoppage_start_date' => 'date',
            'expected_resume_date' => 'date',
            'status' => DriverStoppageStatus::class,
        ];
    }

    /**
     * @return BelongsTo<EquipmentBreakdown, $this>
     */
    public function breakdown(): BelongsTo
    {
        return $this->belongsTo(EquipmentBreakdown::class, 'breakdown_id');
    }

    /**
     * Computed number of idle days since stoppage started.
     *
     * @return Attribute<int, never>
     */
    protected function idleDays(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                if ($this->stoppage_start_date === null) {
                    return 0;
                }

                return max(0, (int) $this->stoppage_start_date->startOfDay()->diffInDays(today(), false));
            },
        );
    }

    /**
     * @param  Builder<BreakdownDriverStoppage>  $query
     * @return Builder<BreakdownDriverStoppage>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', DriverStoppageStatus::Waiting->value);
    }

    /**
     * @param  Builder<BreakdownDriverStoppage>  $query
     * @return Builder<BreakdownDriverStoppage>
     */
    public function scopeCritical(Builder $query): Builder
    {
        return $query->active()
            ->whereDate('stoppage_start_date', '<=', today()->subDays(self::CRITICAL_IDLE_DAYS_THRESHOLD));
    }
}
