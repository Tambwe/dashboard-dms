<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Insérer le cluster CCCM s'il n'existe pas déjà
        $existing = DB::table('clusters')->where('code', 'CCCM')->first();

        if (!$existing) {
            $clusterId = DB::table('clusters')->insertGetId([
                'code'        => 'CCCM',
                'name'        => 'Camp Coordination and Camp Management',
                'description' => 'Cluster CCCM – coordination des camps et gestion des camps',
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        } else {
            $clusterId = $existing->id;
        }

        // Associer toutes les organisations existantes au cluster CCCM
        $organisationIds = DB::table('organisations')->pluck('id');

        foreach ($organisationIds as $orgId) {
            $alreadyLinked = DB::table('cluster_organisation')
                ->where('cluster_id', $clusterId)
                ->where('organisation_id', $orgId)
                ->exists();

            if (!$alreadyLinked) {
                DB::table('cluster_organisation')->insert([
                    'cluster_id'      => $clusterId,
                    'organisation_id' => $orgId,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $cluster = DB::table('clusters')->where('code', 'CCCM')->first();
        if ($cluster) {
            DB::table('cluster_organisation')->where('cluster_id', $cluster->id)->delete();
            DB::table('clusters')->where('id', $cluster->id)->delete();
        }
    }
};
