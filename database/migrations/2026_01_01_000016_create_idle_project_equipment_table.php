<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idle_project_equipment', function (Blueprint $table): void {
            $table->id();
            $table->string('equipment_code', 50)->nullable()->index();
            $table->string('equipment_type', 120)->nullable()->index();
            $table->string('driver_name', 150)->nullable();
            $table->string('employee_id', 50)->nullable()->index();
            $table->string('branch', 100)->nullable()->index();
            $table->string('site_location', 150)->nullable()->index();
            $table->string('equipment_status', 120)->nullable()->index();
            $table->unsignedInteger('total_idle_days')->nullable()->default(0)->index();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idle_project_equipment');
    }
};
