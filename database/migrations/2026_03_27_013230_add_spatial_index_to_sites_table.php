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
            // Modifier les colonnes pour ajouter des commentaires indiquant qu'elles contiennent des données géographiques
            $table->decimal('longitude', 11, 8)->nullable()->comment('Coordonnée géographique - Longitude (WGS84)')->change();
            $table->decimal('latitude', 10, 8)->nullable()->comment('Coordonnée géographique - Latitude (WGS84)')->change();
            
            // Ajouter un index composite pour améliorer les requêtes géographiques
            $table->index(['longitude', 'latitude'], 'sites_coordinates_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropIndex('sites_coordinates_index');
            
            // Retirer les commentaires
            $table->decimal('longitude', 11, 8)->nullable()->change();
            $table->decimal('latitude', 10, 8)->nullable()->change();
        });
    }
};
