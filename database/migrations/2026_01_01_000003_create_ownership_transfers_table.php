<?php

declare(strict_types=1);

use App\Enums\OwnershipTransferStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ownership_transfers', function (Blueprint $table): void {
            $table->id();
            $table->string('equipment_code', 50)->index();
            $table->string('equipment_type', 120);
            $table->string('current_owner', 150);
            $table->string('target_owner', 150);
            $table->date('request_date')->index();
            $table->date('expected_date')->index();
            $table->date('actual_date')->nullable();
            $table->string('status', 30)
                ->default(OwnershipTransferStatus::Pending->value)
                ->index();
            $table->text('blocker_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'expected_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ownership_transfers');
    }
};
