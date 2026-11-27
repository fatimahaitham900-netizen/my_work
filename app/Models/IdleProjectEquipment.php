<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasExtraColumns;
use App\Traits\Auditable;
use Database\Factories\IdleProjectEquipmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'equipment_code',
    'equipment_type',
    'driver_name',
    'employee_id',
    'branch',
    'site_location',
    'equipment_status',
    'total_idle_days',
])]
class IdleProjectEquipment extends Model
{
    /** @use HasFactory<IdleProjectEquipmentFactory> */
    use Auditable, HasExtraColumns, HasFactory, SoftDeletes;

    protected $table = 'idle_project_equipment';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_idle_days' => 'integer',
        ];
    }

    /**
     * @param  Builder<IdleProjectEquipment>  $query
     * @return Builder<IdleProjectEquipment>
     */
    public function scopeHighIdleDays(Builder $query, int $threshold = 7): Builder
    {
        return $query->where('total_idle_days', '>=', $threshold);
    }
}
