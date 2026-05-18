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
        Schema::table('sites', function (Blueprint $table) {
            // Organisation qui gère ce site
            $table->foreignId('organisation_id')->nullable()->after('id')->constrained('organisations')->onDelete('set null');
            
            // Photos du site (JSON array des chemins)
            $table->json('photos')->nullable()->after('latitude');
            
            // Données GeoJSON (pour la géolocalisation avancée)
            $table->json('geojson_data')->nullable()->after('photos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropForeign(['organisation_id']);
            $table->dropColumn(['organisation_id', 'photos', 'geojson_data']);
        });
    }
};
