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
        Schema::create('households', function (Blueprint $table) {
            $table->id();
            
            // Relation avec le site
            $table->foreignId('site_id')->constrained('sites')->onDelete('cascade');
            
            // Niveau d'enregistrement (1 ou 2)
            $table->enum('niveau_enregistrement', ['1', '2'])->default('1');
            
            // Numéro unique du ménage
            $table->string('numero_menage')->unique();
            
            // INFORMATIONS DU CHEF DE MÉNAGE
            $table->string('chef_nom');
            $table->string('chef_postnom')->nullable();
            $table->string('chef_prenom')->nullable();
            $table->enum('chef_sexe', ['M', 'F']);
            $table->date('chef_date_naissance')->nullable();
            $table->integer('chef_age')->nullable();
            $table->string('chef_lieu_naissance')->nullable();
            $table->string('chef_nationalite')->default('Congolaise');
            $table->string('chef_etat_civil')->nullable();
            
            // Contact
            $table->string('chef_telephone')->nullable();
            $table->string('chef_email')->nullable();
            
            // Documents
            $table->string('chef_type_document')->nullable();
            $table->string('chef_numero_document')->nullable();
            
            // Biométrie
            $table->text('chef_photo')->nullable(); // Stockage base64 ou chemin
            $table->text('chef_empreinte')->nullable(); // Données d'empreinte digitale
            
            // Province et territoire d'origine
            $table->foreignId('province_origine_id')->nullable()->constrained('provinces')->onDelete('set null');
            $table->foreignId('territoire_origine_id')->nullable()->constrained('territoires')->onDelete('set null');
            $table->string('commune_origine')->nullable();
            $table->string('village_origine')->nullable();
            
            // Raison du déplacement
            $table->text('raison_deplacement')->nullable();
            $table->date('date_arrivee_site')->nullable();
            
            // NIVEAU 1 - Nombre de personnes seulement
            $table->integer('nombre_hommes')->default(0);
            $table->integer('nombre_femmes')->default(0);
            $table->integer('nombre_garcons')->default(0); // moins de 18 ans
            $table->integer('nombre_filles')->default(0); // moins de 18 ans
            $table->integer('nombre_total_personnes')->default(0);
            
            // Vulnérabilités
            $table->integer('nombre_femmes_enceintes')->default(0);
            $table->integer('nombre_femmes_allaitantes')->default(0);
            $table->integer('nombre_personnes_handicapees')->default(0);
            $table->integer('nombre_personnes_agees')->default(0); // 60 ans et plus
            $table->integer('nombre_enfants_orphelins')->default(0);
            $table->integer('nombre_enfants_separes')->default(0);
            $table->integer('nombre_malades_chroniques')->default(0);
            
            // Abri et conditions de vie
            $table->string('type_abri')->nullable();
            $table->boolean('acces_eau_potable')->default(false);
            $table->boolean('acces_latrines')->default(false);
            $table->boolean('acces_electricite')->default(false);
            
            // Assistance reçue
            $table->boolean('recu_kits_nfi')->default(false);
            $table->boolean('recu_assistance_alimentaire')->default(false);
            $table->boolean('recu_soins_sante')->default(false);
            
            // Statut et suivi
            $table->string('statut')->default('actif');
            $table->text('observations')->nullable();
            
            // Utilisateur qui a enregistré
            $table->foreignId('enregistre_par')->constrained('users')->onDelete('cascade');
            $table->timestamp('date_enregistrement')->useCurrent();
            
            // Utilisateur qui a vérifié (si applicable)
            $table->foreignId('verifie_par')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('date_verification')->nullable();
            
            $table->timestamps();
            
            // Index pour recherche rapide
            $table->index('site_id');
            $table->index('numero_menage');
            $table->index('chef_nom');
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('households');
    }
};
