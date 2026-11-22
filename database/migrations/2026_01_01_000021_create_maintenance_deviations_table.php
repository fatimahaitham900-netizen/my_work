<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_deviations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('day_number')->nullable()->index();
            $table->string('row_type', 30)->default('active')->index();
            $table->decimal('daily_maintenance_rate', 5, 2)->nullable();
            $table->decimal('preventive_maintenance_rate', 5, 2)->nullable();
            $table->decimal('daily_deviation_rate', 5, 2)->nullable();
            $table->decimal('preventive_deviation_rate', 5, 2)->nullable();
            $table->string('status_note', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_deviations');
    }
};
