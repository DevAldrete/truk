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
        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description', 200);
            $table->unsignedInteger('quantity');
            $table->string('unit', 20)->default('piece');
            $table->unsignedBigInteger('weight_grams')->default(0);
            $table->unsignedBigInteger('volume_cm3')->default(0);
            $table->boolean('hazmat')->default(false);
            $table->timestamps();

            $table->index(['team_id', 'shipment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_items');
    }
};
