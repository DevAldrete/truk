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
        Schema::create('sat_postal_codes', function (Blueprint $table) {
            $table->id();
            $table->string('postal_code', 5);
            $table->string('state', 120);
            $table->string('municipality', 160);
            $table->string('locality', 160)->nullable();
            $table->string('version', 40);
            $table->timestamps();

            $table->unique(['version', 'postal_code', 'municipality']);
            $table->index('postal_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sat_postal_codes');
    }
};
