<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== STATISTIQUES DES MOUVEMENTS DE POPULATION ===\n\n";

$stats = DB::table('site_mouvements_population')
    ->selectRaw('statut, COUNT(*) as count')
    ->groupBy('statut')
    ->get();

echo "Répartition par statut:\n";
foreach ($stats as $stat) {
    echo "  - {$stat->statut}: {$stat->count} mouvements\n";
}

echo "\nTotal: " . DB::table('site_mouvements_population')->count() . " mouvements\n";
