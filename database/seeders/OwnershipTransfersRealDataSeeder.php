<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OwnershipTransferStatus;
use App\Models\AuditLog;
use App\Models\OwnershipTransfer;
use App\Services\DashboardService;
use Illuminate\Database\Seeder;

class OwnershipTransfersRealDataSeeder extends Seeder
{
    public function run(): void
    {
        AuditLog::withoutRecording(function (): void {
            OwnershipTransfer::withTrashed()->forceDelete();

            $records = [
                ['BK-005', 'بوبكات', 'المدينة المنوره', 'A1Z776061', 'لا يوجد بطاقة جمركية'],
                ['BK-006', 'بوبكات', 'الحائر', 'B1E 612735', 'لا يوجد بطاقة جمركية'],
                ['BK-009', 'بوبكات', 'نجران', 'A1Z774677', 'لا يوجد بطاقة جمركية'],
                ['BL-005', 'باك هو لودر', 'المدينة المنوره', 'CTA0422EAMAW01178', 'لا يوجد بطاقة جمركية'],
                ['BL-006', 'باك هو لودر', 'الجوف', 'CTA0422EVMAW01180', 'لا يوجد بطاقة جمركية'],
                ['BL-007', 'باك هو لودر', 'الجوف', 'CTA0422ECMAW01171', 'لا يوجد بطاقة جمركية'],
                ['BL-008', 'باك هو لودر', 'الجوف', 'CTA0422EVMAW01177', 'لا يوجد بطاقة جمركية'],
                ['BL-015', 'باك هو لودر', 'نجران', 'JCB3CX4TJ02107644', 'لا يوجد بطاقة جمركية'],
                ['BL-017', 'باك هو لودر', 'المدينة المنوره', 'JCB3XC4TB02260134', 'لا يوجد بطاقة جمركية'],
                ['BL-018', 'لودر باك', 'الرياض', 'CAT0422FHLRH0663', 'لا يوجد بطاقة جمركية'],
                ['CR-006', 'كرين', 'المدينة المنوره', 'KW31M-00593', 'لا يوجد بطاقة جمركية'],
                ['CR-012', 'كرين كوماتسو', 'نجران', 'L00415120', 'لا يوجد بطاقة جمركية'],
                ['FK-004', 'رافعة شوكية', 'نجران', 'HHKHFZ07AD0000008', 'لا يوجد بطاقة جمركية'],
                ['FK-012', 'رافعة شوكية', 'المدينة المنوره', 'CT28C51581', 'لا يوجد بطاقة جمركية'],
                ['TE-002', 'رافعه', 'المدينة المنوره', 'JCB5AF8GH02353784', 'لا يوجد بطاقة جمركية'],
                ['TE-003', 'رافعه', 'المدينة المنوره', 'JCB5AF8GJG2461910', 'لا يوجد بطاقة جمركية'],
                ['TE-004', 'رافعه', 'المدينة المنوره', 'JCB5AFMGAM3060695', 'لا يوجد بطاقة جمركية'],
            ];

            foreach ($records as [$code, $type, $branch, $chassis, $note]) {
                OwnershipTransfer::query()->create([
                    'equipment_code' => $code,
                    'equipment_type' => $type,
                    'branch' => $branch,
                    'chassis_number' => $chassis,
                    'current_owner' => 'فرع '.$branch,
                    'target_owner' => 'الإدارة العامة للمعدات',
                    'request_date' => today()->subDays(15),
                    'expected_date' => today()->subDays(5),
                    'actual_date' => null,
                    'status' => OwnershipTransferStatus::Delayed,
                    'blocker_reason' => $note,
                    'notes' => $note,
                ]);
            }
        });

        DashboardService::clearCache();
    }
}
