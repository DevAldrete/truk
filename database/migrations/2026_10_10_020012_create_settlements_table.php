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
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30);
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('carrier_party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->string('status', 20)->default('open');
            $table->string('currency', 3)->default('MXN');
            $table->integer('total_minor')->default(0);
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status']);
        });

        // A deleted settlement must not reserve its number forever.
        DB::statement(
            'CREATE UNIQUE INDEX settlements_team_id_number_unique ON settlements (team_id, number) WHERE deleted_at IS NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
