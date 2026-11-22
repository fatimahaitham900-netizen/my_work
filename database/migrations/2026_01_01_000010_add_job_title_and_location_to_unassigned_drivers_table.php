<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unassigned_drivers', function (Blueprint $table): void {
            $table->string('job_title', 100)->nullable()->after('employee_id');
            $table->string('location', 100)->nullable()->after('job_title')->index();
        });
    }

    public function down(): void
    {
        Schema::table('unassigned_drivers', function (Blueprint $table): void {
            $table->dropIndex(['location']);
            $table->dropColumn(['job_title', 'location']);
        });
    }
};
