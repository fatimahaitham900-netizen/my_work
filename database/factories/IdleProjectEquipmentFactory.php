<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\IdleProjectEquipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IdleProjectEquipment>
 */
class IdleProjectEquipmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'equipment_code' => sprintf('%s_%06d', fake()->randomElement(['AP', 'AR', 'BU', 'BL', 'DT', 'EX', 'WL', 'BK']), fake()->numberBetween(1, 999)),
            'equipment_type' => fake()->randomElement(['فرادات', 'رصاصات إسفلتيه', 'بلدوزرات', 'باك هو لودر', 'قلابات', 'بوكلينات', 'شيولات', 'بوبكات']),
            'driver_name' => fake()->randomElement(['عدنان محمد العباسي', 'محمد هارون خان', 'جعفر علي', 'فكري محمد فرحان نصر']),
            'employee_id' => (string) fake()->numberBetween(100, 7999),
            'branch' => fake()->randomElement(['ابها', 'الرياض', 'المدينة', 'جدة', 'حائل', 'مكة', 'نجران']),
            'site_location' => fake()->randomElement(['مشروع احد روفيدة', 'مشروع الحائر', 'مشروع الجنادرية', 'كسارة الشفا', 'وحدات إنتاج نجران']),
            'equipment_status' => 'بدون عمل فعلي',
            'total_idle_days' => fake()->numberBetween(1, 14),
        ];
    }
}
