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
        // Fiscal-grade master data, nullable until the fiscal phase collects it.
        Schema::table('parties', function (Blueprint $table) {
            $table->string('tax_regime', 10)->nullable()->after('rfc');
            $table->string('cfdi_use', 10)->nullable()->after('tax_regime');
            $table->string('tax_zip_code', 10)->nullable()->after('cfdi_use');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->string('rfc', 13)->nullable()->after('party_id');
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->string('curp', 18)->nullable()->after('license_number');
            $table->string('license_type', 40)->nullable()->after('curp');
            $table->date('medical_exam_expires_at')->nullable()->after('license_expires_at');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->nullable()->after('plate');
            $table->unsignedBigInteger('tare_weight_grams')->nullable()->after('max_volume_cm3');
            $table->unsignedTinyInteger('axles')->nullable()->after('tare_weight_grams');
            $table->string('permit_number', 60)->nullable()->after('axles');
            $table->date('insurance_expires_at')->nullable()->after('permit_number');
            $table->string('gps_device_id', 60)->nullable()->after('insurance_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->dropColumn(['tax_regime', 'cfdi_use', 'tax_zip_code']);
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('rfc');
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['curp', 'license_type', 'medical_exam_expires_at']);
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['year', 'tare_weight_grams', 'axles', 'permit_number', 'insurance_expires_at', 'gps_device_id']);
        });
    }
};
