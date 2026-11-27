<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table): void {
            $table->id();
            $table->string('equipment_code', 80)->nullable()->index();
            $table->string('branch', 120)->nullable()->index();
            $table->string('site_location', 200)->nullable()->index();
            $table->string('driver_name', 180)->nullable()->index();
            $table->string('employee_id', 60)->nullable()->index();
            $table->string('job_title', 150)->nullable()->index();
            $table->string('nationality', 100)->nullable()->index();
            $table->decimal('salary', 12, 2)->default(0);
            $table->string('sponsor', 150)->nullable()->index();
            $table->text('notes')->nullable();
            $table->json('extra_columns')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
