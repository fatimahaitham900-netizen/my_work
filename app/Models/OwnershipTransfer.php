<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OwnershipTransferStatus;
use App\Models\Concerns\HasExtraColumns;
use App\Traits\Auditable;
use Database\Factories\OwnershipTransferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'equipment_code',
    'equipment_type',
    'branch',
    'chassis_number',
    'current_owner',
    'target_owner',
    'request_date',
    'expected_date',
    'actual_date',
    'status',
    'blocker_reason',
    'notes',
])]
class OwnershipTransfer extends Model
{
    /** @use HasFactory<OwnershipTransferFactory> */
    use Auditable, HasExtraColumns, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'expected_date' => 'date',
            'actual_date' => 'date',
            'status' => OwnershipTransferStatus::class,
        ];
    }

    /**
     * Number of overdue days beyond expected_date.
     *
     * @return Attribute<int, never>
     */
    protected function delayDays(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                if ($this->expected_date === null) {
                    return 0;
                }

                $comparisonDate = $this->actual_date?->startOfDay() ?? today();

                return max(0, (int) $this->expected_date->startOfDay()->diffInDays($comparisonDate, false));
            },
        );
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function isOverdue(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->status === OwnershipTransferStatus::Delayed
                || ($this->status === OwnershipTransferStatus::Pending && $this->delay_days > 0),
        );
    }

    /**
     * @param  Builder<OwnershipTransfer>  $query
     * @return Builder<OwnershipTransfer>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            OwnershipTransferStatus::Delayed->value,
            OwnershipTransferStatus::Pending->value,
        ]);
    }

    /**
     * @param  Builder<OwnershipTransfer>  $query
     * @return Builder<OwnershipTransfer>
     */
    public function scopeDelayed(Builder $query): Builder
    {
        return $query->where('status', OwnershipTransferStatus::Delayed->value);
    }

    /**
     * @param  Builder<OwnershipTransfer>  $query
     * @return Builder<OwnershipTransfer>
     */
    public function scopeCritical(Builder $query): Builder
    {
        return $query->where(function (Builder $subQuery): void {
            $subQuery->where('status', OwnershipTransferStatus::Delayed->value)
                ->orWhere(function (Builder $overduePending): void {
                    $overduePending->where('status', OwnershipTransferStatus::Pending->value)
                        ->whereDate('expected_date', '<', today());
                });
        });
    }
}
