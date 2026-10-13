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
        Schema::table('stops', function (Blueprint $table) {
            // Actual times captured from driver actions, kept separate from the
            // planned window so detention and ETA can be computed later.
            $table->timestampTz('actual_arrival_at')->nullable()->after('planned_at');
            $table->timestampTz('actual_departure_at')->nullable()->after('actual_arrival_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stops', function (Blueprint $table) {
            $table->dropColumn(['actual_arrival_at', 'actual_departure_at']);
        });
    }
};
