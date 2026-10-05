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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->string('number', 30);
            $table->string('status', 20)->default('draft');
            $table->char('currency', 3)->default('MXN');
            $table->timestampTz('requested_pickup_at')->nullable();
            $table->timestampTz('requested_delivery_at')->nullable();
            $table->text('notes')->nullable();

            // Customer details are frozen on the order so a later edit to the
            // party never rewrites commercial history.
            $table->string('customer_name', 160)->nullable();
            $table->string('customer_rfc', 13)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status']);
            $table->index(['team_id', 'created_at']);
        });

        // A deleted order must not reserve its number forever, so uniqueness is
        // enforced with a partial index while the record is live.
        DB::statement(
            'CREATE UNIQUE INDEX orders_team_id_number_unique ON orders (team_id, number) WHERE deleted_at IS NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
