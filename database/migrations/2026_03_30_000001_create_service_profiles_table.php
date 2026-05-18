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
        Schema::create('service_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->onDelete('cascade');
            $table->date('date_collecte');
            $table->foreignId('collecteur_id')->constrained('users')->onDelete('cascade');
            
            // SANTÉ
            $table->boolean('sante_disponible')->default(false);
            $table->integer('sante_structures_fonctionnelles')->nullable();
            $table->integer('sante_personnel_medical')->nullable();
            $table->text('sante_services_offerts')->nullable(); // JSON: consultation, vaccination, etc.
            $table->integer('sante_consultations_mois')->nullable();
            $table->text('sante_observations')->nullable();
            
            // ÉDUCATION
            $table->boolean('education_disponible')->default(false);
            $table->integer('education_ecoles_fonctionnelles')->nullable();
            $table->integer('education_enseignants')->nullable();
            $table->integer('education_eleves_inscrits')->nullable();
            $table->integer('education_salles_classe')->nullable();
            $table->text('education_niveaux_offerts')->nullable(); // JSON: primaire, secondaire, etc.
            $table->text('education_observations')->nullable();
            
            // WASH (Water, Sanitation and Hygiene)
            $table->boolean('wash_disponible')->default(false);
            $table->integer('wash_points_eau')->nullable();
            $table->decimal('wash_litres_par_personne', 8, 2)->nullable();
            $table->integer('wash_latrines')->nullable();
            $table->integer('wash_douches')->nullable();
            $table->boolean('wash_gestion_dechets')->default(false);
            $table->text('wash_observations')->nullable();
            
            // ENVIRONNEMENT
            $table->boolean('environnement_disponible')->default(false);
            $table->boolean('environnement_gestion_dechets')->default(false);
            $table->boolean('environnement_drainage')->default(false);
            $table->boolean('environnement_espaces_verts')->default(false);
            $table->text('environnement_risques')->nullable(); // JSON: inondation, éboulement, etc.
            $table->text('environnement_observations')->nullable();
            
            // ABRI ET AME (Articles Ménagers Essentiels)
            $table->boolean('abri_ame_disponible')->default(false);
            $table->integer('abri_logements_fonctionnels')->nullable();
            $table->text('abri_types')->nullable(); // JSON: tente, habitation durable, etc.
            $table->integer('abri_menages_ame')->nullable(); // Ménages ayant reçu AME
            $table->text('abri_ame_distribues')->nullable(); // JSON: couvertures, ustensiles, etc.
            $table->text('abri_observations')->nullable();
            
            // GESTION ET COORDINATION DU SITE
            $table->boolean('gestion_disponible')->default(false);
            $table->boolean('gestion_comite_site')->default(false);
            $table->integer('gestion_membres_comite')->nullable();
            $table->boolean('gestion_mecanisme_plainte')->default(false);
            $table->integer('gestion_reunions_mois')->nullable();
            $table->text('gestion_partenaires')->nullable(); // JSON: liste des partenaires actifs
            $table->text('gestion_observations')->nullable();
            
            // MÉTADONNÉES
            $table->enum('statut', ['brouillon', 'soumis', 'valide', 'rejete'])->default('brouillon');
            $table->text('notes_generales')->nullable();
            $table->timestamps();
            
            // Index pour performance
            $table->index(['site_id', 'date_collecte']);
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_profiles');
    }
};
