<?php

declare(strict_types=1);

use App\Enums\DriverStoppageStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breakdown_driver_stoppages', function (Blueprint $table): void {
            $table->id();
            $table->string('driver_name', 150);
            $table->string('employee_id', 50)->index();
            $table->string('phone', 30)->nullable();
            $table->string('equipment_code', 50)->index();
            $table->foreignId('breakdown_id')
                ->nullable()
                ->constrained('equipment_breakdowns')
                ->nullOnDelete();
            $table->date('stoppage_start_date')->index();
            $table->date('expected_resume_date')->nullable()->index();
            $table->string('status', 30)
                ->default(DriverStoppageStatus::Waiting->value)
                ->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'stoppage_start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breakdown_driver_stoppages');
    }
};
