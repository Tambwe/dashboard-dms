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
        Schema::create('communes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // adm3_name
            $table->string('pcode')->unique(); // adm3_pcode
            $table->foreignId('territoire_id')->constrained('territoires')->onDelete('cascade');
            $table->foreignId('province_id')->constrained('provinces')->onDelete('cascade');
            $table->decimal('area_sqkm', 12, 2)->nullable();
            $table->decimal('center_lat', 10, 7)->nullable();
            $table->decimal('center_lon', 10, 7)->nullable();
            $table->json('geometry'); // Store GeoJSON geometry
            $table->json('properties')->nullable(); // Store additional properties
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communes');
    }
};
