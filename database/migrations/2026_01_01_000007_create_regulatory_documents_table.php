<?php

declare(strict_types=1);

use App\Enums\ComplianceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regulatory_documents', function (Blueprint $table): void {
            $table->id();
            $table->string('entity_type', 30)->index();
            $table->string('entity_identifier', 100)->index();
            $table->string('document_type', 100)->index();
            $table->string('document_number', 100)->nullable()->index();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable()->index();
            $table->string('compliance_status', 30)
                ->default(ComplianceStatus::Valid->value)
                ->index();
            $table->decimal('renewal_cost', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['compliance_status', 'expiry_date']);
            $table->index(['entity_type', 'entity_identifier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regulatory_documents');
    }
};
