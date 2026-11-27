<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasExtraColumns;
use App\Traits\Auditable;
use Database\Factories\DriverFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'equipment_code',
    'branch',
    'site_location',
    'driver_name',
    'employee_id',
    'job_title',
    'nationality',
    'salary',
    'sponsor',
    'notes',
])]
class Driver extends Model
{
    /** @use HasFactory<DriverFactory> */
    use Auditable, HasExtraColumns, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'salary' => 'decimal:2',
        ];
    }
}
