<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ownership_transfers', function (Blueprint $table): void {
            $table->string('branch', 120)->nullable()->after('equipment_type')->index();
            $table->string('chassis_number', 100)->nullable()->after('branch')->index();
        });
    }

    public function down(): void
    {
        Schema::table('ownership_transfers', function (Blueprint $table): void {
            $table->dropIndex(['branch']);
            $table->dropIndex(['chassis_number']);
            $table->dropColumn(['branch', 'chassis_number']);
        });
    }
};
