<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $newClusters = [
        [
            'code'        => 'ABRI',
            'name'        => 'Abri',
            'description' => "Cluster Abri - assistance en matiere d'abris d'urgence et transitoires",
        ],
        [
            'code'        => 'AME',
            'name'        => 'AME',
            'description' => 'Cluster AME - Articles Menagers Essentiels',
        ],
        [
            'code'        => 'LTP',
            'name'        => 'Logement Terre Et Propriete',
            'description' => 'Cluster LTP - Logement, Terre et Propriete',
        ],
    ];

    public function up(): void
    {
        $organisationIds = DB::table('organisations')->pluck('id');

        foreach ($this->newClusters as $clusterData) {
            $existing = DB::table('clusters')->where('code', $clusterData['code'])->first();

            if (!$existing) {
                $clusterId = DB::table('clusters')->insertGetId([
                    'code'        => $clusterData['code'],
                    'name'        => $clusterData['name'],
                    'description' => $clusterData['description'],
                    'is_active'   => true,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            } else {
                $clusterId = $existing->id;
            }

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
    }

    public function down(): void
    {
        foreach ($this->newClusters as $clusterData) {
            $cluster = DB::table('clusters')->where('code', $clusterData['code'])->first();
            if ($cluster) {
                DB::table('cluster_organisation')->where('cluster_id', $cluster->id)->delete();
                DB::table('clusters')->where('id', $cluster->id)->delete();
            }
        }
    }
};
