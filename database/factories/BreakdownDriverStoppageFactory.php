<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DriverStoppageStatus;
use App\Models\BreakdownDriverStoppage;
use App\Models\EquipmentBreakdown;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BreakdownDriverStoppage>
 */
class BreakdownDriverStoppageFactory extends Factory
{
    protected $model = BreakdownDriverStoppage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = today()->subDays(fake()->numberBetween(1, 16));

        return [
            'driver_name' => fake()->randomElement([
                'سعد فهد الدوسري',
                'عبدالرحمن علي الحربي',
                'تركي سليمان الشمري',
                'ماجد حسن الغامدي',
                'ياسر صالح المطيري',
                'وليد إبراهيم العنزي',
                'بدر فيصل الشهري',
                'نواف حمد البقمي',
            ]),
            'employee_id' => sprintf('%04d', fake()->numberBetween(1001, 9999)),
            'job_title' => fake()->randomElement(['سائق قلاب', 'سائق حفار', 'سائق بلدوزر', 'سائق شيول', 'سائق تريلا']),
            'branch' => fake()->randomElement(['نجران', 'مكة', 'المدينة', 'احدرفيده']),
            'phone' => sprintf('05%08d', fake()->numberBetween(10000000, 99999999)),
            'equipment_code' => sprintf('%s_%06d', fake()->randomElement(['DT', 'EX', 'BU', 'WL', 'GR']), fake()->numberBetween(1, 99)),
            'equipment_type' => fake()->randomElement(['قلاب', 'حفار', 'بلدوزر', 'شيول', 'تريلا', 'جريدر']),
            'breakdown_id' => null,
            'stoppage_start_date' => $startDate,
            'expected_resume_date' => today()->addDays(fake()->numberBetween(1, 10)),
            'fault_diagnosis' => fake()->randomElement(['فرامل وقماشات', 'عطل دقاق', 'بلف الرفع', 'كسر كرسي سوسته']),
            'workshop_status' => fake()->randomElement(['توريد قطع', 'قيد الاصلاح', 'متوقف تسعير', 'جاري الاصلاح']),
            'action_taken' => null,
            'status' => fake()->randomElement(DriverStoppageStatus::cases()),
            'notes' => fake()->optional(0.7)->randomElement([
                'السائق متوقف بالموقع لحين استلام المعدة من الورشة المركزية',
                'بانتظار وصول قطع الغيار لإعادة التشغيل',
                'تم التنسيق مع مشرف التشغيل لتوفير معدة بديلة مؤقتة',
            ]),
        ];
    }

    public function waiting(int $idleDays = 10): static
    {
        return $this->state(fn (): array => [
            'stoppage_start_date' => today()->subDays($idleDays),
            'expected_resume_date' => today()->addDays(4),
            'status' => DriverStoppageStatus::Waiting,
        ]);
    }

    public function reassigned(): static
    {
        return $this->state(fn (): array => [
            'status' => DriverStoppageStatus::Reassigned,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (): array => [
            'status' => DriverStoppageStatus::Resolved,
            'expected_resume_date' => today()->subDay(),
        ]);
    }

    public function forBreakdown(EquipmentBreakdown $breakdown): static
    {
        return $this->state(fn (): array => [
            'breakdown_id' => $breakdown->id,
            'equipment_code' => $breakdown->equipment_code,
            'stoppage_start_date' => $breakdown->breakdown_date ?? today()->subDays(5),
        ]);
    }
}
