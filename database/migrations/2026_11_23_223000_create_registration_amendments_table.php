<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_amendments', function (Blueprint $table): void {
            $table->id();
            $table->string('equipment_code', 60)->nullable()->index();
            $table->string('equipment_type', 150)->nullable()->index();
            $table->string('branch', 120)->nullable()->index();
            $table->date('stoppage_date')->nullable()->index();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_amendments');
    }
};

