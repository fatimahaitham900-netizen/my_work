<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('ownership_transfers', function (Blueprint $table): void {
            $table->string('equipment_code', 50)->nullable()->change();
            $table->string('equipment_type', 120)->nullable()->change();
            $table->string('current_owner', 150)->nullable()->change();
            $table->string('target_owner', 150)->nullable()->change();
            $table->date('request_date')->nullable()->change();
            $table->date('expected_date')->nullable()->change();
        });

        Schema::table('equipment_breakdowns', function (Blueprint $table): void {
            $table->string('equipment_code', 50)->nullable()->change();
            $table->string('category', 30)->nullable()->change();
            $table->string('severity', 20)->nullable()->change();
            $table->date('breakdown_date')->nullable()->change();
            $table->string('repair_status', 30)->nullable()->change();
        });

        Schema::table('breakdown_driver_stoppages', function (Blueprint $table): void {
            $table->string('driver_name', 150)->nullable()->change();
            $table->string('employee_id', 50)->nullable()->change();
            $table->string('equipment_code', 50)->nullable()->change();
            $table->date('stoppage_start_date')->nullable()->change();
        });

        Schema::table('unassigned_drivers', function (Blueprint $table): void {
            $table->string('driver_name', 150)->nullable()->change();
            $table->string('employee_id', 50)->nullable()->change();
            $table->string('license_type', 80)->nullable()->change();
            $table->date('license_expiry')->nullable()->change();
            $table->date('unassigned_since')->nullable()->change();
        });

        Schema::table('regulatory_documents', function (Blueprint $table): void {
            $table->string('entity_type', 30)->nullable()->change();
            $table->string('entity_identifier', 100)->nullable()->change();
            $table->string('document_type', 100)->nullable()->change();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Making columns nullable is safely non-destructive; keeping schema flexible on rollback.
    }
};
