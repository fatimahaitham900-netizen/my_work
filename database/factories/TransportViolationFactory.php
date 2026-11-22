<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TransportViolation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransportViolation>
 */
class TransportViolationFactory extends Factory
{
    protected $model = TransportViolation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'violation_number' => sprintf('100000%08d', fake()->numberBetween(10000000, 99999999)),
            'record_holder' => fake()->randomElement(['المسار الحديث المحدودة', 'المسار الحديث للمقاولات', 'الموظف']),
            'violation_date' => today()->subDays(fake()->numberBetween(5, 400)),
            'amount' => fake()->randomElement([600, 1000, 1500, 2400, 3800, 10000, 36000]),
            'department' => fake()->randomElement(['المعدات', 'انتاج نجران', 'بيتومين - انتاج', 'مشروع تبوك', 'المصنع']),
            'city' => fake()->randomElement(['نجران', 'الرياض', 'الحصينية', 'مكة', 'المدينة المنورة', 'تبوك']),
            'violation_description' => fake()->randomElement([
                'تجاوز الأحمال والأوزان النظامية للشاحنة',
                'تجاوز الوزن الإجمالي المسموح به بمقدار 2000 كجم',
                'تجاوز الأبعاد النظامية المقررة للشاحنة',
            ]),
            'objection_status' => fake()->randomElement([
                'لم يتم تقديمه في موعده',
                'تم عمل اعتراض وتم الرفض',
                'تم عمل اعتراض وفي انتظار النتيجه',
            ]),
            'plate_number' => sprintf('و ر ا %04d', fake()->numberBetween(1000, 9999)),
            'equipment_code' => sprintf('TR-%03d', fake()->numberBetween(1, 120)),
            'driver_name' => fake()->randomElement(['عبد القادر اشتياق', 'نور الله جان', 'صفوان عرفان', 'رياض أحمد الياس']),
            'employee_id' => (string) fake()->numberBetween(90, 6200),
            'employee_status' => fake()->randomElement(['متواجد على رأس العمل', 'خروج نهائي', 'مجاز']),
            'liability' => fake()->randomElement(['الشركة', 'الانتاج', 'المصنع', null]),
            'permit_status' => fake()->randomElement(['يوجد تصريح', 'لا يوجد', 'لا يوجد حيث الوزن لا يستدعي عمل تصريح']),
        ];
    }
}
