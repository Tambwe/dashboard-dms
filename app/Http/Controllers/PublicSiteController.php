<?php

namespace App\Http\Controllers;

use App\Models\OssatReport;
use App\Models\Province;
use App\Models\Site;
use App\Services\SitePopulationService;
use App\Models\Territoire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PublicSiteController extends Controller
{
    /**
     * Page d'accueil publique du profil site (sélecteur Province→Territoire→Site).
     */
    public function index()
    {
        $provinces = Province::orderBy('name')->get();
        return view('public.site-profil', compact('provinces'));
    }

    /**
     * Affiche le profil complet d'un site avec son dernier rapport OSSAT validé.
     */
    public function show(Site $site)
    {
        $provinces = Province::orderBy('name')->get();
        $site->load(['commune.territoire.province', 'organisation', 'typeSite', 'mouvementsPopulationValides']);

        // Priorité au rapport validé, sinon le plus récent
        $ossatReport = OssatReport::where('site_id', $site->id)
            ->where('statut_validation', 'valide')
            ->latest('today')
            ->first()
            ?? OssatReport::where('site_id', $site->id)->latest('today')->first();

        if ($ossatReport) {
            $ossatReport->load(['createdBy', 'validePar']);
        }

        $populationMouvement = app(SitePopulationService::class)->snapshotForSite($site->id);

        return view('public.site-profil', compact('provinces', 'site', 'ossatReport', 'populationMouvement'));
    }

    /**
     * Carte interactive de tous les sites avec coordonnées GPS.
     */
    public function cartographie()
    {
        $provinces  = Province::orderBy('name')->get();
        $territoires = \App\Models\Territoire::orderBy('name')->get(['id', 'name', 'province_id']);
        $totalSites = 0;
        if (Schema::hasColumn('sites', 'latitude') && Schema::hasColumn('sites', 'longitude')) {
            $totalSites = \App\Models\Site::whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('latitude', '!=', 0)
                ->where('longitude', '!=', 0)
                ->count();
        }
        return view('public.cartographie', compact('provinces', 'territoires', 'totalSites'));
    }

    /**
     * Carte interactive Mapbox GL JS.
     */
    public function cartographieMapbox()
    {
        $provinces   = Province::orderBy('name')->get();
        $territoires = \App\Models\Territoire::orderBy('name')->get(['id', 'name', 'province_id']);
        $totalSites = 0;
        if (Schema::hasColumn('sites', 'latitude') && Schema::hasColumn('sites', 'longitude')) {
            $totalSites = \App\Models\Site::whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('latitude', '!=', 0)
                ->where('longitude', '!=', 0)
                ->count();
        }
        $mapboxToken = env('MAPBOX_TOKEN', '');
        return view('public.cartographie-mapbox', compact('provinces', 'territoires', 'totalSites', 'mapboxToken'));
    }
}
