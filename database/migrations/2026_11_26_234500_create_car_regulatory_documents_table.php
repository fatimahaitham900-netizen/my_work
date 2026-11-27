<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_regulatory_documents', function (Blueprint $table): void {
            $table->id();
            $table->string('document_type', 40)->default('فحص دوري')->index();
            $table->string('car_code', 60)->nullable()->index();
            $table->string('plate_number', 60)->nullable()->index();
            $table->string('car_type_model', 180)->nullable();
            $table->string('branch', 120)->nullable()->index();
            $table->string('project_location', 180)->nullable()->index();
            $table->string('hijri_expiry_date', 40)->nullable();
            $table->date('expiry_date')->nullable()->index();
            $table->string('status_label', 120)->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_regulatory_documents');
    }
};
