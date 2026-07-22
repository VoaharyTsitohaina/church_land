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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            // Identification
            $table->string('reference')->unique();
            $table->string('name');
            $table->foreignId('property_type_id')->constrained();
            
            // Localisation
            $table->foreignId('church_id')->constrained();
            $table->string('region')->nullable();
            $table->string('admin_district')->nullable(); //district administratif
            $table->string('commune')->nullable();
            $table->string('fokontany')->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Information foncière
            $table->decimal('area', 12, 2)->nullable(); // superficie en m²
            $table->string('land_title_number')->nullable(); // numéro de titre foncier
            $table->string('cadastral_number')->nullable();
            $table->string('legal_status')->nullable();
            $table->string('acquisition_mode')->nullable();
            $table->date('acquisition_date')->nullable();
            $table->decimal('estimated_value', 15, 2)->nullable(); // valeur estimée en Ariary

            // Description
            $table->string('current_value')->nullable();
            $table->text('observations')->nullable();
            $table->text('history')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
