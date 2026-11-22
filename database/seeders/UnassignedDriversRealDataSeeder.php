<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UnassignedDriverStatus;
use App\Models\AuditLog;
use App\Models\UnassignedDriver;
use App\Services\DashboardService;
use Illuminate\Database\Seeder;

class UnassignedDriversRealDataSeeder extends Seeder
{
    public function run(): void
    {
        AuditLog::withoutRecording(function (): void {
            UnassignedDriver::withTrashed()->forceDelete();

            $records = [
                [
                    'driver_name' => 'ابو الوفا محمد ابو الوفا احمد',
                    'job_title' => 'سائق شيول',
                    'employee_id' => '2312',
                    'location' => 'نجران',
                    'license_type' => 'رخصة معدات ثقيلة',
                    'license_expiry' => today()->addMonths(10),
                    'unassigned_since' => today()->subDays(7),
                    'status' => UnassignedDriverStatus::Ready,
                    'notes' => null,
                ],
                [
                    'driver_name' => 'صدام حسين صديقي',
                    'job_title' => 'سائق بوبكات',
                    'employee_id' => '1807',
                    'location' => 'نجران',
                    'license_type' => 'رخصة معدات ثقيلة',
                    'license_expiry' => today()->addMonths(8),
                    'unassigned_since' => today()->subDays(7),
                    'status' => UnassignedDriverStatus::Ready,
                    'notes' => null,
                ],
                [
                    'driver_name' => 'أورنجزيب محمد أكرم',
                    'job_title' => 'سائق بلدوزر',
                    'employee_id' => '1036',
                    'location' => 'نجران',
                    'license_type' => 'رخصة معدات ثقيلة',
                    'license_expiry' => today()->addMonths(9),
                    'unassigned_since' => today()->subDays(7),
                    'status' => UnassignedDriverStatus::Ready,
                    'notes' => null,
                ],
                [
                    'driver_name' => 'طه جمال طه ابو النجا',
                    'job_title' => 'سائق شيول',
                    'employee_id' => '5153',
                    'location' => 'المدينة',
                    'license_type' => 'رخصة معدات ثقيلة',
                    'license_expiry' => today()->addMonths(11),
                    'unassigned_since' => today()->subDays(7),
                    'status' => UnassignedDriverStatus::Ready,
                    'notes' => null,
                ],
                [
                    'driver_name' => 'جورنام سينج دارشان سينج',
                    'job_title' => 'سائق تريلا',
                    'employee_id' => '5968',
                    'location' => 'حائل',
                    'license_type' => 'رخصة نقل ثقيل',
                    'license_expiry' => today()->addMonths(6),
                    'unassigned_since' => today()->subDays(14),
                    'status' => UnassignedDriverStatus::Ready,
                    'notes' => 'بينتهي عقده بتاريخ 23/11/2026 وواقف عن العمل بدون معده',
                ],
            ];

            foreach ($records as $record) {
                UnassignedDriver::query()->create($record);
            }
        });

        DashboardService::clearCache();
    }
}
