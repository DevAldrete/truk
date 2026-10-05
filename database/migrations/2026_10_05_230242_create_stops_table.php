<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence')->default(0);
            $table->string('type', 20)->default('delivery');
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();

            // Address snapshot, frozen when the stop is planned.
            $table->json('location_snapshot')->nullable();
            $table->timestampTz('planned_at')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'trip_id', 'sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stops');
    }
};
