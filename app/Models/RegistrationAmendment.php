<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Database\Factories\RegistrationAmendmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'equipment_code',
    'equipment_type',
    'branch',
    'stoppage_date',
    'notes',
])]
class RegistrationAmendment extends Model
{
    /** @use HasFactory<RegistrationAmendmentFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stoppage_date' => 'date',
        ];
    }

    /**
     * Automatically calculate stoppage days from stoppage_date to today.
     *
     * @return Attribute<int|null, never>
     */
    protected function stoppageDays(): Attribute
    {
        return Attribute::get(function (): ?int {
            if ($this->stoppage_date === null) {
                return null;
            }

            return (int) max(0, $this->stoppage_date->startOfDay()->diffInDays(today(), false));
        });
    }
}

