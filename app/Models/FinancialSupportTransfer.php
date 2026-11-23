<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Database\Factories\FinancialSupportTransferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'support_year',
    'support_month',
    'equipment_type',
    'equipment_code',
    'amount',
    'beneficiary_name',
    'fault_type',
    'branch',
    'case_status',
    'po_number',
    'transfer_stage',
    'transfer_date',
    'supply_date',
    'repair_date',
    'repair_status',
])]
class FinancialSupportTransfer extends Model
{
    /** @use HasFactory<FinancialSupportTransferFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'support_year' => 'integer',
            'support_month' => 'integer',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * @param  Builder<FinancialSupportTransfer>  $query
     * @return Builder<FinancialSupportTransfer>
     */
    public function scopeNotTransferred(Builder $query): Builder
    {
        return $query->where('transfer_stage', 'like', '%لم يتم التحويل%');
    }

    /**
     * @param  Builder<FinancialSupportTransfer>  $query
     * @return Builder<FinancialSupportTransfer>
     */
    public function scopeUnderRepair(Builder $query): Builder
    {
        return $query->where(function (Builder $sub): void {
            $sub->where('repair_status', 'like', '%لم يتم الاصلاح%')
                ->orWhere('repair_status', 'like', '%لم يتم الإصلاح%')
                ->orWhere('repair_status', 'like', '%لم يتم اصلاح%')
                ->orWhere('repair_status', 'like', '%مازال متعطل%');
        });
    }
}
