<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table): void {
            $table->string('staff_category', 40)->default('driver')->after('id')->index();
            $table->string('status', 60)->nullable()->after('sponsor')->index();
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table): void {
            $table->dropIndex(['staff_category']);
            $table->dropIndex(['status']);
            $table->dropColumn(['staff_category', 'status']);
        });
    }
};
