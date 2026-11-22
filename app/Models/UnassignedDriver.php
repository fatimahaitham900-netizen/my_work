<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UnassignedDriverStatus;
use App\Traits\Auditable;
use Database\Factories\UnassignedDriverFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'driver_name',
    'employee_id',
    'job_title',
    'location',
    'license_type',
    'license_expiry',
    'unassigned_since',
    'status',
    'notes',
])]
class UnassignedDriver extends Model
{
    /** @use HasFactory<UnassignedDriverFactory> */
    use Auditable, HasFactory, SoftDeletes;

    public const int CRITICAL_IDLE_DAYS_THRESHOLD = 14;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'license_expiry' => 'date',
            'unassigned_since' => 'date',
            'status' => UnassignedDriverStatus::class,
        ];
    }

    /**
     * Computed number of days the driver has been without assigned equipment.
     *
     * @return Attribute<int, never>
     */
    protected function idleDurationDays(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                if ($this->unassigned_since === null) {
                    return 0;
                }

                return max(0, (int) $this->unassigned_since->startOfDay()->diffInDays(today(), false));
            },
        );
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function isLicenseExpired(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->license_expiry !== null && $this->license_expiry->startOfDay()->lt(today()),
        );
    }

    /**
     * Signed days remaining until driver's license expires.
     *
     * @return Attribute<int|null, never>
     */
    protected function licenseDaysRemaining(): Attribute
    {
        return Attribute::make(
            get: function (): ?int {
                if ($this->license_expiry === null) {
                    return null;
                }

                return (int) today()->diffInDays($this->license_expiry->startOfDay(), false);
            },
        );
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function isLicenseExpiringSoon(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                $days = $this->license_days_remaining;

                return $days !== null && $days >= 0 && $days <= 30;
            },
        );
    }

    /**
     * @param  Builder<UnassignedDriver>  $query
     * @return Builder<UnassignedDriver>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            UnassignedDriverStatus::Ready->value,
            UnassignedDriverStatus::Evaluation->value,
        ]);
    }

    /**
     * @param  Builder<UnassignedDriver>  $query
     * @return Builder<UnassignedDriver>
     */
    public function scopeCritical(Builder $query): Builder
    {
        return $query->active()
            ->where(function (Builder $subQuery): void {
                $subQuery->whereDate('unassigned_since', '<=', today()->subDays(self::CRITICAL_IDLE_DAYS_THRESHOLD))
                    ->orWhereDate('license_expiry', '<', today());
            });
    }
}
