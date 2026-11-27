<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasExtraColumns;
use App\Traits\Auditable;
use Database\Factories\CarRegulatoryDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'document_type',
    'car_code',
    'plate_number',
    'car_type_model',
    'branch',
    'project_location',
    'hijri_expiry_date',
    'expiry_date',
    'status_label',
    'notes',
])]
class CarRegulatoryDocument extends Model
{
    /** @use HasFactory<CarRegulatoryDocumentFactory> */
    use Auditable, HasExtraColumns, HasFactory, SoftDeletes;

    public const string TYPE_INSPECTION = 'فحص دوري';

    public const string TYPE_REGISTRATION = 'استمارة';

    public const string TYPE_INSURANCE = 'تأمين';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
        ];
    }

    /**
     * Computed days remaining until expiry (negative when expired, null when missing expiry_date).
     *
     * @return Attribute<int|null, never>
     */
    protected function daysRemaining(): Attribute
    {
        return Attribute::get(function (): ?int {
            if ($this->expiry_date === null) {
                return null;
            }

            return (int) today()->diffInDays($this->expiry_date->startOfDay(), false);
        });
    }
}
