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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 30);
            $table->string('status', 20)->default('planned');
            $table->char('currency', 3)->default('MXN');

            $table->foreignId('pickup_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('delivery_location_id')->nullable()->constrained('locations')->nullOnDelete();

            // Address snapshots, frozen when the shipment is planned.
            $table->json('pickup_snapshot')->nullable();
            $table->json('delivery_snapshot')->nullable();
            $table->string('customer_name', 160)->nullable();

            // Derived totals used by the capacity guard. Weight in grams,
            // volume in cm³, per the project's integer-unit convention.
            $table->unsignedBigInteger('weight_grams')->default(0);
            $table->unsignedBigInteger('volume_cm3')->default(0);
            $table->unsignedInteger('pieces')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status']);
            $table->index(['team_id', 'order_id']);
        });

        // A deleted shipment must not reserve its number forever.
        DB::statement(
            'CREATE UNIQUE INDEX shipments_team_id_number_unique ON shipments (team_id, number) WHERE deleted_at IS NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
