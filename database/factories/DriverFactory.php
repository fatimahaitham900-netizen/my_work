<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    protected $model = Driver::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'equipment_code' => 'AP_'.$this->faker->unique()->numerify('######'),
            'branch' => $this->faker->randomElement(['نجران', 'المدينة', 'أبها', 'مكة']),
            'site_location' => $this->faker->randomElement(['تشغيل -نجران', 'مشروع الدار', 'مشروع أحد رفيدة', 'مشروع ولي العهد']),
            'driver_name' => $this->faker->name(),
            'employee_id' => (string) $this->faker->numberBetween(100, 9999),
            'job_title' => $this->faker->randomElement(['سائق فرادة اسفلت', 'سائق رصاصة', 'سائق بوبكات', 'سائق بوكلين']),
            'nationality' => $this->faker->randomElement(['India', 'Pakistan', 'Yemen', 'Nepal', 'Bangladesh']),
            'salary' => $this->faker->numberBetween(1500, 4200),
            'sponsor' => 'كفاله شركة',
        ];
    }
}
