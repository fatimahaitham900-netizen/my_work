<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_breakdowns', function (Blueprint $table): void {
            $table->string('equipment_name', 100)->nullable()->after('equipment_code')->index();
            $table->string('brand', 80)->nullable()->after('equipment_name')->index();
            $table->string('workshop_name', 120)->nullable()->after('brand')->index();
            $table->text('fault_description')->nullable()->after('expected_repair_date');
            $table->string('workshop_status', 100)->nullable()->after('repair_status')->index();
            $table->string('cost_note', 120)->nullable()->after('estimated_cost');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_breakdowns', function (Blueprint $table): void {
            $table->dropIndex(['equipment_name']);
            $table->dropIndex(['brand']);
            $table->dropIndex(['workshop_name']);
            $table->dropIndex(['workshop_status']);
            $table->dropColumn([
                'equipment_name',
                'brand',
                'workshop_name',
                'fault_description',
                'workshop_status',
                'cost_note',
            ]);
        });
    }
};
