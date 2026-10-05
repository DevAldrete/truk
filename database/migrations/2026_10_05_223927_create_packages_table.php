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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('status', 20)->default('created');
            $table->unsignedBigInteger('weight_grams')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'shipment_id']);
        });

        // A live barcode/SSCC is unique per team; a deleted one is released.
        DB::statement(
            'CREATE UNIQUE INDEX packages_team_id_code_unique ON packages (team_id, code) WHERE deleted_at IS NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
