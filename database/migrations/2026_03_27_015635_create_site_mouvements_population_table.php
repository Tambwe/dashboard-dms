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
        Schema::create('site_mouvements_population', function (Blueprint $table) {
            $table->id();
            
            // Référence au site
            $table->foreignId('site_id')
                ->constrained('sites')
                ->onDelete('cascade');
            
            // Date et type de mouvement
            $table->date('date_mouvement')->index();
            $table->enum('type_mouvement', ['arrivee', 'depart', 'ajustement', 'recensement'])
                ->comment('Type de mouvement: arrivee (nouvelles personnes), depart (personnes parties), ajustement (correction), recensement (comptage complet)');
            
            // Période de référence (optionnel, pour les rapports mensuels par exemple)
            $table->string('periode')->nullable()->comment('Ex: 2026-03, Q1-2026');
            
            // Statistiques générales (valeurs peuvent être positives ou négatives sauf pour recensement)
            $table->integer('menages')->default(0)->comment('Nombre de ménages (positif=arrivée, négatif=départ)');
            $table->integer('individus')->default(0)->comment('Nombre d\'individus (positif=arrivée, négatif=départ)');
            
            // Démographie - Femmes
            $table->integer('f_0_5')->default(0)->comment('Femmes 0-5 ans');
            $table->integer('f_6_17')->default(0)->comment('Femmes 6-17 ans');
            $table->integer('f_18_59')->default(0)->comment('Femmes 18-59 ans');
            $table->integer('f_60_plus')->default(0)->comment('Femmes 60+ ans');
            
            // Démographie - Hommes
            $table->integer('h_0_5')->default(0)->comment('Hommes 0-5 ans');
            $table->integer('h_6_17')->default(0)->comment('Hommes 6-17 ans');
            $table->integer('h_18_59')->default(0)->comment('Hommes 18-59 ans');
            $table->integer('h_60_plus')->default(0)->comment('Hommes 60+ ans');
            
            // Métadonnées
            $table->string('raison')->nullable()->comment('Raison du mouvement (ex: conflit, retour, relocation)');
            $table->text('description')->nullable()->comment('Description détaillée du mouvement');
            $table->string('source')->nullable()->comment('Source de l\'information');
            $table->string('round')->nullable()->comment('Round de collecte des données');
            
            // Audit
            $table->foreignId('created_by')->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('Utilisateur ayant enregistré le mouvement');
            
            $table->timestamps();
            
            // Index composites pour améliorer les performances de requêtes
            $table->index(['site_id', 'date_mouvement']);
            $table->index(['date_mouvement', 'type_mouvement']);
            $table->index('periode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_mouvements_population');
    }
};
