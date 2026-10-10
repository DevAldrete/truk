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
        // Global reference data: never tenant-scoped, versioned so an issued
        // fiscal document can point at the catalog version in force.
        Schema::create('catalog_versions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('version', 40);
            $table->date('published_at')->nullable();
            $table->string('source', 255)->nullable();
            $table->timestamps();

            $table->unique(['name', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_versions');
    }
};
