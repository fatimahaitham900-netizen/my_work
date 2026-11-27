<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasExtraColumns;
use App\Traits\Auditable;
use Database\Factories\EquipmentDistributionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'equipment_code',
    'equipment_type',
    'branch',
    'site_location',
    'equipment_status',
    'notes',
])]
class EquipmentDistribution extends Model
{
    /** @use HasFactory<EquipmentDistributionFactory> */
    use Auditable, HasExtraColumns, HasFactory, SoftDeletes;
}
