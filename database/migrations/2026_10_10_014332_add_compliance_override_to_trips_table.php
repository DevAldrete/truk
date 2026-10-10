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
            // A permissioned, audited override of the compliance gate.
            $table->text('compliance_override_reason')->nullable()->after('capacity_overridden_at');
            $table->foreignId('compliance_overridden_by')->nullable()->after('compliance_override_reason')->constrained('users')->nullOnDelete();
            $table->timestampTz('compliance_overridden_at')->nullable()->after('compliance_overridden_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('compliance_overridden_by');
            $table->dropColumn(['compliance_override_reason', 'compliance_overridden_at']);
        });
    }
};
