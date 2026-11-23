<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('car_breakdowns', function (Blueprint $table): void {
            $table->string('vehicle_category', 30)->default('سيارة')->after('car_code')->index();
        });
    }

    public function down(): void
    {
        Schema::table('car_breakdowns', function (Blueprint $table): void {
            $table->dropIndex(['vehicle_category']);
            $table->dropColumn('vehicle_category');
        });
    }
};
