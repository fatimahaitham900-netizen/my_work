<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasExtraColumns;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'day_number',
    'row_type',
    'daily_maintenance_rate',
    'preventive_maintenance_rate',
    'daily_deviation_rate',
    'preventive_deviation_rate',
    'status_note',
    'notes',
])]
class MaintenanceDeviation extends Model
{
    use Auditable, HasExtraColumns, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_number' => 'integer',
            'daily_maintenance_rate' => 'decimal:2',
            'preventive_maintenance_rate' => 'decimal:2',
            'daily_deviation_rate' => 'decimal:2',
            'preventive_deviation_rate' => 'decimal:2',
        ];
    }

    /**
     * @param  Builder<MaintenanceDeviation>  $query
     * @return Builder<MaintenanceDeviation>
     */
    public function scopeActiveDays(Builder $query): Builder
    {
        return $query
            ->where('row_type', 'active')
            ->whereNotNull('daily_maintenance_rate');
    }
}
