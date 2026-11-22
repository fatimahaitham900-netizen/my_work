<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SparePartTransfer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SparePartTransfer>
 */
class SparePartTransferFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $from = fake()->randomElement(['كرين 14', 'شيول 14', 'قلاب 54', 'مولد 33', 'بكلين 26']);
        $to = fake()->randomElement(['كرين 6', 'شيول 8', 'وايت 45', 'مولد 35 مكة', 'بكلين 28']);
        $part = fake()->randomElement(['طرمبة هيدروليك', 'مكنة', 'مروحة ورديتر', 'مبرد زيت (كولر)', 'جير']);

        return [
            'item_description' => sprintf('نقل %s من %s الى %s', $part, $from, $to),
            'from_equipment' => $from,
            'to_equipment' => $to,
            'transfer_date' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'notes' => fake()->optional(0.3)->randomElement(['عدد 2كولد', 'تم التركيب بنجاح', 'بانتظار توفير قطعة بديلة']),
        ];
    }

    public function unknownSource(): static
    {
        return $this->state(fn (): array => [
            'item_description' => 'نقل طرمبة هيدروليك شيول 11 دون معرفة المعده المنقول منها',
            'from_equipment' => null,
            'to_equipment' => 'شيول 11',
        ]);
    }
}
