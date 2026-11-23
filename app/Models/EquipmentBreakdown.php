<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BreakdownCategory;
use App\Enums\BreakdownSeverity;
use App\Enums\RepairStatus;
use App\Traits\Auditable;
use Carbon\Carbon;
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
            'repair_status' => RepairStatus::class,
            'spare_parts_delay' => 'boolean',
            'estimated_cost' => 'decimal:2',
        ];
    }

    /**
     * Normalized display string for expected_repair_date.
     *
     * @return Attribute<string, never>
     */
    protected function expectedRepairDateDisplay(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $val = trim((string) ($this->attributes['expected_repair_date'] ?? ''));
                if ($val === '' || $val === '—' || $val === '-') {
                    return '—';
                }

                if (is_numeric($val) && (int) $val > 20000 && (int) $val < 60000) {
                    try {
                        return Carbon::create(1899, 12, 30)->addDays((int) $val)->format('d/m/Y');
                    } catch (\Throwable) {
                        return $val;
                    }
                }

                try {
                    return Carbon::parse($val)->format('d/m/Y');
                } catch (\Throwable) {
                    return $val;
                }
            },
        );
    }

    /**
     * Mutator/Accessor for expected_repair_date to gracefully accept Carbon, DateTime, or string.
     *
     * @return Attribute<string|null, string|\DateTimeInterface|null>
     */
    protected function expectedRepairDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: function ($value): ?string {
                if ($value === null || $value === '' || $value === '—' || $value === '-') {
                    return null;
                }
                if ($value instanceof \DateTimeInterface) {
                    return $value->format('Y-m-d');
                }

                return (string) $value;
            },
        );
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
