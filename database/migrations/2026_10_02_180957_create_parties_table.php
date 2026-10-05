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
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('name', 160);
            $table->string('legal_name', 160)->nullable();
            $table->string('rfc', 13)->nullable();
            $table->string('email', 160)->nullable();
            $table->string('phone', 40)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // The RFC is unique per team among live records only: see the
            // partial index created below.
            $table->index(['team_id', 'type']);
            $table->index(['team_id', 'name']);
        });

        // A deleted party must not reserve its RFC forever, so the uniqueness
        // is enforced with a partial index. PostgreSQL and SQLite support
        // partial indexes; MySQL would need a different strategy.
        DB::statement(
            'CREATE UNIQUE INDEX parties_team_id_rfc_unique ON parties (team_id, rfc) WHERE deleted_at IS NULL'
        );

        Schema::create('party_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('position', 120)->nullable();
            $table->string('email', 160)->nullable();
            $table->string('phone', 40)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'party_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('party_contacts');
        Schema::dropIfExists('parties');
    }
};
