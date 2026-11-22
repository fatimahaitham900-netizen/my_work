<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UnassignedDriverStatus;
use App\Models\UnassignedDriver;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnassignedDriver>
 */
class UnassignedDriverFactory extends Factory
{
    protected $model = UnassignedDriver::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'driver_name' => fake()->randomElement([
                'عمر راشد السبيعي',
                'سلطان عبدالعزيز الزهراني',
                'أحمد سالم الجهني',
                'محمد عبدالله الشهراني',
                'فيصل خالد المالكي',
                'راشد سعيد اليامي',
                'حسام طارق الأحمدي',
            ]),
            'employee_id' => sprintf('%04d', fake()->numberBetween(1001, 9999)),
            'job_title' => fake()->randomElement(['سائق شيول', 'سائق بوبكات', 'سائق بلدوزر', 'سائق تريلا']),
            'location' => fake()->randomElement(['نجران', 'المدينة', 'حائل', 'مكة', 'الرياض']),
            'license_type' => fake()->randomElement([
                'رخصة نقل ثقيل',
                'رخصة معدات ثقيلة',
                'رخصة قيادة عمومي',
                'رخصة تشغيل رافعات',
            ]),
            'license_expiry' => today()->addMonths(fake()->numberBetween(2, 18)),
            'unassigned_since' => today()->subDays(fake()->numberBetween(3, 25)),
            'status' => fake()->randomElement(UnassignedDriverStatus::cases()),
            'notes' => fake()->optional(0.7)->randomElement([
                'جاهز للاستلام الفوري في مشاريع المنطقة الوسطى',
                'بانتظار استكمال اختبار السلامة الميداني',
                'تم إرجاع المعدة السابقة للصيانة الشاملة',
            ]),
        ];
    }

    public function ready(int $idleDays = 5): static
    {
        return $this->state(fn (): array => [
            'status' => UnassignedDriverStatus::Ready,
            'unassigned_since' => today()->subDays($idleDays),
            'license_expiry' => today()->addMonths(8),
        ]);
    }

    public function onLeave(): static
    {
        return $this->state(fn (): array => [
            'status' => UnassignedDriverStatus::OnLeave,
            'unassigned_since' => today()->subDays(12),
            'license_expiry' => today()->addMonths(10),
        ]);
    }

    public function evaluation(int $idleDays = 4): static
    {
        return $this->state(fn (): array => [
            'status' => UnassignedDriverStatus::Evaluation,
            'unassigned_since' => today()->subDays($idleDays),
            'license_expiry' => today()->addMonths(10),
        ]);
    }

    public function expiredLicense(): static
    {
        return $this->state(fn (): array => [
            'status' => UnassignedDriverStatus::Ready,
            'license_expiry' => today()->subDays(15),
        ]);
    }
}
