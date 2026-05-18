<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\Territoire;
use App\Models\Commune;
use App\Models\Site;
use App\Models\SiteMouvementPopulation;
use App\Models\Coordinateur;
use App\Models\Gestionnaire;
use App\Models\CategorieSite;
use Illuminate\Http\Request;
use Carbon\Carbon;

class GeographicController extends Controller
{
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

        $sites = Site::where('commune_id', $communeId)
            ->select('id', 'nom', 'code_site', 'commune_id')
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
        $query = Site::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', 0)
            ->where('longitude', '!=', 0);

        // Filtrer par province
        if ($request->has('province_id') && $request->province_id) {
            $province = Province::select('id', 'name')->find($request->province_id);

            if ($province) {
                $query->where(function ($siteQuery) use ($request, $province) {
                    $siteQuery->whereHas('commune.territoire.province', function ($q) use ($request) {
                        $q->where('id', $request->province_id);
                    })->orWhere('province', $province->name);
                });
            }
        }

        // Filtrer par territoire
        if ($request->has('territoire_id') && $request->territoire_id) {
            $territoire = Territoire::select('id', 'name')->find($request->territoire_id);

            if ($territoire) {
                $query->where(function ($siteQuery) use ($request, $territoire) {
                    $siteQuery->whereHas('commune.territoire', function ($q) use ($request) {
                        $q->where('id', $request->territoire_id);
                    })->orWhere('territoire', $territoire->name);
                });
            }
        }

        // Filtrer par commune (zone de santé)
        if ($request->has('commune_id') && $request->commune_id) {
            $commune = Commune::select('id', 'name')->find($request->commune_id);

            if ($commune) {
                $query->where(function ($siteQuery) use ($request, $commune) {
                    $siteQuery->where('commune_id', $request->commune_id)
                        ->orWhere('zone_sante', $commune->name);
                });
            } else {
                $query->where('commune_id', $request->commune_id);
            }
        }

        // Filtrer par nom de zone de santé (champ texte sur Site)
        if ($request->has('zone_sante') && trim((string) $request->zone_sante) !== '') {
            $zoneSante = trim((string) $request->zone_sante);
            $query->where(function ($siteQuery) use ($zoneSante) {
                $siteQuery->where('zone_sante', $zoneSante)
                    ->orWhereHas('commune', function ($q) use ($zoneSante) {
                        $q->where('name', $zoneSante);
                    });
            });
        }

        // Filtrer par site spécifique
        if ($request->has('site_id') && $request->site_id) {
            $query->where('id', $request->site_id);
        }

        // Filtrer par coordinateur
        if ($request->has('coordinateur_id') && $request->coordinateur_id) {
            $query->where('coordinateur_id', $request->coordinateur_id);
        }

        // Filtrer par gestionnaire
        if ($request->has('gestionnaire_id') && $request->gestionnaire_id) {
            $query->where('gestionnaire_id', $request->gestionnaire_id);
        }

        // Filtrer par catégorie de site
        if ($request->has('categorie_site_id') && $request->categorie_site_id) {
            $query->where('categorie_site_id', $request->categorie_site_id);
        }

        $sites = $query->select(
            'id',
            'nom',
            'code_site',
            'latitude',
            'longitude',
            'individus',
            'menages',
            'province',
            'territoire',
            'zone_sante',
            'categorie_site_id',
            'gestionnaire_id',
            'coordinateur_id',
            'organisation_id',
            'geojson_data'
        )
        ->with([
            'categorieSite:id,name',
            'gestionnaire:id,name',
            'coordinateur:id,name',
            'organisation:id,name'
        ])
        ->get();

        if ($request->filled('periode') && (preg_match('/^\d{4}-\d{2}$/', $request->periode) || preg_match('/^\d{2}\/\d{4}$/', $request->periode))) {
            $selectedPeriod = preg_match('/^\d{2}\/\d{4}$/', $request->periode)
                ? Carbon::createFromFormat('m/Y', $request->periode)->startOfMonth()
                : Carbon::createFromFormat('Y-m', $request->periode)->startOfMonth();

            $movements = SiteMouvementPopulation::query()
                ->where('type_mouvement', 'recensement')
                ->whereYear('date_mouvement', (int) $selectedPeriod->format('Y'))
                ->whereMonth('date_mouvement', (int) $selectedPeriod->format('m'))
                ->whereIn('site_id', $sites->pluck('id'))
                ->get()
                ->keyBy('site_id');

            $sites = $sites
                ->filter(function ($site) use ($movements) {
                    return $movements->has($site->id);
                })
                ->map(function ($site) use ($movements) {
                    $movement = $movements->get($site->id);
                    $site->menages = $movement->menages;
                    $site->individus = $movement->individus;

                    return $site;
                })
                ->values();
        }

        return response()->json($sites);
    }
}
