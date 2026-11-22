<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OwnershipTransferStatus;
use App\Models\OwnershipTransfer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OwnershipTransfer>
 */
class OwnershipTransferFactory extends Factory
{
    protected $model = OwnershipTransfer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $requestDate = today()->subDays(fake()->numberBetween(5, 35));
        $expectedDate = $requestDate->copy()->addDays(fake()->numberBetween(5, 14));
        $status = fake()->randomElement(OwnershipTransferStatus::cases());

        return [
            'equipment_code' => sprintf('EQ-%s-%03d', fake()->randomElement(['EXC', 'TRK', 'CRN', 'LDR', 'BLD']), fake()->numberBetween(100, 999)),
            'equipment_type' => fake()->randomElement([
                'بوبكات',
                'باك هو لودر',
                'لودر باك',
                'كرين',
                'كرين كوماتسو',
                'رافعة شوكية',
                'رافعه',
            ]),
            'branch' => fake()->randomElement([
                'المدينة المنوره',
                'الحائر',
                'نجران',
                'الجوف',
                'الرياض',
            ]),
            'chassis_number' => strtoupper(fake()->bothify('???####??###????')),
            'current_owner' => fake()->randomElement([
                'قطاع مشاريع الرياض',
                'فرع المنطقة الشرقية',
                'إدارة النقل الثقيل',
                'مستودع المعدات الرئيسي',
            ]),
            'target_owner' => fake()->randomElement([
                'مشروع البنية التحتية الشمالي',
                'قطاع المنطقة الغربية',
                'مشروع تطوير الميناء اللوجستي',
                'إدارة المشاريع الكبرى',
            ]),
            'request_date' => $requestDate,
            'expected_date' => $expectedDate,
            'actual_date' => $status === OwnershipTransferStatus::OnTime ? $expectedDate : null,
            'status' => $status,
            'blocker_reason' => $status === OwnershipTransferStatus::Delayed
                ? fake()->randomElement([
                    'بانتظار مخالصة مرورية ومطابقة أرقام الهيكل',
                    'تأخر اعتماد المفوض المالي في المنصة الحكومية',
                    'عدم تجديد الفحص الدوري للمعدة قبل النقل',
                    'مخالفة قائمة على السجل التجاري للفرع المحوّل',
                ])
                : null,
            'notes' => fake()->optional(0.6)->randomElement([
                'تم رفع الطلب عبر بوابة تم وبانتظار التوثيق النهائي',
                'يتطلب متابعة يومية مع إدارة الشؤون القانونية',
                'تم استلام خطاب التفويض الرسمي',
            ]),
        ];
    }

    public function onTime(): static
    {
        return $this->state(function (): array {
            $requestDate = today()->subDays(12);
            $expectedDate = today()->subDays(3);

            return [
                'request_date' => $requestDate,
                'expected_date' => $expectedDate,
                'actual_date' => $expectedDate,
                'status' => OwnershipTransferStatus::OnTime,
                'blocker_reason' => null,
            ];
        });
    }

    public function delayed(int $daysOverdue = 10): static
    {
        return $this->state(fn (): array => [
            'request_date' => today()->subDays($daysOverdue + 10),
            'expected_date' => today()->subDays($daysOverdue),
            'actual_date' => null,
            'status' => OwnershipTransferStatus::Delayed,
            'blocker_reason' => 'تأخر استكمال إجراءات التوثيق ونقل الاستمارة',
        ]);
    }

    public function pending(int $daysUntilDue = 5): static
    {
        return $this->state(fn (): array => [
            'request_date' => today()->subDays(4),
            'expected_date' => today()->addDays($daysUntilDue),
            'actual_date' => null,
            'status' => OwnershipTransferStatus::Pending,
            'blocker_reason' => null,
        ]);
    }
}
