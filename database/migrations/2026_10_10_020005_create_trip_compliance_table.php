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
        // The immutable fiscal snapshot of a trip. A stamped document is never
        // overwritten; a correction creates a new row. The payload is frozen so
        // a later edit to the trip cannot invalidate an issued document.
        Schema::create('trip_compliance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('schema_version', 20);
            $table->string('status', 20)->default('pending');
            $table->string('provider', 60)->nullable();
            $table->string('cfdi_uuid', 40)->nullable();
            $table->json('payload');
            $table->string('xml_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestampTz('stamped_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'trip_id']);
            $table->unique(['team_id', 'cfdi_uuid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_compliance');
    }
};
