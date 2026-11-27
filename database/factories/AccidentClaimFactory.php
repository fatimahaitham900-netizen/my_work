<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AccidentClaim;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccidentClaim>
 */
class AccidentClaimFactory extends Factory
{
    protected $model = AccidentClaim::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehicle_code' => 'C_'.$this->faker->unique()->numerify('######'),
            'plate_number' => 'أ ب ج '.$this->faker->numerify('####'),
            'accident_date' => $this->faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'accident_location' => $this->faker->randomElement(['طريق الملك فهد - الرياض', 'مشروع ولي العهد - مكة', 'طريق أبها العام', 'نجران']),
            'branch' => $this->faker->randomElement(['الرياض', 'مكة', 'أبها', 'نجران']),
            'driver_name' => $this->faker->name(),
            'employee_id' => (string) $this->faker->numberBetween(1000, 9999),
            'najm_report_number' => 'NJM-'.$this->faker->numerify('######'),
            'claim_number' => 'CLM-'.$this->faker->numerify('#####'),
            'insurance_company' => $this->faker->randomElement(['التعاونية', 'بوبا', 'ميدغلف', 'ولاء للتأمين']),
            'fault_percentage' => $this->faker->randomElement([0, 25, 50, 75, 100]),
            'estimated_cost' => $this->faker->numberBetween(2000, 35000),
            'compensation_amount' => $this->faker->numberBetween(0, 25000),
            'claim_status' => $this->faker->randomElement(['تحت الإجراء', 'مرفوعة للتأمين', 'تم التعويض', 'مغلقة']),
            'notes' => 'تقرير نجم مرفق',
        ];
    }
}
