<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_breakdowns', function (Blueprint $table): void {
            $table->string('expected_repair_date', 191)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('equipment_breakdowns', function (Blueprint $table): void {
            $table->date('expected_repair_date')->nullable()->change();
        });
    }
};
