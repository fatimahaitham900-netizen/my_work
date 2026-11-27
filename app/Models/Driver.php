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
    'staff_category',
    'equipment_code',
    'branch',
    'site_location',
    'driver_name',
    'employee_id',
    'job_title',
    'nationality',
    'salary',
    'sponsor',
    'status',
    'notes',
])]
class Driver extends Model
{
    /** @use HasFactory<DriverFactory> */
    use Auditable, HasExtraColumns, HasFactory, SoftDeletes;

    public const CATEGORY_DRIVER = 'driver';

    public const CATEGORY_WORKSHOP_STAFF = 'workshop_staff';

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
