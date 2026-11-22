<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'year',
    'month',
    'title',
    'repair_support_amount',
    'custody_amount',
    'not_transferred_amount',
    'total_equipment_count',
    'repaired_count',
    'under_repair_count',
    'notes',
])]
class FinancialSupportMonth extends Model
{
    use Auditable, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'repair_support_amount' => 'decimal:2',
            'custody_amount' => 'decimal:2',
            'not_transferred_amount' => 'decimal:2',
            'total_equipment_count' => 'integer',
            'repaired_count' => 'integer',
            'under_repair_count' => 'integer',
        ];
    }

    /**
     * @return Attribute<float, never>
     */
    protected function totalAmount(): Attribute
    {
        return Attribute::get(fn (): float => (float) $this->repair_support_amount + (float) $this->custody_amount);
    }
}
