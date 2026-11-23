<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\RentalEquipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentalEquipment>
 */
class RentalEquipmentFactory extends Factory
{
    protected $model = RentalEquipment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contractor_name' => fake()->randomElement(['شركة ابن الجزيرة المحدودة', 'مؤسسة الخليج للمعدات', 'شركة المسار للمقاولات']),
            'equipment_type' => fake()->randomElement(['حفار', 'شيول', 'قلاب', 'جريدر', 'رصاصة']),
            'rental_amount' => fake()->randomElement([12000, 15000, 16000, 18500, 22000]),
            'rental_type' => fake()->randomElement(['شهري', 'يومي', 'بالساعة']),
            'branch' => fake()->randomElement(['الرياض', 'نجران', 'مكة المكرمة', 'المدينة المنورة', 'أبها']),
            'project' => fake()->randomElement(['الحائر', 'أحد رفيدة', 'الطائف', 'جدة']),
            'notes' => null,
        ];
    }
}
