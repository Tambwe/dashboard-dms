<?php

namespace App\Http\Controllers;

use App\Models\Province;
use App\Models\Territoire;
use App\Models\Commune;
use App\Models\Site;
use App\Models\SiteMouvementPopulation;
use App\Services\SitePopulationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SiteMasterListController extends Controller
{
    /**
     * Affiche la Master List des sites avec variations mensuelles
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = $request->input('per_page', 50);
        $selectedPeriod = $this->resolveSelectedPeriod($request->input('periode'));
        $selectedProvinceId = $request->input('province_id');
        $selectedTerritoireId = $request->input('territoire_id');
        $selectedCommuneId = $request->input('commune_id');

        if (!$selectedTerritoireId && $selectedCommuneId) {
            $selectedTerritoireId = Commune::find($selectedCommuneId)?->territoire_id;
        }

        if (!$selectedProvinceId) {
            if ($selectedTerritoireId) {
                $selectedProvinceId = Territoire::find($selectedTerritoireId)?->province_id;
            } elseif ($selectedCommuneId) {
                $selectedProvinceId = Commune::with('territoire')->find($selectedCommuneId)?->territoire?->province_id;
            }
        }
        
        // Période de référence sélectionnée par l'utilisateur.
        $selectedPeriodEnd = $selectedPeriod->copy()->endOfMonth();
        
        // Query de base
        $query = Site::with([
            'province',
            'territoire',
            'commune',
            'coordinateur',
            'gestionnaire',
            'typeSite',
            'categorieSite'
        ]);
        
        // Recherche
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nom', 'LIKE', "%{$search}%")
                  ->orWhere('code_site', 'LIKE', "%{$search}%")
                  ->orWhereHas('province', function($sq) use ($search) {
                      $sq->where('name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('territoire', function($sq) use ($search) {
                      $sq->where('name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('commune', function($sq) use ($search) {
                      $sq->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        $this->applyGeoFilters($query, $request);

        $filteredSites = (clone $query)->with([
            'province',
            'territoire',
            'commune',
            'coordinateur',
            'gestionnaire',
            'typeSite',
            'categorieSite'
        ])->get();
        app(SitePopulationService::class)->applyToSites($filteredSites, $selectedPeriodEnd);

        $sitesByType = $filteredSites->groupBy(function ($site) {
            return $site->typeSite->name ?? 'Non spécifié';
        })->map(function ($items, $typeName) use ($selectedPeriodEnd) {
            return [
                'type' => $typeName,
                'site_count' => $items->count(),
                'total_menages' => $items->sum(function ($site) use ($selectedPeriodEnd) {
                    return $this->resolveEffectiveCurrentValue($site, 'menages', $selectedPeriodEnd);
                }),
                'total_individus' => $items->sum(function ($site) use ($selectedPeriodEnd) {
                    return $this->resolveEffectiveCurrentValue($site, 'individus', $selectedPeriodEnd);
                }),
            ];
        })->sortByDesc('site_count')->values();

        $typeSummaryTotals = [
            'site_count' => $filteredSites->count(),
            'total_menages' => $filteredSites->sum(function ($site) use ($selectedPeriodEnd) {
                return $this->resolveEffectiveCurrentValue($site, 'menages', $selectedPeriodEnd);
            }),
            'total_individus' => $filteredSites->sum(function ($site) use ($selectedPeriodEnd) {
                return $this->resolveEffectiveCurrentValue($site, 'individus', $selectedPeriodEnd);
            }),
        ];
        
        // Pagination
        $sites = $query->orderBy('nom')->paginate($perPage);
        $sites->appends($request->query());
        app(SitePopulationService::class)->applyToSites($sites->getCollection(), $selectedPeriodEnd);
        
        // Calculer les variations pour chaque site
        foreach ($sites as $site) {
            $site->current_menages = $this->resolveEffectiveCurrentValue($site, 'menages', $selectedPeriodEnd);
            $site->current_individus = $this->resolveEffectiveCurrentValue($site, 'individus', $selectedPeriodEnd);
            $site->variation = $this->calculateMonthlyVariation($site->id, $selectedPeriod);
        }
        
        $provinces = Province::orderBy('name')->get(['id', 'name']);

        return view('sites.master-list', compact(
            'sites',
            'search',
            'perPage',
            'provinces',
            'selectedProvinceId',
            'selectedTerritoireId',
            'selectedCommuneId',
            'selectedPeriod',
            'sitesByType',
            'typeSummaryTotals'
        ));
    }
    
    /**
     * Calcule la variation mensuelle pour un site
     */
    private function calculateMonthlyVariation($siteId, Carbon $selectedPeriod)
    {
        // Récupérer le dernier recensement validé disponible jusqu'à la période sélectionnée.
        $comparisonRecord = SiteMouvementPopulation::where('site_id', $siteId)
            ->where('statut', 'valide')
            ->where('type_mouvement', 'recensement')
            ->whereDate('date_mouvement', '<=', $selectedPeriod->copy()->endOfMonth()->toDateString())
            ->orderBy('date_mouvement', 'desc')
            ->first();
        
        if (!$comparisonRecord) {
            return [
                'menages_variation' => 0,
                'menages_percentage' => 0,
                'individus_variation' => 0,
                'individus_percentage' => 0,
                'has_data' => false,
                'comparison_period_label' => null,
            ];
        }
        
        $comparisonPeriod = Carbon::parse($comparisonRecord->date_mouvement)->startOfMonth();
        $currentPopulation = app(SitePopulationService::class)
            ->forSite((int) $siteId, $selectedPeriod->copy()->endOfMonth());
        $currentMenages = $currentPopulation['menages'];
        $currentIndividus = $currentPopulation['individus'];

        $menagesVariation = $currentMenages - $comparisonRecord->menages;
        $individusVariation = $currentIndividus - $comparisonRecord->individus;
        
        $menagesPercentage = $comparisonRecord->menages > 0
            ? round(($menagesVariation / $comparisonRecord->menages) * 100, 1)
            : 0;
            
        $individusPercentage = $comparisonRecord->individus > 0
            ? round(($individusVariation / $comparisonRecord->individus) * 100, 1)
            : 0;
        
        return [
            'menages_variation' => $menagesVariation,
            'menages_percentage' => $menagesPercentage,
            'menages_previous' => $comparisonRecord->menages,
            'individus_variation' => $individusVariation,
            'individus_percentage' => $individusPercentage,
            'individus_previous' => $comparisonRecord->individus,
            'has_data' => true,
            'comparison_period_label' => $comparisonPeriod->format('m/Y'),
        ];
    }
    
    /**
     * Export Excel de la Master List
     */
    public function exportExcel(Request $request)
    {
        $search = $request->input('search');
        $selectedPeriod = $this->resolveSelectedPeriod($request->input('periode'));
        
        // Calculer les dates
        $selectedPeriodEnd = $selectedPeriod->copy()->endOfMonth();
        
        // Query identique à index()
        $query = Site::with([
            'province',
            'territoire',
            'commune',
            'coordinateur',
            'gestionnaire',
            'typeSite',
            'categorieSite'
        ]);
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nom', 'LIKE', "%{$search}%")
                  ->orWhere('code_site', 'LIKE', "%{$search}%");
            });
        }

        $this->applyGeoFilters($query, $request);
        
        $sites = $query->orderBy('nom')->get();
        app(SitePopulationService::class)->applyToSites($sites, $selectedPeriodEnd);
        
        // Calculer les variations
        foreach ($sites as $site) {
            $site->current_menages = $this->resolveEffectiveCurrentValue($site, 'menages', $selectedPeriodEnd);
            $site->current_individus = $this->resolveEffectiveCurrentValue($site, 'individus', $selectedPeriodEnd);
            $site->variation = $this->calculateMonthlyVariation($site->id, $selectedPeriod);
        }
        
        return $this->generateExcel($sites);
    }

    /**
     * Historique détaillé des variations pour un site de la Master List.
     */
    public function history(Request $request, Site $site)
    {
        $selectedPeriod = $this->resolveSelectedPeriod($request->input('periode'));

        $site->load(['province', 'territoire', 'commune', 'typeSite', 'categorieSite', 'gestionnaire', 'coordinateur']);

        $recensements = SiteMouvementPopulation::with(['raisonMouvement', 'createdBy', 'validatedBy'])
            ->where('site_id', $site->id)
            ->where('statut', 'valide')
            ->where('type_mouvement', 'recensement')
            ->whereDate('date_mouvement', '<=', $selectedPeriod->copy()->endOfMonth()->toDateString())
            ->orderBy('date_mouvement')
            ->orderBy('id')
            ->get();

        $cumulativeIndividusVariation = 0;
        $cumulativeMenagesVariation = 0;
        $previous = null;

        $recensements = $recensements->map(function ($item) use (&$previous, &$cumulativeIndividusVariation, &$cumulativeMenagesVariation) {
            $deltaIndividus = $previous ? ((int) $item->individus - (int) $previous->individus) : 0;
            $deltaMenages = $previous ? ((int) $item->menages - (int) $previous->menages) : 0;

            $cumulativeIndividusVariation += $deltaIndividus;
            $cumulativeMenagesVariation += $deltaMenages;

            $item->variation_individus = $deltaIndividus;
            $item->variation_menages = $deltaMenages;
            $item->cumul_variation_individus = $cumulativeIndividusVariation;
            $item->cumul_variation_menages = $cumulativeMenagesVariation;

            $previous = $item;

            return $item;
        });

        $latestRecensement = $recensements->last();

        return view('sites.master-list-history', [
            'site' => $site,
            'selectedPeriod' => $selectedPeriod,
            'recensements' => $recensements,
            'latestRecensement' => $latestRecensement,
            'cumulativeIndividusVariation' => $cumulativeIndividusVariation,
            'cumulativeMenagesVariation' => $cumulativeMenagesVariation,
            'backQuery' => $request->query(),
        ]);
    }
    
    /**
     * Génère le fichier Excel
     */
    private function generateExcel($sites)
    {
        $filename = 'master-list-sites-' . date('Y-m-d-His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];
        
        $callback = function() use ($sites) {
            $file = fopen('php://output', 'w');
            
            // BOM UTF-8 pour Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // En-têtes
            fputcsv($file, [
                'Code Site',
                'Nom du Site',
                'Province',
                'Territoire',
                'Zone de Santé',
                'Type Site',
                'Catégorie',
                'Coordinateur',
                'Gestionnaire',
                'Ménages Actuels',
                'Individus Actuels',
                'Période de comparaison',
                'Ménages Référence',
                'Variation Ménages',
                'Variation % Ménages',
                'Période de comparaison Individus',
                'Individus Référence',
                'Variation Individus',
                'Variation % Individus',
                'Femmes 0-5',
                'Femmes 6-17',
                'Femmes 18-59',
                'Femmes 60+',
                'Hommes 0-5',
                'Hommes 6-17',
                'Hommes 18-59',
                'Hommes 60+',
                'Date Mise à Jour'
            ], ';');
            
            // Données
            foreach ($sites as $site) {
                fputcsv($file, [
                    $site->code_site,
                    $site->nom,
                    $site->province->name ?? '',
                    $site->territoire->name ?? '',
                    $site->commune->name ?? '',
                    $site->typeSite->name ?? '',
                    $site->categorieSite->name ?? '',
                    $site->coordinateur->nom ?? '',
                    $site->gestionnaire->nom ?? '',
                    $site->current_menages,
                    $site->current_individus,
                    $site->variation['comparison_period_label'] ?? '',
                    $site->variation['menages_previous'] ?? '',
                    $site->variation['menages_variation'] ?? 0,
                    $site->variation['menages_percentage'] ?? 0,
                    $site->variation['comparison_period_label'] ?? '',
                    $site->variation['individus_previous'] ?? '',
                    $site->variation['individus_variation'] ?? 0,
                    $site->variation['individus_percentage'] ?? 0,
                    $site->f_0_5 ?? 0,
                    $site->f_6_17 ?? 0,
                    $site->f_18_59 ?? 0,
                    $site->f_60_plus ?? 0,
                    $site->h_0_5 ?? 0,
                    $site->h_6_17 ?? 0,
                    $site->h_18_59 ?? 0,
                    $site->h_60_plus ?? 0,
                    $site->population_date ? Carbon::parse($site->population_date)->format('d/m/Y') : ''
                ], ';');
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    private function resolveEffectiveCurrentValue(Site $site, string $field, Carbon $periodEnd): int
    {
        if ($site->date_fermeture && Carbon::parse($site->date_fermeture)->lte($periodEnd)) {
            return 0;
        }

        return (int) ($site->{$field} ?? 0);
    }

    private function resolveSelectedPeriod(?string $period): Carbon
    {
        if (!$period) {
            return Carbon::now()->startOfMonth();
        }

        try {
            return Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        } catch (\Throwable $e) {
            return Carbon::now()->startOfMonth();
        }
    }

    private function applyGeoFilters($query, Request $request): void
    {
        if ($request->filled('province_id')) {
            $province = Province::select('id', 'name')->find($request->province_id);

            if ($province) {
                $query->where(function ($siteQuery) use ($province) {
                    $siteQuery->whereHas('province', function ($relationQuery) use ($province) {
                        $relationQuery->where('provinces.id', $province->id);
                    });

                    if (!empty($province->name)) {
                        $siteQuery->orWhere('province', $province->name);
                    }
                });
            }
        }

        if ($request->filled('territoire_id')) {
            $territoire = Territoire::select('id', 'name')->find($request->territoire_id);

            if ($territoire) {
                $query->where(function ($siteQuery) use ($territoire) {
                    $siteQuery->whereHas('territoire', function ($relationQuery) use ($territoire) {
                        $relationQuery->where('territoires.id', $territoire->id);
                    });

                    if (!empty($territoire->name)) {
                        $siteQuery->orWhere('territoire', $territoire->name);
                    }
                });
            }
        }

        if ($request->filled('commune_id')) {
            $commune = Commune::select('id', 'name')->find($request->commune_id);

            if ($commune) {
                $query->where(function ($siteQuery) use ($commune) {
                    $siteQuery->whereHas('commune', function ($relationQuery) use ($commune) {
                        $relationQuery->where('communes.id', $commune->id);
                    })->orWhere('zone_sante', $commune->name);
                });
            }
        }
    }
}
