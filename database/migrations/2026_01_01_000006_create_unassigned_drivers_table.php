<?php

declare(strict_types=1);

use App\Enums\UnassignedDriverStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unassigned_drivers', function (Blueprint $table): void {
            $table->id();
            $table->string('driver_name', 150);
            $table->string('employee_id', 50)->index();
            $table->string('license_type', 80);
            $table->date('license_expiry')->index();
            $table->date('unassigned_since')->index();
            $table->string('status', 30)
                ->default(UnassignedDriverStatus::Ready->value)
                ->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'unassigned_since']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unassigned_drivers');
    }
};
