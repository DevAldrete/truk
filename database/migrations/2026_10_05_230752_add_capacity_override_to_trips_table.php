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
        Schema::table('trips', function (Blueprint $table) {
            // A dispatcher with the override permission may dispatch an
            // over-capacity trip; who and why is recorded, never silent.
            $table->text('capacity_override_reason')->nullable()->after('notes');
            $table->foreignId('capacity_overridden_by')->nullable()->after('capacity_override_reason')->constrained('users')->nullOnDelete();
            $table->timestampTz('capacity_overridden_at')->nullable()->after('capacity_overridden_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('capacity_overridden_by');
            $table->dropColumn(['capacity_override_reason', 'capacity_overridden_at']);
        });
    }
};
