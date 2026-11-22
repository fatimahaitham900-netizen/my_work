<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FinancialSupportTransfer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialSupportTransfer>
 */
class FinancialSupportTransferFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'support_year' => 2026,
            'support_month' => fake()->randomElement([8, 9, 10, 11]),
            'equipment_type' => fake()->randomElement(['شيول', 'حفار', 'مولد', 'قلاب', 'جريدر', 'رصاصة اسفلتية']),
            'equipment_code' => sprintf('%s-%03d', fake()->randomElement(['WL', 'EX', 'GN', 'DT', 'GR', 'AR']), fake()->numberBetween(1, 99)),
            'amount' => fake()->randomElement([1500, 2400, 4485, 6800, 9500, 11500]),
            'beneficiary_name' => fake()->randomElement(['اتحاد الخير', 'عبدالقوي محمد', 'العريني', 'محاسب مكة', 'روائع نجران']),
            'fault_type' => fake()->randomElement(['اصلاح طلمبة ديزل', 'اصلاح بخاخات', 'تصنيع رديتر', 'بلف هيدروليك']),
            'branch' => fake()->randomElement(['نجران', 'الجنادرية', 'مكة المكرمة', 'المدينة المنورة', 'الجوف']),
            'case_status' => 'استهلاك طبيعي',
            'po_number' => 'PO-000'.fake()->numberBetween(4000, 6999),
            'transfer_stage' => fake()->randomElement(['تم التحويل', 'دعم مالي', 'لم يتم التحويل']),
            'transfer_date' => '08/09/2026',
            'supply_date' => '15-08-2026',
            'repair_date' => '18-08-2026',
            'repair_status' => fake()->randomElement(['تم الاصلاح', 'لم يتم الاصلاح', 'STOK']),
        ];
    }
}
