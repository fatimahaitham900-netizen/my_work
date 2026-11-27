<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accident_claims', function (Blueprint $table): void {
            $table->id();
            $table->string('vehicle_code', 80)->nullable()->index();
            $table->string('plate_number', 80)->nullable()->index();
            $table->date('accident_date')->nullable()->index();
            $table->string('accident_location', 200)->nullable();
            $table->string('branch', 120)->nullable()->index();
            $table->string('driver_name', 180)->nullable()->index();
            $table->string('employee_id', 60)->nullable();
            $table->string('najm_report_number', 100)->nullable()->index();
            $table->string('claim_number', 100)->nullable()->index();
            $table->string('insurance_company', 150)->nullable()->index();
            $table->unsignedSmallInteger('fault_percentage')->nullable();
            $table->decimal('estimated_cost', 12, 2)->default(0);
            $table->decimal('compensation_amount', 12, 2)->default(0);
            $table->string('claim_status', 100)->nullable()->index();
            $table->text('notes')->nullable();
            $table->json('extra_columns')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accident_claims');
    }
};
