<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spare_part_transfers', function (Blueprint $table): void {
            $table->id();
            $table->text('item_description')->nullable();
            $table->string('from_equipment', 120)->nullable()->index();
            $table->string('to_equipment', 120)->nullable()->index();
            $table->date('transfer_date')->nullable()->index();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spare_part_transfers');
    }
};
