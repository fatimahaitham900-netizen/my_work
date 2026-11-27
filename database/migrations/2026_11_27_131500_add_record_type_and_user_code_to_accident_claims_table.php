<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accident_claims', function (Blueprint $table): void {
            $table->string('record_type', 30)->default('insurance_claim')->after('id')->index();
            $table->string('user_code', 80)->nullable()->after('vehicle_code')->index();
            $table->string('record_holder', 150)->nullable()->after('branch')->index();
        });
    }

    public function down(): void
    {
        Schema::table('accident_claims', function (Blueprint $table): void {
            $table->dropColumn(['record_type', 'user_code', 'record_holder']);
        });
    }
};
