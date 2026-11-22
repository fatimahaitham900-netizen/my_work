<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_support_months', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('year')->nullable()->default(2026)->index();
            $table->unsignedTinyInteger('month')->nullable()->index();
            $table->string('title', 60)->nullable();
            $table->decimal('repair_support_amount', 14, 2)->nullable()->default(0);
            $table->decimal('custody_amount', 14, 2)->nullable()->default(0);
            $table->decimal('not_transferred_amount', 14, 2)->nullable();
            $table->unsignedInteger('total_equipment_count')->nullable()->default(0);
            $table->unsignedInteger('repaired_count')->nullable()->default(0);
            $table->unsignedInteger('under_repair_count')->nullable()->default(0);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('financial_support_transfers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('support_year')->nullable()->default(2026)->index();
            $table->unsignedTinyInteger('support_month')->nullable()->index();
            $table->string('equipment_type', 120)->nullable()->index();
            $table->string('equipment_code', 120)->nullable()->index();
            $table->decimal('amount', 14, 2)->nullable()->default(0);
            $table->string('beneficiary_name', 200)->nullable()->index();
            $table->text('fault_type')->nullable();
            $table->string('branch', 100)->nullable()->index();
            $table->string('case_status', 120)->nullable();
            $table->string('po_number', 120)->nullable()->index();
            $table->string('transfer_stage', 120)->nullable()->index();
            $table->string('transfer_date', 60)->nullable();
            $table->string('supply_date', 60)->nullable();
            $table->string('repair_date', 60)->nullable();
            $table->string('repair_status', 200)->nullable()->index();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_support_transfers');
        Schema::dropIfExists('financial_support_months');
    }
};
