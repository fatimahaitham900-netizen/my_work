<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [
        'ownership_transfers',
        'breakdown_driver_stoppages',
        'unassigned_drivers',
        'equipment_breakdowns',
        'regulatory_documents',
        'car_regulatory_documents',
        'transport_violations',
        'spare_part_transfers',
        'car_breakdowns',
        'idle_project_equipment',
        'financial_support_transfers',
        'maintenance_deviations',
        'rental_equipment',
        'registration_amendments',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'extra_columns')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->json('extra_columns')->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'extra_columns')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropColumn('extra_columns');
                });
            }
        }
    }
};
