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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30);
            $table->foreignId('customer_party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('draft');
            $table->string('currency', 3)->default('MXN');
            $table->integer('subtotal_minor')->default(0);
            $table->integer('tax_minor')->default(0);
            $table->integer('total_minor')->default(0);
            $table->string('cfdi_uuid', 40)->nullable();
            $table->timestampTz('issued_at')->nullable();
            $table->timestampTz('due_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status']);
        });

        // A deleted invoice must not reserve its number forever.
        DB::statement(
            'CREATE UNIQUE INDEX invoices_team_id_number_unique ON invoices (team_id, number) WHERE deleted_at IS NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
