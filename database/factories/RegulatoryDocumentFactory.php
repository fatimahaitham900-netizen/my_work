<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ComplianceStatus;
use App\Enums\RegulatoryEntityType;
use App\Models\RegulatoryDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegulatoryDocument>
 */
class RegulatoryDocumentFactory extends Factory
{
    protected $model = RegulatoryDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $entityType = fake()->randomElement(RegulatoryEntityType::cases());
        $complianceStatus = fake()->randomElement(ComplianceStatus::cases());

        $expiryDate = match ($complianceStatus) {
            ComplianceStatus::Valid => today()->addDays(fake()->numberBetween(60, 320)),
            ComplianceStatus::ExpiringSoon => today()->addDays(fake()->numberBetween(3, 25)),
            ComplianceStatus::Expired => today()->subDays(fake()->numberBetween(2, 45)),
            ComplianceStatus::Missing => null,
        };

        $issueDate = $expiryDate?->copy()->subYear();

        $documentType = $entityType === RegulatoryEntityType::Equipment
            ? fake()->randomElement(['استمارة', 'فحص دوري', 'تأمين', 'كرت تشغيل'])
            : fake()->randomElement(['رخصة قيادة', 'بطاقة سائق', 'تأمين طبي', 'شهادة سلامة مهنية']);

        return [
            'entity_type' => $entityType,
            'entity_identifier' => $entityType === RegulatoryEntityType::Equipment
                ? sprintf('EQ-%s-%03d', fake()->randomElement(['EXC', 'TRK', 'CRN', 'LDR']), fake()->numberBetween(100, 999))
                : sprintf('EMP-%04d', fake()->numberBetween(1001, 9999)),
            'document_type' => $documentType,
            'document_number' => $complianceStatus === ComplianceStatus::Missing
                ? null
                : sprintf('DOC-%d-%05d', now()->year, fake()->numberBetween(10000, 99999)),
            'issue_date' => $issueDate,
            'expiry_date' => $expiryDate,
            'compliance_status' => $complianceStatus,
            'renewal_cost' => fake()->randomFloat(2, 350, 6500),
            'notes' => fake()->optional(0.65)->randomElement([
                'مطلوب التجديد العاجل لتفادي المخالفات المرورية',
                'تم رفع طلب التجديد عبر منصة نقل وبانتظار السداد',
                'الوثيقة الأصلية غير مرفقة في ملف المعدة',
            ]),
        ];
    }

    public function valid(): static
    {
        return $this->state(fn (): array => [
            'issue_date' => today()->subMonths(4),
            'expiry_date' => today()->addMonths(8),
            'compliance_status' => ComplianceStatus::Valid,
        ]);
    }

    public function expiringSoon(int $daysRemaining = 12): static
    {
        return $this->state(fn (): array => [
            'issue_date' => today()->subYear()->addDays($daysRemaining),
            'expiry_date' => today()->addDays($daysRemaining),
            'compliance_status' => ComplianceStatus::ExpiringSoon,
        ]);
    }

    public function expired(int $daysExpired = 15): static
    {
        return $this->state(fn (): array => [
            'issue_date' => today()->subYear()->subDays($daysExpired),
            'expiry_date' => today()->subDays($daysExpired),
            'compliance_status' => ComplianceStatus::Expired,
        ]);
    }

    public function missing(): static
    {
        return $this->state(fn (): array => [
            'document_number' => null,
            'issue_date' => null,
            'expiry_date' => null,
            'compliance_status' => ComplianceStatus::Missing,
        ]);
    }

    public function forEquipment(string $code = 'EQ-TRK-201'): static
    {
        return $this->state(fn (): array => [
            'entity_type' => RegulatoryEntityType::Equipment,
            'entity_identifier' => $code,
            'document_type' => 'استمارة',
        ]);
    }

    public function forDriver(string $employeeId = 'EMP-3011'): static
    {
        return $this->state(fn (): array => [
            'entity_type' => RegulatoryEntityType::Driver,
            'entity_identifier' => $employeeId,
            'document_type' => 'بطاقة سائق',
        ]);
    }
}
