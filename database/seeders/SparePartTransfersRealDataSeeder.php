<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\SparePartTransfer;
use App\Services\DashboardService;
use Illuminate\Database\Seeder;

class SparePartTransfersRealDataSeeder extends Seeder
{
    /**
     * All 11 real spare part transfer records imported verbatim from the official sheet.
     *
     * @return list<array{0: string, 1: ?string, 2: ?string, 3: string, 4: ?string}>
     */
    public static function rows(): array
    {
        return [
            ['نقل طرمبة هيدروليك من كرين 14 الى كرين 6', 'كرين 14', 'كرين 6', '2026-08-05', null],
            ['نقل مكنة جريدر 19 الى جريدر 4', 'جريدر 19', 'جريدر 4', '2026-07-22', null],
            ['نقل مكنة شيول 3 الى شيول 8', 'شيول 3', 'شيول 8', '2026-09-01', null],
            ['نقل مروحة ورديتر من قلاب 54 الى وايت 45', 'قلاب 54', 'وايت 45', '2026-09-22', null],
            ['نقل كمبيوترات من قلاب 29 الى قلاب 18', 'قلاب 29', 'قلاب 18', '2026-02-23', null],
            ['نقل طرمبة هيدروليك من بكلين 26 الى بكلين 28', 'بكلين 26', 'بكلين 28', '2026-02-23', null],
            ['نقل ساسيه شيول 14 الى شيول 3', 'شيول 14', 'شيول 3', '2026-04-22', null],
            ['نقل طرمبة هيدروليك من شنيور 1 الى شيول 35', 'شنيور 1', 'شيول 35', '2026-09-24', null],
            ['نقل جير شيول 14 الى شيول 10', 'شيول 14', 'شيول 10', '2026-09-01', null],
            ['نقل مبرد زيت( كولر) من مولد 33 الى مولد 35 مكة', 'مولد 33', 'مولد 35 مكة', '2026-09-09', 'عدد 2كولد'],
            ['نقل طرمبة هيدروليك شيول 11 دون معرفة المعده المنقول منها', null, 'شيول 11', '2026-08-24', null],
        ];
    }

    public function run(): void
    {
        AuditLog::withoutRecording(function (): void {
            SparePartTransfer::withTrashed()->forceDelete();

            $now = now();
            $payload = [];

            foreach (self::rows() as $row) {
                $payload[] = [
                    'item_description' => $row[0],
                    'from_equipment' => $row[1],
                    'to_equipment' => $row[2],
                    'transfer_date' => $row[3],
                    'notes' => $row[4],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            SparePartTransfer::query()->insert($payload);
        });

        DashboardService::clearCache();
    }
}
