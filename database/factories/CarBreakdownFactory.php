<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CarBreakdown;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CarBreakdown>
 */
class CarBreakdownFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = (string) fake()->numberBetween(2004, 2022);

        return [
            'car_code' => sprintf('C_%06d', fake()->numberBetween(1, 999)),
            'car_type' => fake()->randomElement(['متسوبيشي '.$year.' بكب غمارتين', 'تويوتا '.$year.' هايلكس', 'تويوتا '.$year.' كورولا', 'ايسوزو '.$year.' بكب غمارتين']),
            'plate_number' => fake()->randomElement(['أ ل ر ', 'ا ق م ', 'ب ص ع ', 'ب د هـ ']).fake()->numberBetween(1000, 9999),
            'model_year' => $year,
            'previous_receiver' => fake()->randomElement(['احمد عبد الستار', 'علي قاسم', 'بدون']),
            'employee_id' => (string) fake()->numberBetween(1000, 7999),
            'department' => fake()->randomElement(['التنفيذ', 'المعدات', 'الإنتاج', 'الكهرباء', 'المشتريات']),
            'job_title' => fake()->randomElement(['مهندس موقع', 'مشرف صيانة', 'مشرف تشغيل وصيانة', 'بدون']),
            'fuel_type' => fake()->randomElement(['بنزين', 'ديزل']),
            'branch' => fake()->randomElement(['نجران', 'الرياض', 'المدينة', 'الجوف', 'البحر الاحمر']),
            'condition_status' => fake()->randomElement(['ماكينة + عفشة', 'فحص دوري', 'تحتاج عفشة', 'فحص شامل']),
        ];
    }
}
