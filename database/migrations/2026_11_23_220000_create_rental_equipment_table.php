<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_equipment', function (Blueprint $table): void {
            $table->id();
            $table->string('contractor_name')->nullable();
            $table->string('equipment_type')->nullable()->index();
            $table->decimal('rental_amount', 12, 2)->default(0);
            $table->string('rental_type')->nullable()->index();
            $table->string('branch')->nullable()->index();
            $table->string('project')->nullable()->index();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_equipment');
    }
};
