<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_breakdowns', function (Blueprint $table): void {
            $table->id();
            $table->string('car_code', 50)->nullable()->index();
            $table->string('car_type', 150)->nullable();
            $table->string('plate_number', 60)->nullable()->index();
            $table->string('model_year', 20)->nullable()->index();
            $table->string('previous_receiver', 150)->nullable();
            $table->string('employee_id', 50)->nullable()->index();
            $table->string('department', 120)->nullable()->index();
            $table->string('job_title', 150)->nullable();
            $table->string('fuel_type', 40)->nullable()->index();
            $table->string('branch', 100)->nullable()->index();
            $table->text('condition_status')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_breakdowns');
    }
};
