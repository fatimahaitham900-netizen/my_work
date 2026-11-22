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
            $table->string('renewal_status', 60)->nullable()->index()->after('compliance_status');
        });
    }

    public function down(): void
    {
        Schema::table('regulatory_documents', function (Blueprint $table): void {
            $table->dropIndex(['renewal_status']);
            $table->dropColumn('renewal_status');
        });
    }
};
