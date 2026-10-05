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
        Schema::create('proofs_of_delivery', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_attempt_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stop_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient_name', 160)->nullable();
            $table->string('signature_path')->nullable();
            $table->json('photos')->nullable();
            $table->json('document_paths')->nullable();
            $table->boolean('consent')->default(false);
            $table->text('notes')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestampTz('captured_at');
            $table->uuid('idempotency_key');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Evidence is append-only: the key makes a retried submit return
            // the existing record instead of a second correction.
            $table->unique(['team_id', 'idempotency_key']);
            $table->index(['team_id', 'stop_id']);
            $table->index(['team_id', 'shipment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proofs_of_delivery');
    }
};
