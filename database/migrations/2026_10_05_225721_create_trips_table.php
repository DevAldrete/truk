<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30);
            $table->string('status', 20)->default('planned');
            $table->timestampTz('planned_start_at')->nullable();
            $table->timestampTz('planned_end_at')->nullable();
            $table->string('timezone', 64)->nullable();

            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trailer_id')->nullable()->constrained()->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status']);
            $table->index(['team_id', 'planned_start_at']);
            $table->index('driver_id');
            $table->index('vehicle_id');
            $table->index('trailer_id');
        });

        // A deleted trip must not reserve its number forever.
        DB::statement(
            'CREATE UNIQUE INDEX trips_team_id_number_unique ON trips (team_id, number) WHERE deleted_at IS NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
