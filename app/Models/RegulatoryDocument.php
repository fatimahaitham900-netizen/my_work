<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ComplianceStatus;
use App\Enums\RegulatoryEntityType;
use App\Traits\Auditable;
use Carbon\CarbonInterface;
use Database\Factories\RegulatoryDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'entity_type',
    'entity_identifier',
    'equipment_type',
    'plate_number',
    'transport_type',
    'branch',
    'document_type',
    'document_number',
    'issue_date',
    'hijri_expiry_date',
    'expiry_date',
    'compliance_status',
    'renewal_status',
    'renewal_cost',
    'notes',
])]
class RegulatoryDocument extends Model
{
    /** @use HasFactory<RegulatoryDocumentFactory> */
    use Auditable, HasFactory, SoftDeletes;

    public const int EXPIRING_SOON_DAYS_THRESHOLD = 30;

    /**
     * Automatically derive compliance status from expiry date and explicit missing flag.
     */
    public static function resolveComplianceStatus(
        ?CarbonInterface $expiryDate,
        ?ComplianceStatus $requestedStatus = null,
    ): ComplianceStatus {
        if ($requestedStatus === ComplianceStatus::Missing || $expiryDate === null) {
            return ComplianceStatus::Missing;
        }

        $daysRemaining = (int) today()->diffInDays($expiryDate->copy()->startOfDay(), false);

        if ($daysRemaining < 0) {
            return ComplianceStatus::Expired;
        }

        if ($daysRemaining <= self::EXPIRING_SOON_DAYS_THRESHOLD) {
            return ComplianceStatus::ExpiringSoon;
        }

        return ComplianceStatus::Valid;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entity_type' => RegulatoryEntityType::class,
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'compliance_status' => ComplianceStatus::class,
            'renewal_cost' => 'decimal:2',
        ];
    }

    /**
     * Computed days remaining until expiry (negative when expired, null when missing expiry_date).
     *
     * @return Attribute<int|null, never>
     */
    protected function daysRemaining(): Attribute
    {
        return Attribute::make(
            get: function (): ?int {
                if ($this->expiry_date === null) {
                    return null;
                }

                return (int) today()->diffInDays($this->expiry_date->startOfDay(), false);
            },
        );
    }

    /**
     * @param  Builder<RegulatoryDocument>  $query
     * @return Builder<RegulatoryDocument>
     */
    public function scopeDeviations(Builder $query): Builder
    {
        return $query->whereIn('compliance_status', [
            ComplianceStatus::ExpiringSoon->value,
            ComplianceStatus::Expired->value,
            ComplianceStatus::Missing->value,
        ]);
    }

    /**
     * @param  Builder<RegulatoryDocument>  $query
     * @return Builder<RegulatoryDocument>
     */
    public function scopeCritical(Builder $query): Builder
    {
        return $query->whereIn('compliance_status', [
            ComplianceStatus::Expired->value,
            ComplianceStatus::Missing->value,
        ]);
    }

    /**
     * @param  Builder<RegulatoryDocument>  $query
     * @return Builder<RegulatoryDocument>
     */
    public function scopeExpiringSoon(Builder $query): Builder
    {
        return $query->where('compliance_status', ComplianceStatus::ExpiringSoon->value);
    }

    /**
     * @param  Builder<RegulatoryDocument>  $query
     * @return Builder<RegulatoryDocument>
     */
    public function scopeExpiredPeriodicInspections(Builder $query): Builder
    {
        return $query
            ->where('document_type', 'فحص دوري')
            ->where('compliance_status', ComplianceStatus::Expired->value);
    }

    /**
     * @param  Builder<RegulatoryDocument>  $query
     * @return Builder<RegulatoryDocument>
     */
    public function scopeExpiredOperatingCards(Builder $query): Builder
    {
        return $query
            ->where('document_type', 'كرت تشغيل')
            ->where('compliance_status', ComplianceStatus::Expired->value);
    }

    /**
     * @param  Builder<RegulatoryDocument>  $query
     * @return Builder<RegulatoryDocument>
     */
    public function scopeExpiredRegistrations(Builder $query): Builder
    {
        return $query
            ->where('document_type', 'استمارة')
            ->where('compliance_status', ComplianceStatus::Expired->value);
    }
}
