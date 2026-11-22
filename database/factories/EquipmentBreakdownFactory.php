<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BreakdownCategory;
use App\Enums\BreakdownSeverity;
use App\Enums\RepairStatus;
use App\Models\EquipmentBreakdown;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentBreakdown>
 */
class EquipmentBreakdownFactory extends Factory
{
    protected $model = EquipmentBreakdown::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $breakdownDate = today()->subDays(fake()->numberBetween(1, 20));
        $repairStatus = fake()->randomElement(RepairStatus::cases());

        return [
            'equipment_code' => sprintf('%s_%06d', fake()->randomElement(['RD', 'GN', 'MT', 'TE', 'WL', 'CR', 'DT', 'BU', 'EX']), fake()->numberBetween(1, 99)),
            'equipment_name' => fake()->randomElement(['شنيور', 'مولد', 'مكسر', 'شيول', 'كرين', 'قلاب', 'بلدوزر', 'حفار']),
            'brand' => fake()->randomElement(['CAT', 'MAN', 'KOMATSU', 'JCB', 'VOLVO', 'HYANDAI', 'Mitsubishi']),
            'workshop_name' => fake()->randomElement(['ورشه نجران', 'ورشه المدينة', 'ورشه مكة المكرمة', 'ورشه الجنادريه']),
            'category' => fake()->randomElement(BreakdownCategory::cases()),
            'severity' => fake()->randomElement(BreakdownSeverity::cases()),
            'breakdown_date' => $breakdownDate,
            'workshop_entry_date' => $breakdownDate->copy()->addDay(),
            'expected_repair_date' => today()->addDays(fake()->numberBetween(2, 14)),
            'fault_description' => fake()->randomElement(['عمرة ماكينة', 'بخاخات + سلف', 'تهريب هيدروليك', 'فرامل وقماشات']),
            'repair_status' => $repairStatus,
            'workshop_status' => fake()->randomElement(['دعم مالي', 'قيد الاصلاح', 'جاري الكشف', 'متوقف تسعير', 'نقل للورشه المركزيه', 'مؤجل', 'توريد قطع']),
            'spare_parts_delay' => $repairStatus === RepairStatus::WaitingParts ? true : fake()->boolean(25),
            'estimated_cost' => fake()->randomFloat(2, 1500, 48000),
            'cost_note' => null,
            'notes' => fake()->randomElement([
                'تسرّب زيت في مضخة الهيدروليك الرئيسية ويتطلب استبدال طقم إصلاح',
                'عطل في وحدة التحكم الإلكترونية وحساسات المحرك',
                'تلف ناقل الحركة بالكامل وتوقف المعدة في الموقع',
                'تآكل شديد في الإطارات الخلفية وتوقف عن العمل للسلامة',
                'اصطدام جانبي داخل الموقع وتضرر الذراع الأمامي',
            ]),
        ];
    }

    public function critical(): static
    {
        return $this->state(fn (): array => [
            'severity' => BreakdownSeverity::Critical,
            'repair_status' => RepairStatus::WaitingParts,
            'spare_parts_delay' => true,
            'breakdown_date' => today()->subDays(9),
            'workshop_entry_date' => today()->subDays(8),
        ]);
    }

    public function medium(): static
    {
        return $this->state(fn (): array => [
            'severity' => BreakdownSeverity::Medium,
            'repair_status' => RepairStatus::InWorkshop,
            'spare_parts_delay' => false,
            'breakdown_date' => today()->subDays(4),
            'workshop_entry_date' => today()->subDays(3),
        ]);
    }

    public function minor(): static
    {
        return $this->state(fn (): array => [
            'severity' => BreakdownSeverity::Minor,
            'repair_status' => RepairStatus::InWorkshop,
            'spare_parts_delay' => false,
            'breakdown_date' => today()->subDays(2),
            'workshop_entry_date' => today()->subDay(),
        ]);
    }

    public function repaired(): static
    {
        return $this->state(fn (): array => [
            'repair_status' => RepairStatus::Repaired,
            'spare_parts_delay' => false,
            'expected_repair_date' => today()->subDay(),
        ]);
    }

    public function waitingParts(): static
    {
        return $this->state(fn (): array => [
            'repair_status' => RepairStatus::WaitingParts,
            'spare_parts_delay' => true,
        ]);
    }
}
