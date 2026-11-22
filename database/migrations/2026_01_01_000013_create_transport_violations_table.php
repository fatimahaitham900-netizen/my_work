<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_violations', function (Blueprint $table): void {
            $table->id();
            $table->string('violation_number', 60)->nullable()->index();
            $table->string('record_holder', 120)->nullable()->index();
            $table->date('violation_date')->nullable()->index();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('department', 120)->nullable()->index();
            $table->string('city', 100)->nullable()->index();
            $table->text('violation_description')->nullable();
            $table->string('objection_status', 200)->nullable()->index();
            $table->string('plate_number', 60)->nullable()->index();
            $table->string('equipment_code', 50)->nullable()->index();
            $table->string('driver_name', 150)->nullable()->index();
            $table->string('employee_id', 50)->nullable()->index();
            $table->string('employee_status', 100)->nullable()->index();
            $table->string('liability', 120)->nullable()->index();
            $table->string('permit_status', 200)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['department', 'violation_date']);
            $table->index(['record_holder', 'objection_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_violations');
    }
};
