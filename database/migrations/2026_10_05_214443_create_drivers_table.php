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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('carrier_party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->string('name', 160);
            $table->string('phone', 40);
            $table->string('license_number', 60)->nullable();
            $table->date('license_expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'name']);
        });

        // A deleted driver must not reserve its licence forever, so uniqueness
        // is enforced with a partial index while the record is live.
        DB::statement(
            'CREATE UNIQUE INDEX drivers_team_id_license_number_unique ON drivers (team_id, license_number) WHERE deleted_at IS NULL'
        );

        // A login can back at most one live driver per team; a deleted driver
        // must not reserve the login forever.
        DB::statement(
            'CREATE UNIQUE INDEX drivers_team_id_user_id_unique ON drivers (team_id, user_id) WHERE user_id IS NOT NULL AND deleted_at IS NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
