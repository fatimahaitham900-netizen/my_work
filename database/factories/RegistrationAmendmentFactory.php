<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\RegistrationAmendment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistrationAmendment>
 */
class RegistrationAmendmentFactory extends Factory
{
    protected $model = RegistrationAmendment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'equipment_code' => 'EQ_'.str_pad((string) fake()->numberBetween(1, 999), 6, '0', STR_PAD_LEFT),
            'equipment_type' => fake()->randomElement(['قلاب', 'تريلا', 'وايت', 'شيول', 'دينا']),
            'branch' => fake()->randomElement(['نجران', 'الرياض', 'مكة المكرمة', 'المدينة المنورة', 'أبها']),
            'stoppage_date' => today()->subDays(fake()->numberBetween(1, 45)),
            'notes' => null,
        ];
    }
}

