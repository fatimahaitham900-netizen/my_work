<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\DashboardService;
use Illuminate\Database\Seeder;

class FleetDeviationsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OwnershipTransfersRealDataSeeder::class,
            BreakdownDriverStoppagesRealDataSeeder::class,
            UnassignedDriversRealDataSeeder::class,
            EquipmentBreakdownsRealDataSeeder::class,
            RegulatoryDocumentsRealDataSeeder::class,
            TransportViolationsRealDataSeeder::class,
            SparePartTransfersRealDataSeeder::class,
            CarBreakdownsRealDataSeeder::class,
            IdleProjectEquipmentRealDataSeeder::class,
            FinancialSupportRealDataSeeder::class,
            MaintenanceDeviationsRealDataSeeder::class,
        ]);

        DashboardService::clearCache();
    }
}
