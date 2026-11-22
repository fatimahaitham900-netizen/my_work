<?php

declare(strict_types=1);

use App\Enums\BreakdownSeverity;
use App\Enums\RepairStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_breakdowns', function (Blueprint $table): void {
            $table->id();
            $table->string('equipment_code', 50)->index();
            $table->string('category', 30)->index();
            $table->string('severity', 20)
                ->default(BreakdownSeverity::Medium->value)
                ->index();
            $table->date('breakdown_date')->index();
            $table->date('workshop_entry_date')->nullable();
            $table->date('expected_repair_date')->nullable()->index();
            $table->string('repair_status', 30)
                ->default(RepairStatus::InWorkshop->value)
                ->index();
            $table->boolean('spare_parts_delay')->default(false)->index();
            $table->decimal('estimated_cost', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['repair_status', 'severity']);
            $table->index(['category', 'repair_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_breakdowns');
    }
};
