<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\MaintenanceDeviation;
use App\Services\DashboardService;
use Illuminate\Database\Seeder;

class MaintenanceDeviationsRealDataSeeder extends Seeder
{
    /**
     * All 17 daily maintenance deviation rows imported verbatim from the official PDF.
     * Format: [day_number, row_type, daily_rate, preventive_rate, daily_deviation, preventive_deviation, status_note]
     *
     * @return list<array{0: int, 1: string, 2: ?float, 3: ?float, 4: ?float, 5: ?float, 6: ?string}>
     */
    public static function records(): array
    {
        return [
            [1, 'on_leave', null, null, null, null, 'مسؤول الصيانة مجاز'],
            [2, 'on_leave', null, null, null, null, 'مسؤول الصيانة مجاز'],
            [3, 'on_leave', null, null, null, null, 'مسؤول الصيانة مجاز'],
            [4, 'on_leave', null, null, null, null, 'مسؤول الصيانة مجاز'],
            [5, 'active', 99, 65, 1, 35, null],
            [6, 'day_off', null, null, null, null, null],
            [7, 'active', 99, 60, 1, 40, null],
            [8, 'active', 99, 61, 1, 39, null],
            [9, 'active', 99, 56, 1, 44, null],
            [10, 'active', 99, 62, 1, 38, null],
            [11, 'active', 99, 70, 1, 30, null],
            [12, 'active', 99, 73, 1, 27, null],
            [13, 'day_off', null, null, null, null, null],
            [14, 'active', 99, 75, 1, 25, null],
            [15, 'active', 99, 77, 1, 23, null],
            [16, 'active', 99, 74, 1, 26, null],
            [17, 'active', 99, 74, 1, 26, null],
            [18, 'active', null, null, null, null, null],
            [19, 'active', null, null, null, null, null],
            [20, 'active', null, null, null, null, null],
            [21, 'active', null, null, null, null, null],
            [22, 'active', null, null, null, null, null],
            [23, 'active', null, null, null, null, null],
            [24, 'active', null, null, null, null, null],
            [25, 'active', null, null, null, null, null],
            [26, 'active', null, null, null, null, null],
            [27, 'active', null, null, null, null, null],
            [28, 'active', null, null, null, null, null],
            [29, 'active', null, null, null, null, null],
            [30, 'active', null, null, null, null, null],
        ];
    }

    public function run(): void
    {
        AuditLog::withoutRecording(function (): void {
            MaintenanceDeviation::withTrashed()->forceDelete();

            $now = now();
            $payload = [];

            foreach (self::records() as $row) {
                $payload[] = [
                    'day_number' => $row[0],
                    'row_type' => $row[1],
                    'daily_maintenance_rate' => $row[2],
                    'preventive_maintenance_rate' => $row[3],
                    'daily_deviation_rate' => $row[4],
                    'preventive_deviation_rate' => $row[5],
                    'status_note' => $row[6],
                    'notes' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            MaintenanceDeviation::query()->insert($payload);
        });

        DashboardService::clearCache();
    }
}
