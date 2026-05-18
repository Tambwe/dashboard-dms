<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('raison_mouvements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categorie_mouvement_id')
                ->constrained('categorie_mouvements')
                ->onDelete('cascade')
                ->comment('Catégorie de mouvement (nouvelle entree, sortie)');
            $table->string('name')->comment('Nom de la raison');
            $table->string('code')->nullable()->comment('Code court de la raison');
            $table->text('description')->nullable();
            $table->timestamps();
            
            // Index pour les recherches
            $table->index(['categorie_mouvement_id', 'name']);
        });

        // Insérer les raisons par défaut
        $entreeId = DB::table('categorie_mouvements')->where('name', 'nouvelle entree')->value('id');
        $sortieId = DB::table('categorie_mouvements')->where('name', 'sortie')->value('id');

        // Raisons pour "nouvelle entree"
        DB::table('raison_mouvements')->insert([
            [
                'categorie_mouvement_id' => $entreeId,
                'name' => 'insecurite zone d\'origine',
                'code' => 'INSEC_ORIG',
                'description' => 'Déplacement dû à l\'insécurité dans la zone d\'origine',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'categorie_mouvement_id' => $entreeId,
                'name' => 'fusion avec un autre site',
                'code' => 'FUSION',
                'description' => 'Fusion de plusieurs sites',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'categorie_mouvement_id' => $entreeId,
                'name' => 'deplacement dans un autre site',
                'code' => 'DEPL_SITE',
                'description' => 'Déplacement vers un autre site PDI',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Raisons pour "sortie" (retour)
        DB::table('raison_mouvements')->insert([
            [
                'categorie_mouvement_id' => $sortieId,
                'name' => 'fermeture d\'un site',
                'code' => 'FERM_SITE',
                'description' => 'Fermeture officielle du site',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'categorie_mouvement_id' => $sortieId,
                'name' => 'retour volontaire',
                'code' => 'RET_VOL',
                'description' => 'Retour volontaire dans la zone d\'origine',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'categorie_mouvement_id' => $sortieId,
                'name' => 'retour force',
                'code' => 'RET_FORCE',
                'description' => 'Retour forcé dans la zone d\'origine',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'categorie_mouvement_id' => $sortieId,
                'name' => 'demantelement du site',
                'code' => 'DEMANT',
                'description' => 'Démantèlement du site par les autorités',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raison_mouvements');
    }
};
