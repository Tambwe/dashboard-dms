<?php

namespace App\Http\Controllers;

use App\Models\OssatReport;
use App\Models\Site;
use App\Models\SiteMouvementPopulation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OrganisationSiteController extends Controller
{
    /**
     * Display a listing of the organisation's sites.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Vérifier que l'utilisateur appartient à une organisation
        if (!$user->organisation_id) {
            abort(403, 'Vous devez être membre d\'une organisation pour accéder à cette page.');
        }

        $orgUserIds = \App\Models\User::where('organisation_id', $user->organisation_id)->pluck('id');
        $accessSiteIds = \Illuminate\Support\Facades\DB::table('site_user_access')
            ->whereIn('user_id', $orgUserIds)
            ->pluck('site_id');

        $query = Site::where(function ($q) use ($user, $accessSiteIds) {
            $q->where('organisation_id', $user->organisation_id);
            if ($accessSiteIds->isNotEmpty()) {
                $q->orWhereIn('id', $accessSiteIds);
            }
        })->with(['typeSite', 'commune', 'categorieSite']);

        // Recherche
        if ($request->filled('search')) {
            $query->where('nom', 'like', '%' . $request->search . '%');
        }

        $sites = $query->paginate(20);

        return view('organisation.sites.index', compact('sites'));
    }

    /**
     * Show the form for editing the specified site.
     */
    public function edit(Site $site)
    {
        $user = auth()->user();
        
        // Vérifier que le site appartient à l'organisation de l'utilisateur
        if ($site->organisation_id !== $user->organisation_id) {
            abort(403, 'Vous n\'avez pas accès à ce site.');
        }

        // Charger les relations de gestion du site
        $site->load(['organisation', 'gestionnaire', 'coordinateur']);

        $ossatReport = OssatReport::where('site_id', $site->id)->latest('today')->first();

        $populationMouvement = SiteMouvementPopulation::where('site_id', $site->id)
            ->where('statut', 'valide')
            ->latest('date_mouvement')
            ->first()
            ?? SiteMouvementPopulation::where('site_id', $site->id)
            ->latest('date_mouvement')
            ->first();

        return view('organisation.sites.edit', compact('site', 'ossatReport', 'populationMouvement'));
    }

    /**
     * Update the specified site in storage.
     */
    public function update(Request $request, Site $site)
    {
        $user = auth()->user();
        
        // Vérifier que le site appartient à l'organisation de l'utilisateur
        if ($site->organisation_id !== $user->organisation_id) {
            abort(403, 'Vous n\'avez pas accès à ce site.');
        }

        $validated = $request->validate([
            'longitude' => 'nullable|numeric|between:-180,180',
            'latitude' => 'nullable|numeric|between:-90,90',
            'geojson_data' => 'nullable|json',
            'photos.*' => 'nullable|image|max:5120', // 5MB max par image
        ]);

        // Gérer les coordonnées GPS
        if ($request->has('longitude')) {
            $site->longitude = $request->longitude;
        }
        
        if ($request->has('latitude')) {
            $site->latitude = $request->latitude;
        }

        // Gérer les données GeoJSON
        if ($request->has('geojson_data')) {
            $site->geojson_data = json_decode($request->geojson_data, true);
        }

        // Gérer les photos
        if ($request->hasFile('photos')) {
            $photos = $site->photos ?? [];
            
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('sites/photos', 'public');
                $photos[] = $path;
            }
            
            $site->photos = $photos;
        }

        $site->save();

        return redirect()->route('organisation.sites.edit', $site)
            ->with('success', 'Site mis à jour avec succès.');
    }

    /**
     * Delete a photo from the site.
     */
    public function deletePhoto(Request $request, Site $site)
    {
        $user = auth()->user();
        
        // Vérifier que le site appartient à l'organisation de l'utilisateur
        if ($site->organisation_id !== $user->organisation_id) {
            abort(403, 'Vous n\'avez pas accès à ce site.');
        }

        $request->validate([
            'photo_path' => 'required|string',
        ]);

        $photos = $site->photos ?? [];
        
        // Trouver et supprimer la photo
        $index = array_search($request->photo_path, $photos);
        
        if ($index !== false) {
            // Supprimer le fichier du stockage
            Storage::disk('public')->delete($request->photo_path);
            
            // Retirer de la liste
            unset($photos[$index]);
            $site->photos = array_values($photos);
            $site->save();
        }

        return redirect()->back()->with('success', 'Photo supprimée avec succès.');
    }
}
