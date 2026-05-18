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
        Schema::create('household_members', function (Blueprint $table) {
            $table->id();
            
            // Relation avec le ménage
            $table->foreignId('household_id')->constrained('households')->onDelete('cascade');
            
            // Informations personnelles
            $table->string('nom');
            $table->string('postnom')->nullable();
            $table->string('prenom')->nullable();
            $table->enum('sexe', ['M', 'F']);
            $table->date('date_naissance')->nullable();
            $table->integer('age')->nullable();
            $table->string('lieu_naissance')->nullable();
            $table->string('nationalite')->default('Congolaise');
            
            // Relation avec le chef de ménage
            $table->string('lien_avec_chef');
            
            // État civil
            $table->string('etat_civil')->nullable();
            
            // Documents
            $table->string('type_document')->nullable();
            $table->string('numero_document')->nullable();
            
            // Biométrie
            $table->text('photo')->nullable();
            $table->text('empreinte')->nullable();
            
            // Éducation et profession
            $table->string('niveau_education')->nullable();
            $table->boolean('scolarise_actuellement')->default(false);
            $table->string('profession')->nullable();
            
            // Santé
            $table->boolean('handicap')->default(false);
            $table->string('type_handicap')->nullable();
            $table->boolean('maladie_chronique')->default(false);
            $table->string('type_maladie')->nullable();
            
            // Cas spéciaux
            $table->boolean('femme_enceinte')->default(false);
            $table->boolean('femme_allaitante')->default(false);
            $table->boolean('enfant_orphelin')->default(false);
            $table->boolean('enfant_separe')->default(false);
            $table->boolean('personne_agee')->default(false); // 60 ans et plus
            
            // Contact
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            
            // Observations
            $table->text('observations')->nullable();
            
            // Statut
            $table->string('statut')->default('actif');
            
            $table->timestamps();
            
            // Index
            $table->index('household_id');
            $table->index(['nom', 'sexe']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('household_members');
    }
};
