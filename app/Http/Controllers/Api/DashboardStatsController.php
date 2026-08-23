<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\SiteMouvementPopulation;
use App\Models\Commune;
use App\Models\Province;
use App\Models\Territoire;
use App\Services\SitePopulationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardStatsController extends Controller
{
    private array $movementStatFields = [
        'menages',
        'individus',
        'f_0_5',
        'f_6_17',
        'f_18_59',
        'f_60_plus',
        'h_0_5',
        'h_6_17',
        'h_18_59',
        'h_60_plus',
    ];

    private function movementPopulationTableExists(): bool
    {
        return Schema::hasTable((new SiteMouvementPopulation())->getTable());
    }

    private function applySiteOpenFilter($query)
    {
        return $query->whereNull('date_fermeture');
    }

    private function applyActiveSiteFilterToMovementQuery($query)
    {
        return $query->whereHas('site', function ($siteQuery) {
            $siteQuery->where(function ($q) {
                $q->whereNull('date_fermeture')
                    ->orWhereColumn('date_fermeture', '>', 'site_mouvements_population.date_mouvement');
            });
        });
    }

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

    private function normalizeMovementsForClosure($mouvements, Carbon $periodEnd)
    {
        return $mouvements->map(function ($mouvement) use ($periodEnd) {
            $closureDate = $mouvement->site?->date_fermeture;

            if ($closureDate && Carbon::parse($closureDate)->lte($periodEnd)) {
                foreach ($this->movementStatFields as $field) {
                    $mouvement->{$field} = 0;
                }
            }

            return $mouvement;
        });
    }

    private function resolveDashboardPeriod(Carbon $selectedPeriodDate, Request $request): array
    {
        if (!$this->movementPopulationTableExists()) {
            return [$selectedPeriodDate, false, 'Aucune donnée disponible : le registre des mouvements de population n’est pas configuré.'];
        }

        $selectedQuery = SiteMouvementPopulation::query()
            ->where('statut', 'valide')
            ->whereYear('date_mouvement', (int) $selectedPeriodDate->format('Y'))
            ->whereMonth('date_mouvement', (int) $selectedPeriodDate->format('m'));

        $this->applySiteFiltersToMovementQuery($selectedQuery, $request);

        if ($selectedQuery->exists()) {
            return [$selectedPeriodDate, false, null];
        }

        $latestQuery = SiteMouvementPopulation::query()
            ->where('statut', 'valide')
            ->whereDate('date_mouvement', '<=', $selectedPeriodDate->copy()->endOfMonth()->toDateString());

        $this->applySiteFiltersToMovementQuery($latestQuery, $request);

        $latestDate = $latestQuery->max('date_mouvement');
        $latestPeriod = $latestDate ? Carbon::parse($latestDate)->startOfMonth() : null;

        if ($latestPeriod && !$latestPeriod->equalTo($selectedPeriodDate)) {
            return [$latestPeriod, true, sprintf(
                'Aucune donnée disponible pour la période sélectionnée. Données considérées: %s.',
                $latestPeriod->format('m/Y')
            )];
        }

        return [$selectedPeriodDate, false, sprintf(
            'Aucune donnée disponible pour la période sélectionnée. Données considérées: %s.',
            $selectedPeriodDate->format('m/Y')
        )];
    }

    private function resolveSelectedPeriod(?string $period, ?Request $request = null): ?Carbon
    {
        if (!$this->movementPopulationTableExists()) {
            return null;
        }

        $currentPeriod = now()->startOfMonth();

        if ($period && preg_match('/^\d{2}\/\d{4}$/', $period)) {
            $parsed = Carbon::createFromFormat('m/Y', $period)->startOfMonth();
            return $parsed->gt($currentPeriod) ? $currentPeriod : $parsed;
        }

        if ($period && preg_match('/^\d{4}-\d{2}$/', $period)) {
            $parsed = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
            return $parsed->gt($currentPeriod) ? $currentPeriod : $parsed;
        }

        $latestQuery = SiteMouvementPopulation::query()
            ->where('statut', 'valide');

        $this->applyActiveSiteFilterToMovementQuery($latestQuery);

        if ($request) {
            $this->applySiteFiltersToMovementQuery($latestQuery, $request);
        }

        $latestDate = $latestQuery->max('date_mouvement');

        return $latestDate ? Carbon::parse($latestDate)->startOfMonth() : null;
    }

    private function findLatestAvailablePeriodUpTo(Carbon $maxPeriodDate, ?Request $request = null): ?Carbon
    {
        if (!$this->movementPopulationTableExists()) {
            return null;
        }

        $query = SiteMouvementPopulation::query()
            ->where('statut', 'valide')
            ->whereDate('date_mouvement', '<=', $maxPeriodDate->copy()->endOfMonth()->toDateString());

        if ($request) {
            $this->applySiteFiltersToMovementQuery($query, $request);
        } else {
            $this->applyActiveSiteFilterToMovementQuery($query);
        }

        $latestDate = $query->max('date_mouvement');

        return $latestDate ? Carbon::parse($latestDate)->startOfMonth() : null;
    }

    private function resolveComparisonPeriod(Carbon $consideredPeriod, Request $request): ?Carbon
    {
        return $this->findLatestAvailablePeriodUpTo($consideredPeriod->copy()->subMonthNoOverflow(), $request);
    }

    private function applySiteFiltersToMovementQuery($query, Request $request)
    {
        $this->applyActiveSiteFilterToMovementQuery($query);

        if ($request->filled('province_id')) {
            $provinceId = (int) $request->province_id;
            $province = Province::query()->select('id', 'name', 'pcode')->find($provinceId);
            $query->whereHas('site', function ($siteQuery) use ($provinceId, $province) {
                $siteQuery->where(function ($q) use ($provinceId, $province) {
                    if ($province) {
                        $q->whereRaw('LOWER(TRIM(province)) = ?', [mb_strtolower(trim($province->name))]);
                        if ($province->pcode) {
                            $q->orWhere('code_province', $province->pcode);
                        }
                        $q->orWhereHas('commune.territoire.province', function ($subQuery) use ($provinceId) {
                            $subQuery->where('provinces.id', $provinceId);
                        });
                    } else {
                        $q->whereHas('commune.territoire.province', function ($subQuery) use ($provinceId) {
                            $subQuery->where('provinces.id', $provinceId);
                        });
                    }
                });
            });
        }

        if ($request->filled('territoire_id')) {
            $territoireId = (int) $request->territoire_id;
            $territoire = Territoire::query()->select('id', 'name', 'pcode')->find($territoireId);
            $query->whereHas('site', function ($siteQuery) use ($territoireId, $territoire) {
                $siteQuery->where(function ($q) use ($territoireId, $territoire) {
                    if ($territoire) {
                        $q->whereRaw('LOWER(TRIM(territoire)) = ?', [mb_strtolower(trim($territoire->name))]);
                        if ($territoire->pcode) {
                            $q->orWhere('code_territoire', $territoire->pcode);
                        }
                        $q->orWhereHas('commune.territoire', function ($subQuery) use ($territoireId) {
                            $subQuery->where('territoires.id', $territoireId);
                        });
                    } else {
                        $q->whereHas('commune.territoire', function ($subQuery) use ($territoireId) {
                            $subQuery->where('territoires.id', $territoireId);
                        });
                    }
                });
            });
        }

        if ($request->filled('commune_id')) {
            $communeId = (int) $request->commune_id;
            $commune = Commune::query()->select('id', 'name', 'pcode')->find($communeId);
            $query->whereHas('site', function ($siteQuery) use ($communeId, $commune) {
                $siteQuery->where(function ($q) use ($communeId, $commune) {
                    $q->where('commune_id', $communeId);
                    if ($commune) {
                        $q->orWhereRaw('LOWER(TRIM(zone_sante)) = ?', [mb_strtolower(trim($commune->name))]);
                        if ($commune->pcode) {
                            $q->orWhere('code_zone_sante', $commune->pcode);
                        }
                    }
                });
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
                'deltas' => [],
                'periode' => null,
                'periode_consideree' => null,
                'periode_comparaison_courante' => null,
                'periode_comparaison_reference' => null,
                'fallback_note' => null,
                'used_fallback' => false,
            ]);
        }

        [$consideredPeriod, $usedFallback, $fallbackNote] = $this->resolveDashboardPeriod($selectedPeriod, $request);

        $query = SiteMouvementPopulation::query()
            ->with('site:id,date_fermeture')
            ->where('statut', 'valide')
            ->whereDate('date_mouvement', '<=', $consideredPeriod->copy()->endOfMonth()->toDateString());

        $this->applySiteFiltersToMovementQuery($query, $request);

            $comparisonPeriod = $this->resolveComparisonPeriod($consideredPeriod, $request);

        $mouvements = $this->normalizeMovementsForClosure(
            app(SitePopulationService::class)->snapshots($query->get()),
            $consideredPeriod->copy()->endOfMonth()
        );
            $previousMouvements = collect();

            if ($comparisonPeriod) {
                $previousQuery = SiteMouvementPopulation::query()
                    ->with('site:id,date_fermeture')
                    ->where('statut', 'valide')
                    ->whereDate('date_mouvement', '<=', $comparisonPeriod->copy()->endOfMonth()->toDateString());

                $this->applySiteFiltersToMovementQuery($previousQuery, $request);

                $previousMouvements = $this->normalizeMovementsForClosure(
                    app(SitePopulationService::class)->snapshots($previousQuery->get()),
                    $comparisonPeriod->copy()->endOfMonth()
                );
            }

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
            'periode_consideree' => $consideredPeriod->format('m/Y'),
                'periode_comparaison_courante' => $consideredPeriod->format('m/Y'),
                'periode_comparaison_reference' => $comparisonPeriod?->format('m/Y'),
            'fallback_note' => $fallbackNote,
            'used_fallback' => $usedFallback,
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
                'periode_consideree' => null,
                'fallback_note' => null,
                'used_fallback' => false,
            ]);
        }

        [$consideredPeriod, $usedFallback, $fallbackNote] = $this->resolveDashboardPeriod($selectedPeriod, $request);

        $query = SiteMouvementPopulation::query()
            ->with('site:id,province')
            ->where('statut', 'valide')
            ->whereDate('date_mouvement', '<=', $consideredPeriod->copy()->endOfMonth()->toDateString());

        $this->applySiteFiltersToMovementQuery($query, $request);

        $distribution = $this->normalizeMovementsForClosure(
            app(SitePopulationService::class)->snapshots(
                $query->with('site:id,province,date_fermeture')->get()
            ),
            $consideredPeriod->copy()->endOfMonth()
        )
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
            'periode_consideree' => $consideredPeriod->format('m/Y'),
            'fallback_note' => $fallbackNote,
            'used_fallback' => $usedFallback,
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
            ->where('statut', 'valide')
            ->where('type_mouvement', 'arrivee')
            ->whereHas('site', function ($siteQuery) {
                $siteQuery->where(function ($q) {
                    $q->whereNull('date_fermeture')
                        ->orWhereColumn('date_fermeture', '>', 'site_mouvements_population.date_mouvement');
                });
            })
            ->whereBetween('date_mouvement', [$startDate, $endDate])
            ->groupBy('mois')
            ->orderBy('mois')
            ->pluck('total', 'mois')
            ->toArray();

        // Mouvements de sorties
        $sorties = SiteMouvementPopulation::selectRaw('DATE_FORMAT(date_mouvement, "%Y-%m") as mois, SUM(ABS(individus)) as total')
            ->where('statut', 'valide')
            ->where('type_mouvement', 'depart')
            ->whereHas('site', function ($siteQuery) {
                $siteQuery->where(function ($q) {
                    $q->whereNull('date_fermeture')
                        ->orWhereColumn('date_fermeture', '>', 'site_mouvements_population.date_mouvement');
                });
            })
            ->whereBetween('date_mouvement', [$startDate, $endDate])
            ->groupBy('mois')
            ->orderBy('mois')
            ->pluck('total', 'mois')
            ->toArray();

        $naissances = [];
        $deces = [];

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

        $this->applySiteOpenFilter($query);

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

        $sites = $query->get();
        app(SitePopulationService::class)->applyToSites($sites);
        $sites = $sites->map(function ($site) {
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
