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
        if (
            ! Schema::hasTable('sites')
            || ! Schema::hasTable('site_mouvements_population')
            || ! Schema::hasColumn('sites', 'menages')
            || ! Schema::hasColumn('sites', 'individus')
        ) {
            return;
        }

        // Copier les données démographiques existantes de chaque site 
        // vers la table site_mouvements_population comme recensement initial
        DB::statement("
            INSERT INTO site_mouvements_population (
                site_id,
                date_mouvement,
                type_mouvement,
                periode,
                menages,
                individus,
                f_0_5,
                f_6_17,
                f_18_59,
                f_60_plus,
                h_0_5,
                h_6_17,
                h_18_59,
                h_60_plus,
                source,
                round,
                created_at,
                updated_at
            )
            SELECT 
                id as site_id,
                COALESCE(date_mise_a_jour, CURDATE()) as date_mouvement,
                'recensement' as type_mouvement,
                DATE_FORMAT(COALESCE(date_mise_a_jour, CURDATE()), '%Y-%m') as periode,
                COALESCE(menages, 0) as menages,
                COALESCE(individus, 0) as individus,
                COALESCE(f_0_5, 0) as f_0_5,
                COALESCE(f_6_17, 0) as f_6_17,
                COALESCE(f_18_59, 0) as f_18_59,
                COALESCE(f_60_plus, 0) as f_60_plus,
                COALESCE(h_0_5, 0) as h_0_5,
                COALESCE(h_6_17, 0) as h_6_17,
                COALESCE(h_18_59, 0) as h_18_59,
                COALESCE(h_60_plus, 0) as h_60_plus,
                source,
                round,
                NOW() as created_at,
                NOW() as updated_at
            FROM sites
            WHERE individus IS NOT NULL AND individus > 0
        ");
        
        $count = DB::table('site_mouvements_population')->count();
        echo "Migration effectuée : {$count} mouvements créés.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les mouvements de type 'recensement' créés lors de la migration initiale
        DB::table('site_mouvements_population')
            ->where('type_mouvement', 'recensement')
            ->whereRaw('DATE(created_at) = DATE(updated_at)')
            ->delete();
    }
};
