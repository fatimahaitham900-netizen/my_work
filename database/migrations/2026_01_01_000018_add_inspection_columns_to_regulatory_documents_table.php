<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regulatory_documents', function (Blueprint $table): void {
            $table->string('equipment_type', 120)->nullable()->index()->after('entity_identifier');
            $table->string('plate_number', 60)->nullable()->index()->after('equipment_type');
            $table->string('transport_type', 60)->nullable()->index()->after('plate_number');
            $table->string('branch', 100)->nullable()->index()->after('transport_type');
            $table->string('hijri_expiry_date', 30)->nullable()->after('issue_date');
        });
    }

    public function down(): void
    {
        Schema::table('regulatory_documents', function (Blueprint $table): void {
            $table->dropIndex(['equipment_type']);
            $table->dropIndex(['plate_number']);
            $table->dropIndex(['transport_type']);
            $table->dropIndex(['branch']);
            $table->dropColumn([
                'equipment_type',
                'plate_number',
                'transport_type',
                'branch',
                'hijri_expiry_date',
            ]);
        });
    }
};
