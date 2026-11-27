<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EquipmentDistribution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentDistribution>
 */
class EquipmentDistributionFactory extends Factory
{
    protected $model = EquipmentDistribution::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'equipment_code' => 'EQ_'.$this->faker->unique()->numerify('######'),
            'equipment_type' => $this->faker->randomElement(['حفارات', 'قلابات', 'فرادة إسفلتية', 'شيولات', 'رصاصات']),
            'branch' => $this->faker->randomElement(['نجران', 'المدينة', 'أبها', 'مكة']),
            'site_location' => $this->faker->randomElement(['تشغيل نجران', 'مشروع الدار', 'مشروع أحد رفيدة', 'الورشة المركزية بنجران', 'مشروع ولي العهد']),
            'equipment_status' => $this->faker->randomElement(['تم تشغيلها ساعة أو أكثر', 'متعطلة في الورشة', 'متوقفة بدون عمل']),
            'notes' => $this->faker->randomElement(['بدون عمل فعلي', 'تقييد الي للمعدة في الورشة', '0', 'العداد لا يعمل']),
        ];
    }
}
