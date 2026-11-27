<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_distributions', function (Blueprint $table): void {
            $table->id();
            $table->string('equipment_code', 80)->nullable()->index();
            $table->string('equipment_type', 150)->nullable()->index();
            $table->string('branch', 120)->nullable()->index();
            $table->string('site_location', 200)->nullable()->index();
            $table->string('equipment_status', 150)->nullable()->index();
            $table->text('notes')->nullable();
            $table->json('extra_columns')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_distributions');
    }
};
