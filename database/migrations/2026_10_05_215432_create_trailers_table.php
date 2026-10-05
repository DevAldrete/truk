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
        Schema::create('trailers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('carrier_party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->string('name', 160);
            $table->string('plate', 20);
            $table->string('configuration', 60);
            $table->unsignedBigInteger('max_payload_grams');
            $table->unsignedBigInteger('max_volume_cm3')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'name']);
        });

        // A deleted trailer must not reserve its plate forever, so uniqueness
        // is enforced with a partial index while the record is live.
        DB::statement(
            'CREATE UNIQUE INDEX trailers_team_id_plate_unique ON trailers (team_id, plate) WHERE deleted_at IS NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trailers');
    }
};
