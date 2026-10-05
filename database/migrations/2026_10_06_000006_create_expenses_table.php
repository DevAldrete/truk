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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stop_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40);
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3)->default('MXN');
            $table->timestampTz('incurred_at');
            $table->string('vendor', 160)->nullable();
            $table->text('notes')->nullable();

            // Fuel is a first-class cost: volume, unit price, odometer, and tank
            // make km/L and fuel surcharges computable later.
            $table->unsignedBigInteger('liters_ml')->nullable();
            $table->unsignedBigInteger('price_per_liter_minor')->nullable();
            $table->unsignedBigInteger('odometer_meters')->nullable();
            $table->string('tank', 40)->nullable();

            $table->string('receipt_path')->nullable();
            $table->uuid('idempotency_key');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['team_id', 'idempotency_key']);
            $table->index(['team_id', 'trip_id']);
            $table->index(['team_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
