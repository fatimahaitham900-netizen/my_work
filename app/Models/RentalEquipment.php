<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'contractor_name',
    'equipment_type',
    'rental_amount',
    'rental_type',
    'branch',
    'project',
    'notes',
])]
class RentalEquipment extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'rental_equipment';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rental_amount' => 'decimal:2',
        ];
    }
}
