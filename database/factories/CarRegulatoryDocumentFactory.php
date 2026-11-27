<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CarRegulatoryDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CarRegulatoryDocument>
 */
class CarRegulatoryDocumentFactory extends Factory
{
    protected $model = CarRegulatoryDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_type' => fake()->randomElement([
                CarRegulatoryDocument::TYPE_INSPECTION,
                CarRegulatoryDocument::TYPE_REGISTRATION,
                CarRegulatoryDocument::TYPE_INSURANCE,
            ]),
            'car_code' => 'C_'.str_pad((string) fake()->numberBetween(1, 999), 6, '0', STR_PAD_LEFT),
            'plate_number' => 'أ ب ج '.fake()->numberBetween(1000, 9999),
            'car_type_model' => fake()->randomElement(['تويوتا هايلكس غمارتين (2018)', 'ميتسوبيشي غمارتين (2011)', 'ايسوزو غمارتين (2019)']),
            'branch' => fake()->randomElement(['أبها', 'البحر الأحمر', 'الجوف', 'نجران', 'الرياض']),
            'project_location' => fake()->randomElement(['مشروع باللحمر', 'مشروع السلاحف', 'بدون مشروع', 'الجوف']),
            'hijri_expiry_date' => '1448-03-05',
            'expiry_date' => today()->subDays(fake()->numberBetween(5, 250)),
            'status_label' => null,
            'notes' => null,
        ];
    }
}
