<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\SiteMouvementPopulation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardStatsController extends Controller
{
    private function buildStatsFromMovements($mouvements): array
    {
        $f_0_5 = $mouvements->sum('f_0_5');
        $f_6_17 = $mouvements->sum('f_6_17');
        $f_18_59 = $mouvements->sum('f_18_59');
        $f_60_plus = $mouvements->sum('f_60_plus');
        $h_0_5 = $mouvements->sum('h_0_5');
        $h_6_17 = $mouvements->sum('h_6_17');
        $h_18_59 = $mouvements->sum('h_18_59');
        $h_60_plus = $mouvements->sum('h_60_plus');

        return [
            'total_pdi' => $mouvements->sum('individus'),
            'hommes' => $h_0_5 + $h_6_17 + $h_18_59 + $h_60_plus,
            'femmes' => $f_0_5 + $f_6_17 + $f_18_59 + $f_60_plus,
            'personnes_handicap' => 0,
            'menages' => $mouvements->sum('menages'),
            'enfants' => $f_0_5 + $h_0_5 + $f_6_17 + $h_6_17,
            'adultes' => $f_18_59 + $h_18_59,
            'personnes_agees' => $f_60_plus + $h_60_plus,
        ];
    }

    private function buildDeltas(array $currentStats, array $previousStats): array
    {
        $deltas = [];

        foreach ($currentStats as $key => $value) {
            $previous = $previousStats[$key] ?? 0;
            $deltaValue = $value - $previous;
            $deltaPercent = $previous != 0 ? round(($deltaValue / $previous) * 100, 1) : null;

            $deltas[$key] = [
                'value' => $deltaValue,
                'percent' => $deltaPercent,
            ];
        }

        return $deltas;
    }

    private function resolveSelectedPeriod(?string $period, ?Request $request = null): ?Carbon
    {
        if ($period && preg_match('/^\d{2}\/\d{4}$/', $period)) {
            return Carbon::createFromFormat('m/Y', $period)->startOfMonth();
        }

        if ($period && preg_match('/^\d{4}-\d{2}$/', $period)) {
            return Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        }

        $latestQuery = SiteMouvementPopulation::query()
            ->where('type_mouvement', 'recensement');

        if ($request) {
            $this->applySiteFiltersToMovementQuery($latestQuery, $request);
        }

        $latestDate = $latestQuery->max('date_mouvement');

        return $latestDate ? Carbon::parse($latestDate)->startOfMonth() : null;
    }

    private function applySiteFiltersToMovementQuery($query, Request $request)
    {
        if ($request->filled('province_id')) {
            $provinceId = (int) $request->province_id;
            $query->whereHas('site', function ($siteQuery) use ($provinceId) {
                $siteQuery->where(function ($q) use ($provinceId) {
                    $q->where('province', (string) $provinceId)
                        ->orWhereHas('commune.territoire.province', function ($subQuery) use ($provinceId) {
                            $subQuery->where('id', $provinceId);
                        });
                });
            });
        }

        if ($request->filled('territoire_id')) {
            $territoireId = (int) $request->territoire_id;
            $query->whereHas('site', function ($siteQuery) use ($territoireId) {
                $siteQuery->where(function ($q) use ($territoireId) {
                    $q->where('territoire', (string) $territoireId)
                        ->orWhereHas('commune.territoire', function ($subQuery) use ($territoireId) {
                            $subQuery->where('id', $territoireId);
                        });
                });
            });
        }

        if ($request->filled('commune_id')) {
            $query->whereHas('site', function ($siteQuery) use ($request) {
                $siteQuery->where('commune_id', $request->commune_id);
            });
        }

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->filled('coordinateur_id')) {
            $query->whereHas('site', function ($siteQuery) use ($request) {
                $siteQuery->where('coordinateur_id', $request->coordinateur_id);
            });
        }

        if ($request->filled('gestionnaire_id')) {
            $query->whereHas('site', function ($siteQuery) use ($request) {
                $siteQuery->where('gestionnaire_id', $request->gestionnaire_id);
            });
        }

        if ($request->filled('categorie_site_id')) {
            $query->whereHas('site', function ($siteQuery) use ($request) {
                $siteQuery->where('categorie_site_id', $request->categorie_site_id);
            });
        }

        return $query;
    }

    /**
     * Get dashboard statistics with filters
     */
    public function getStats(Request $request)
    {
        $selectedPeriod = $this->resolveSelectedPeriod($request->input('periode'), $request);
        if (!$selectedPeriod) {
            return response()->json([
                'stats' => [
                    'total_pdi' => 0,
                    'hommes' => 0,
                    'femmes' => 0,
                    'personnes_handicap' => 0,
                    'menages' => 0,
                    'enfants' => 0,
                    'adultes' => 0,
                    'personnes_agees' => 0,
                ],
                'age_distribution' => [
                    '0-5 ans' => 0,
                    '6-17 ans' => 0,
                    '18-59 ans' => 0,
                    '60+ ans' => 0,
                ],
                'gender_distribution' => [
                    'femmes' => 0,
                    'hommes' => 0,
                ],
                'periode' => null,
            ]);
        }

        $query = SiteMouvementPopulation::query()
            ->where('type_mouvement', 'recensement')
            ->whereYear('date_mouvement', (int) $selectedPeriod->format('Y'))
            ->whereMonth('date_mouvement', (int) $selectedPeriod->format('m'));

        $this->applySiteFiltersToMovementQuery($query, $request);

        $previousPeriod = $selectedPeriod->copy()->subMonthNoOverflow();
        $previousQuery = SiteMouvementPopulation::query()
            ->where('type_mouvement', 'recensement')
            ->whereYear('date_mouvement', (int) $previousPeriod->format('Y'))
            ->whereMonth('date_mouvement', (int) $previousPeriod->format('m'));

        $this->applySiteFiltersToMovementQuery($previousQuery, $request);

        $mouvements = $query->get();
        $previousMouvements = $previousQuery->get();

        $stats = $this->buildStatsFromMovements($mouvements);
        $previousStats = $this->buildStatsFromMovements($previousMouvements);
        $deltas = $this->buildDeltas($stats, $previousStats);

        return response()->json([
            'stats' => $stats,
            'age_distribution' => [
                '0-5 ans' => $mouvements->sum('f_0_5') + $mouvements->sum('h_0_5'),
                '6-17 ans' => $mouvements->sum('f_6_17') + $mouvements->sum('h_6_17'),
                '18-59 ans' => $mouvements->sum('f_18_59') + $mouvements->sum('h_18_59'),
                '60+ ans' => $mouvements->sum('f_60_plus') + $mouvements->sum('h_60_plus'),
            ],
            'gender_distribution' => [
                'femmes' => $stats['femmes'],
                'hommes' => $stats['hommes'],
            ],
            'deltas' => $deltas,
            'periode' => $selectedPeriod->format('m/Y'),
        ]);
    }

    /**
     * Get province distribution
     */
    public function getProvinceDistribution(Request $request)
    {
        $selectedPeriod = $this->resolveSelectedPeriod($request->input('periode'), $request);
        if (!$selectedPeriod) {
            return response()->json([
                'provinces' => [],
                'values' => [],
            ]);
        }

        $query = SiteMouvementPopulation::query()
            ->with('site:id,province')
            ->where('type_mouvement', 'recensement')
            ->whereYear('date_mouvement', (int) $selectedPeriod->format('Y'))
            ->whereMonth('date_mouvement', (int) $selectedPeriod->format('m'));

        $this->applySiteFiltersToMovementQuery($query, $request);

        $distribution = $query->get()
            ->groupBy(function ($mouvement) {
                return $mouvement->site->province ?? 'Non spécifié';
            })
            ->map(function ($items) {
                return $items->sum('individus');
            })
            ->toArray();

        return response()->json([
            'provinces' => array_keys($distribution),
            'values' => array_values($distribution),
        ]);
    }

    /**
     * Get monthly trends (entrées, sorties, naissances, décès)
     */
    public function getTrends(Request $request)
    {
        $startDate = Carbon::now()->subMonths(11)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        // Mouvements d'entrées
        $entrees = SiteMouvementPopulation::selectRaw('DATE_FORMAT(date_mouvement, "%Y-%m") as mois, SUM(individus) as total')
            ->where('type_mouvement', 'entree')
            ->whereBetween('date_mouvement', [$startDate, $endDate])
            ->groupBy('mois')
            ->orderBy('mois')
            ->pluck('total', 'mois')
            ->toArray();

        // Mouvements de sorties
        $sorties = SiteMouvementPopulation::selectRaw('DATE_FORMAT(date_mouvement, "%Y-%m") as mois, SUM(individus) as total')
            ->where('type_mouvement', 'sortie')
            ->whereBetween('date_mouvement', [$startDate, $endDate])
            ->groupBy('mois')
            ->orderBy('mois')
            ->pluck('total', 'mois')
            ->toArray();

        // Naissances
        $naissances = SiteMouvementPopulation::selectRaw('DATE_FORMAT(date_mouvement, "%Y-%m") as mois, SUM(individus) as total')
            ->where('type_mouvement', 'naissance')
            ->whereBetween('date_mouvement', [$startDate, $endDate])
            ->groupBy('mois')
            ->orderBy('mois')
            ->pluck('total', 'mois')
            ->toArray();

        // Décès
        $deces = SiteMouvementPopulation::selectRaw('DATE_FORMAT(date_mouvement, "%Y-%m") as mois, SUM(individus) as total')
            ->where('type_mouvement', 'deces')
            ->whereBetween('date_mouvement', [$startDate, $endDate])
            ->groupBy('mois')
            ->orderBy('mois')
            ->pluck('total', 'mois')
            ->toArray();

        // Créer un tableau avec tous les mois des 12 derniers mois
        $months = [];
        $entreesData = [];
        $sortiesData = [];
        $naissancesData = [];
        $decesData = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthKey = $date->format('Y-m');
            $months[] = $date->locale('fr')->isoFormat('MMM YYYY');
            
            $entreesData[] = $entrees[$monthKey] ?? 0;
            $sortiesData[] = $sorties[$monthKey] ?? 0;
            $naissancesData[] = $naissances[$monthKey] ?? 0;
            $decesData[] = $deces[$monthKey] ?? 0;
        }

        return response()->json([
            'months' => $months,
            'entrees' => $entreesData,
            'sorties' => $sortiesData,
            'naissances' => $naissancesData,
            'deces' => $decesData,
        ]);
    }

    /**
     * Get sites for map display
     */
    public function getMapSites(Request $request)
    {
        $query = Site::with(['organisation', 'typeSite'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        // Appliquer les filtres
        if ($request->filled('province_id')) {
            $query->where('province', $request->province_id);
        }

        if ($request->filled('territoire_id')) {
            $query->where('territoire', $request->territoire_id);
        }

        if ($request->filled('commune_id')) {
            $query->where('commune_id', $request->commune_id);
        }

        $sites = $query->get()->map(function ($site) {
            return [
                'id' => $site->id,
                'nom' => $site->nom,
                'code' => $site->code_site,
                'latitude' => $site->latitude,
                'longitude' => $site->longitude,
                'individus' => $site->individus,
                'menages' => $site->menages,
                'organisation' => $site->organisation->nom ?? '',
                'type' => $site->typeSite->nom ?? '',
                'province' => $site->province,
                'territoire' => $site->territoire,
            ];
        });

        return response()->json([
            'sites' => $sites,
            'count' => $sites->count(),
        ]);
    }
}
