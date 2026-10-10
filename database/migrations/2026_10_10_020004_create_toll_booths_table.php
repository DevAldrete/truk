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
        Schema::create('toll_booths', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('highway', 160)->nullable();
            $table->string('state', 120)->nullable();
            $table->string('direction', 40)->nullable();
            $table->string('version', 40);
            $table->timestamps();

            $table->unique(['version', 'name', 'direction']);
            $table->index('state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('toll_booths');
    }
};
