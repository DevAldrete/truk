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
        Schema::create('trip_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();

            // Exactly one resource per row: a driver, a vehicle, or a trailer.
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trailer_id')->nullable()->constrained()->nullOnDelete();

            $table->timestampTz('assigned_at');
            $table->timestampTz('released_at')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'trip_id']);
            $table->index(['trip_id', 'released_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_assignments');
    }
};
