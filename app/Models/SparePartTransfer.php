<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Database\Factories\SparePartTransferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'item_description',
    'from_equipment',
    'to_equipment',
    'transfer_date',
    'notes',
])]
class SparePartTransfer extends Model
{
    /** @use HasFactory<SparePartTransferFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transfer_date' => 'date',
        ];
    }

    public function hasUnknownSource(): bool
    {
        $from = trim((string) ($this->from_equipment ?? ''));
        $description = (string) ($this->item_description ?? '');

        return $from === '' || str_contains($description, 'دون معرفة');
    }

    /**
     * @param  Builder<SparePartTransfer>  $query
     * @return Builder<SparePartTransfer>
     */
    public function scopeUnknownSource(Builder $query): Builder
    {
        return $query->where(function (Builder $sub): void {
            $sub->whereNull('from_equipment')
                ->orWhere('from_equipment', '')
                ->orWhere('item_description', 'like', '%دون معرفة%');
        });
    }

    /**
     * @param  Builder<SparePartTransfer>  $query
     * @return Builder<SparePartTransfer>
     */
    public function scopeKnownSource(Builder $query): Builder
    {
        return $query->whereNotNull('from_equipment')
            ->where('from_equipment', '!=', '')
            ->where(function (Builder $sub): void {
                $sub->whereNull('item_description')
                    ->orWhere('item_description', 'not like', '%دون معرفة%');
            });
    }
}
