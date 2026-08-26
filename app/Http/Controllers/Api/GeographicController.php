<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\Territoire;
use App\Models\Commune;
use App\Models\Site;
use App\Models\SiteGeography;
use App\Models\Coordinateur;
use App\Models\Gestionnaire;
use App\Models\CategorieSite;
use App\Services\SitePopulationService;
use App\Services\GeoJsonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class GeographicController extends Controller
{
    public function __construct(private readonly GeoJsonService $geoJsonService)
    {
    }

    /**
     * Récupère toutes les provinces
     */
    public function getProvinces()
    {
        $provinces = Province::select('id', 'name', 'pcode')
            ->orderBy('name')
            ->get();

        return response()->json($provinces);
    }

    /**
     * Récupère les territoires d'une province
     */
    public function getTerritoires(Request $request)
    {
        $provinceId = $request->input('province_id');

        if (!$provinceId) {
            return response()->json([]);
        }

        $territoires = Territoire::where('province_id', $provinceId)
            ->select('id', 'name', 'pcode', 'province_id')
            ->orderBy('name')
            ->get();

        return response()->json($territoires);
    }

    /**
     * Récupère les communes d'un territoire
     * Note: Les communes sont appelées "Zones de santé" dans l'interface utilisateur
     */
    public function getCommunes(Request $request)
    {
        $territoireId = $request->input('territoire_id');

        if (!$territoireId) {
            return response()->json([]);
        }

        $communes = Commune::where('territoire_id', $territoireId)
            ->select('id', 'name', 'pcode', 'territoire_id', 'province_id')
            ->orderBy('name')
            ->get();

        return response()->json($communes);
    }

    /**
     * Récupère les informations complètes d'une province avec ses territoires
     */
    public function getProvinceDetails($id)
    {
        $province = Province::with(['territoires' => function ($query) {
            $query->select('id', 'name', 'pcode', 'province_id')->orderBy('name');
        }])
            ->select('id', 'name', 'pcode', 'area_sqkm', 'center_lat', 'center_lon')
            ->find($id);

        if (!$province) {
            return response()->json(['error' => 'Province non trouvée'], 404);
        }

        return response()->json($province);
    }

    /**
     * Récupère les informations complètes d'un territoire avec ses communes
     */
    public function getTerritoireDetails($id)
    {
        $territoire = Territoire::with(['communes' => function ($query) {
            $query->select('id', 'name', 'pcode', 'territoire_id', 'province_id')->orderBy('name');
        }])
            ->select('id', 'name', 'pcode', 'province_id', 'area_sqkm', 'center_lat', 'center_lon')
            ->find($id);

        if (!$territoire) {
            return response()->json(['error' => 'Territoire non trouvé'], 404);
        }

        return response()->json($territoire);
    }

    /**
     * Récupère les sites d'une commune (zone de santé)
     */
    public function getSites(Request $request)
    {
        $communeId = $request->input('commune_id');

        if (!$communeId) {
            return response()->json([]);
        }

        $selectColumns = ['id'];
        foreach (['nom', 'code_site', 'commune_id', 'zone_sante'] as $column) {
            if (Schema::hasColumn('sites', $column)) {
                $selectColumns[] = $column;
            }
        }

        $query = Site::query();

        if (Schema::hasColumn('sites', 'date_fermeture')) {
            $query->whereNull('date_fermeture');
        }

        $commune = Commune::select('id', 'name')->find($communeId);
        $hasCommuneColumn = Schema::hasColumn('sites', 'commune_id');
        $hasZoneSanteColumn = Schema::hasColumn('sites', 'zone_sante');

        if (! $hasCommuneColumn && ! $hasZoneSanteColumn) {
            return response()->json(
                $query
                    ->select($selectColumns)
                    ->orderBy('nom')
                    ->get()
            );
        }

        $normalizedCommuneName = $commune ? strtolower(trim((string) $commune->name)) : null;

        if ($hasCommuneColumn && $hasZoneSanteColumn && $commune) {
            $query->where(function ($siteQuery) use ($communeId, $commune, $normalizedCommuneName) {
                $siteQuery->where('commune_id', $communeId)
                    ->orWhere('zone_sante', $commune->name);

                if ($normalizedCommuneName !== '') {
                    $siteQuery->orWhereRaw('LOWER(TRIM(zone_sante)) = ?', [$normalizedCommuneName]);
                }
            });
        } elseif ($hasCommuneColumn) {
            $query->where('commune_id', $communeId);
        } elseif ($hasZoneSanteColumn && $commune) {
            $query->where(function ($siteQuery) use ($commune, $normalizedCommuneName) {
                $siteQuery->where('zone_sante', $commune->name);
                if ($normalizedCommuneName !== '') {
                    $siteQuery->orWhereRaw('LOWER(TRIM(zone_sante)) = ?', [$normalizedCommuneName]);
                }
            });
        } else {
            return response()->json([]);
        }

        $sites = $query
            ->select($selectColumns)
            ->orderBy('nom')
            ->get();

        return response()->json($sites);
    }

    /**
     * Récupère tous les coordinateurs
     */
    public function getCoordinateurs()
    {
        $coordinateurs = Coordinateur::select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        return response()->json($coordinateurs);
    }

    /**
     * Récupère tous les gestionnaires
     */
    public function getGestionnaires()
    {
        $gestionnaires = Gestionnaire::select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        return response()->json($gestionnaires);
    }

    /**
     * Récupère toutes les catégories de sites (Mécanisme CCCM)
     */
    public function getCategoriesSites()
    {
        $categories = CategorieSite::select('id', 'name', 'code', 'description')
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }

    /**
     * Récupère les sites avec coordonnées GPS pour la cartographie
     */
    public function getSitesWithCoordinates(Request $request)
    {
        if (!Schema::hasColumn('sites', 'latitude') || !Schema::hasColumn('sites', 'longitude')) {
            return response()->json([
                'sites' => [],
                'total' => 0,
                'considered_period' => null,
                'requested_period' => $request->input('periode'),
                'used_fallback_period' => false,
                'fallback_note' => null,
            ]);
        }

        $selectedPeriod = null;
        $currentPeriod = now()->startOfMonth();

        if ($request->filled('periode') && (preg_match('/^\d{4}-\d{2}$/', $request->periode) || preg_match('/^\d{2}\/\d{4}$/', $request->periode))) {
            $parsed = preg_match('/^\d{2}\/\d{4}$/', $request->periode)
                ? Carbon::createFromFormat('m/Y', $request->periode)->startOfMonth()
                : Carbon::createFromFormat('Y-m', $request->periode)->startOfMonth();

            $selectedPeriod = $parsed->gt($currentPeriod) ? $currentPeriod : $parsed;
        }

        $query = Site::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', 0)
            ->where('longitude', '!=', 0);

        if (!$selectedPeriod && Schema::hasColumn('sites', 'date_fermeture')) {
            $query->whereNull('date_fermeture');
        }

        // Filtrer par province
        if ($request->has('province_id') && $request->province_id) {
            $province = Province::select('id', 'name')->find($request->province_id);

            if ($province) {
                $query->where(function ($siteQuery) use ($request, $province) {
                    $siteQuery->whereHas('commune.territoire.province', function ($q) use ($request) {
                        $q->where('provinces.id', $request->province_id);
                    });

                    if (Schema::hasColumn('sites', 'province')) {
                        $siteQuery->orWhere('province', $province->name);
                    }
                });
            }
        }

        // Filtrer par territoire
        if ($request->has('territoire_id') && $request->territoire_id) {
            $territoire = Territoire::select('id', 'name')->find($request->territoire_id);

            if ($territoire) {
                $query->where(function ($siteQuery) use ($request, $territoire) {
                    $siteQuery->whereHas('commune.territoire', function ($q) use ($request) {
                        $q->where('territoires.id', $request->territoire_id);
                    });

                    if (Schema::hasColumn('sites', 'territoire')) {
                        $siteQuery->orWhere('territoire', $territoire->name);
                    }
                });
            }
        }

        // Filtrer par commune (zone de santé)
        if (Schema::hasColumn('sites', 'commune_id') && $request->has('commune_id') && $request->commune_id) {
            $commune = Commune::select('id', 'name')->find($request->commune_id);

            if ($commune) {
                $query->where(function ($siteQuery) use ($request, $commune) {
                    $siteQuery->where('commune_id', $request->commune_id);

                    if (Schema::hasColumn('sites', 'zone_sante')) {
                        $siteQuery->orWhere('zone_sante', $commune->name);
                    }
                });
            } else {
                $query->where('commune_id', $request->commune_id);
            }
        }

        // Filtrer par nom de zone de santé (champ texte sur Site)
        if ($request->has('zone_sante') && trim((string) $request->zone_sante) !== '') {
            $zoneSante = trim((string) $request->zone_sante);
            $query->where(function ($siteQuery) use ($zoneSante) {
                $siteQuery->whereHas('commune', function ($q) use ($zoneSante) {
                    $q->where('name', $zoneSante);
                });

                if (Schema::hasColumn('sites', 'zone_sante')) {
                    $siteQuery->orWhere('zone_sante', $zoneSante);
                }
            });
        }

        // Filtrer par site spécifique
        if ($request->has('site_id') && $request->site_id) {
            $query->where('id', $request->site_id);
        }

        // Filtrer par coordinateur
        if (Schema::hasColumn('sites', 'coordinateur_id') && $request->has('coordinateur_id') && $request->coordinateur_id) {
            $query->where('coordinateur_id', $request->coordinateur_id);
        }

        // Filtrer par gestionnaire
        if (Schema::hasColumn('sites', 'gestionnaire_id') && $request->has('gestionnaire_id') && $request->gestionnaire_id) {
            $query->where('gestionnaire_id', $request->gestionnaire_id);
        }

        // Filtrer par catégorie de site
        if (Schema::hasColumn('sites', 'categorie_site_id') && $request->has('categorie_site_id') && $request->categorie_site_id) {
            $query->where('categorie_site_id', $request->categorie_site_id);
        }

        $selectColumns = ['id'];
        foreach (['nom', 'code_site', 'latitude', 'longitude', 'province', 'territoire', 'zone_sante', 'geojson_data', 'geometry_type'] as $column) {
            if (Schema::hasColumn('sites', $column)) {
                $selectColumns[] = $column;
            }
        }

        foreach (['categorie_site_id', 'gestionnaire_id', 'coordinateur_id', 'organisation_id'] as $column) {
            if (Schema::hasColumn('sites', $column)) {
                $selectColumns[] = $column;
            }
        }

        $siteRelations = [];
        if (Schema::hasColumn('sites', 'categorie_site_id')) {
            $siteRelations[] = 'categorieSite:id,name';
        }
        if (Schema::hasColumn('sites', 'gestionnaire_id')) {
            $siteRelations[] = 'gestionnaire:id,name';
        }
        if (Schema::hasColumn('sites', 'coordinateur_id')) {
            $siteRelations[] = 'coordinateur:id,name';
        }
        if (Schema::hasColumn('sites', 'organisation_id')) {
            $siteRelations[] = 'organisation:id,name';
        }

        $sites = $query->select($selectColumns);

        if (!empty($siteRelations)) {
            $sites = $sites->with($siteRelations);
        }

        $sites = $sites->get();

        $geographiesBySite = collect();
        if (Schema::hasTable('site_geographies') && $sites->isNotEmpty()) {
            $geographiesBySite = SiteGeography::query()
                ->whereIn('site_id', $sites->pluck('id'))
                ->whereNotNull('geojson_data')
                ->orderBy('collected_at')
                ->orderBy('id')
                ->get([
                    'id',
                    'site_id',
                    'geometry_type',
                    'point_category',
                    'point_category_other',
                    'polygon_category',
                    'polygon_block_name',
                    'geojson_data',
                    'collected_at',
                ])
                ->groupBy('site_id');
        }

        $sites->each(function (Site $site) use ($geographiesBySite): void {
            $collectedLayers = ($geographiesBySite->get($site->id) ?? collect())
                ->map(fn (SiteGeography $geography): array => [
                    'id' => $geography->id,
                    'name' => $this->geographyLayerName($geography),
                    'geometry_type' => $geography->geometry_type,
                    'point_category' => $geography->point_category,
                    'point_icon' => $this->geographyPointIcon($geography->point_category),
                    'polygon_category' => $geography->polygon_category,
                    'collected_at' => $geography->collected_at?->toIso8601String(),
                    'geojson' => $this->geoJsonService->normalize($geography->geojson_data),
                ])
                ->values();

            if ($collectedLayers->isEmpty()) {
                $collectedLayers = collect($this->normalizeStoredSiteLayers($site->geojson_data));
            }

            $site->setAttribute('collected_layers', $collectedLayers->all());
            $site->setAttribute('geojson_data', $collectedLayers->isEmpty()
                ? null
                : ['layers' => $collectedLayers->all()]);
        });

        app(SitePopulationService::class)->applyToSites(
            $sites,
            $selectedPeriod?->copy()->endOfMonth()
        );
        $consideredPeriod = $selectedPeriod;
        $usedFallback = false;
        $fallbackNote = null;

        return response()->json([
            'sites' => $sites->values(),
            'count' => $sites->count(),
            'periode' => $selectedPeriod?->format('m/Y'),
            'periode_consideree' => $consideredPeriod?->format('m/Y'),
            'fallback_note' => $fallbackNote,
            'used_fallback' => $usedFallback,
        ]);
    }

    private function geographyLayerName(SiteGeography $geography): string
    {
        if ($geography->geometry_type === 'point') {
            if ($geography->point_category === 'autre' && filled($geography->point_category_other)) {
                return (string) $geography->point_category_other;
            }

            return match ($geography->point_category) {
                'robinet' => 'Robinet',
                'douche' => 'Douche',
                'toilette' => 'Toilette',
                'abris' => 'Abris',
                'point_eau' => 'Point d’eau',
                'centre_sante' => 'Centre de santé',
                'ecole' => 'École',
                'universite' => 'Université',
                'marche' => 'Marché',
                'hopital' => 'Hôpital',
                'lavage_main' => 'Lavage des mains',
                default => 'Point collecté',
            };
        }

        if ($geography->polygon_category === 'bloc') {
            return filled($geography->polygon_block_name)
                ? 'Bloc - '.$geography->polygon_block_name
                : 'Bloc du site';
        }

        return $geography->polygon_category === 'contour_site'
            ? 'Contour du site'
            : 'Polygone collecté';
    }

    private function geographyPointIcon(?string $category): string
    {
        return match ($category) {
            'robinet' => '🚰',
            'douche' => '🚿',
            'toilette' => '🚻',
            'abris' => '🏠',
            'point_eau' => '💧',
            'centre_sante' => '⚕️',
            'ecole' => '🏫',
            'universite' => '🎓',
            'marche' => '🛒',
            'hopital' => '🏥',
            'lavage_main' => '🧼',
            default => '📍',
        };
    }

    private function normalizeStoredSiteLayers(mixed $geojson): array
    {
        if (! is_array($geojson)) {
            return [];
        }

        if (isset($geojson['layers']) && is_array($geojson['layers'])) {
            return collect($geojson['layers'])
                ->filter(fn (mixed $layer): bool => is_array($layer) && is_array($layer['geojson'] ?? $layer['data'] ?? null))
                ->map(function (array $layer): array {
                    $layer['geojson'] = $this->geoJsonService->normalize($layer['geojson'] ?? $layer['data']);
                    unset($layer['data']);

                    return $layer;
                })
                ->values()
                ->all();
        }

        if (isset($geojson['type'])) {
            return [[
                'id' => 'site-geojson',
                'name' => 'Couche du site',
                'geometry_type' => null,
                'point_category' => null,
                'point_icon' => '📍',
                'polygon_category' => null,
                'collected_at' => null,
                'geojson' => $this->geoJsonService->normalize($geojson),
            ]];
        }

        return [];
    }
}
