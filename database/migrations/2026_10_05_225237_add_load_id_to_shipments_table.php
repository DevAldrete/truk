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
        Schema::table('shipments', function (Blueprint $table) {
            // A load groups shipments for planning. A shipment belongs to at
            // most one load; shipments still reach trips through stops.
            $table->foreignId('load_id')->nullable()->after('order_id')->constrained()->nullOnDelete();

            $table->index(['team_id', 'load_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('load_id');
        });
    }
};
