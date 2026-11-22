<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('breakdown_driver_stoppages', function (Blueprint $table): void {
            $table->string('job_title', 100)->nullable()->after('employee_id');
            $table->string('branch', 100)->nullable()->after('job_title')->index();
            $table->string('equipment_type', 100)->nullable()->after('equipment_code');
            $table->text('fault_diagnosis')->nullable()->after('expected_resume_date');
            $table->string('workshop_status', 120)->nullable()->after('fault_diagnosis')->index();
            $table->text('action_taken')->nullable()->after('workshop_status');
        });
    }

    public function down(): void
    {
        Schema::table('breakdown_driver_stoppages', function (Blueprint $table): void {
            $table->dropIndex(['branch']);
            $table->dropIndex(['workshop_status']);
            $table->dropColumn([
                'job_title',
                'branch',
                'equipment_type',
                'fault_diagnosis',
                'workshop_status',
                'action_taken',
            ]);
        });
    }
};
