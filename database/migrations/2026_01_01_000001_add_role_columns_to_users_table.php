<?php

declare(strict_types=1);

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 30)
                ->default(UserRole::ExecutiveViewer->value)
                ->after('email')
                ->index();
            $table->string('job_title', 120)
                ->nullable()
                ->after('role');
            $table->boolean('is_active')
                ->default(true)
                ->after('job_title')
                ->index();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropSoftDeletes();
            $table->dropIndex(['role']);
            $table->dropIndex(['is_active']);
            $table->dropColumn(['role', 'job_title', 'is_active']);
        });
    }
};
