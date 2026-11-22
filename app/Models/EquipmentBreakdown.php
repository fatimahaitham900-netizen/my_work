<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BreakdownCategory;
use App\Enums\BreakdownSeverity;
use App\Enums\RepairStatus;
use App\Traits\Auditable;
use Database\Factories\EquipmentBreakdownFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'equipment_code',
    'equipment_name',
    'brand',
    'workshop_name',
    'category',
    'severity',
    'breakdown_date',
    'workshop_entry_date',
    'expected_repair_date',
    'fault_description',
    'repair_status',
    'workshop_status',
    'spare_parts_delay',
    'estimated_cost',
    'cost_note',
    'notes',
])]
class EquipmentBreakdown extends Model
{
    /** @use HasFactory<EquipmentBreakdownFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => BreakdownCategory::class,
            'severity' => BreakdownSeverity::class,
            'breakdown_date' => 'date',
            'workshop_entry_date' => 'date',
            'expected_repair_date' => 'date',
            'repair_status' => RepairStatus::class,
            'spare_parts_delay' => 'boolean',
            'estimated_cost' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<BreakdownDriverStoppage, $this>
     */
    public function driverStoppages(): HasMany
    {
        return $this->hasMany(BreakdownDriverStoppage::class, 'breakdown_id');
    }

    /**
     * Elapsed downtime days since breakdown_date.
     *
     * @return Attribute<int, never>
     */
    protected function downtimeDays(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                if ($this->breakdown_date === null) {
                    return 0;
                }

                return max(0, (int) $this->breakdown_date->startOfDay()->diffInDays(today(), false));
            },
        );
    }

    /**
     * @param  Builder<EquipmentBreakdown>  $query
     * @return Builder<EquipmentBreakdown>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('repair_status', '!=', RepairStatus::Repaired->value);
    }

    /**
     * @param  Builder<EquipmentBreakdown>  $query
     * @return Builder<EquipmentBreakdown>
     */
    public function scopeCritical(Builder $query): Builder
    {
        return $query->active()
            ->where('severity', BreakdownSeverity::Critical->value);
    }

    /**
     * @param  Builder<EquipmentBreakdown>  $query
     * @return Builder<EquipmentBreakdown>
     */
    public function scopeWaitingParts(Builder $query): Builder
    {
        return $query->active()
            ->where(function (Builder $subQuery): void {
                $subQuery->where('repair_status', RepairStatus::WaitingParts->value)
                    ->orWhere('spare_parts_delay', true);
            });
    }
}
