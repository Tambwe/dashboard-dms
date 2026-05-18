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
        Schema::table('site_mouvements_population', function (Blueprint $table) {
            // Ajouter la clé étrangère vers raison_mouvements
            $table->foreignId('raison_mouvement_id')
                ->nullable()
                ->after('type_mouvement')
                ->constrained('raison_mouvements')
                ->onDelete('set null')
                ->comment('Raison spécifique du mouvement');
        });

        // Mettre à jour tous les mouvements existants avec la raison "insecurite zone d'origine"
        $raisonId = DB::table('raison_mouvements')
            ->where('name', 'insecurite zone d\'origine')
            ->value('id');

        if ($raisonId) {
            DB::table('site_mouvements_population')
                ->update(['raison_mouvement_id' => $raisonId]);
            
            echo "Mise à jour effectuée : tous les mouvements existants ont été assignés à 'insecurite zone d'origine'.\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_mouvements_population', function (Blueprint $table) {
            $table->dropForeign(['raison_mouvement_id']);
            $table->dropColumn('raison_mouvement_id');
        });
    }
};
