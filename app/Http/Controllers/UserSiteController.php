<?php

namespace App\Http\Controllers;

use App\Models\OssatReport;
use App\Models\Site;
use App\Models\SiteMouvementPopulation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserSiteController extends Controller
{
    /**
     * Display a listing of sites the user has access to.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Si l'utilisateur n'a pas d'organisation et n'est pas super admin
        if (!$user->organisation_id && !$user->isSuperAdmin()) {
            // Récupérer uniquement les sites assignés
            $query = $user->assignedSites()->with(['typeSite', 'commune', 'categorieSite', 'organisation']);
        } else {
            // Sites de l'organisation OU sites assignés
            $organisationSites = $user->organisation_id 
                ? Site::where('organisation_id', $user->organisation_id) 
                : collect();
            
            $assignedSiteIds = $user->assignedSites()->pluck('sites.id');
            
            $query = Site::query()
                ->where(function($q) use ($user, $assignedSiteIds) {
                    if ($user->organisation_id) {
                        $q->where('organisation_id', $user->organisation_id);
                    }
                    if ($assignedSiteIds->isNotEmpty()) {
                        $q->orWhereIn('id', $assignedSiteIds);
                    }
                })
                ->with(['typeSite', 'commune', 'categorieSite', 'organisation']);
        }

        // Recherche
        if ($request->filled('search')) {
            $query->where('nom', 'like', '%' . $request->search . '%');
        }

        $sites = $query->paginate(20);

        return view('user.sites.index', compact('sites'));
    }

    /**
     * Show the form for editing the specified site.
     */
    public function edit(Site $site)
    {
        $user = auth()->user();
        
        // Vérifier l'accès
        if (!$user->hasAccessToSite($site)) {
            abort(403, 'Vous n\'avez pas accès à ce site.');
        }

        // Charger les permissions spécifiques
        $access = $user->assignedSites()->where('sites.id', $site->id)->first();
        $canEdit = $user->canEditSite($site);

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

        return view('user.sites.edit', compact('site', 'canEdit', 'access', 'ossatReport', 'populationMouvement'));
    }

    /**
     * Update the specified site in storage.
     */
    public function update(Request $request, Site $site)
    {
        $user = auth()->user();
        
        // Vérifier l'accès et la permission d'édition
        if (!$user->canEditSite($site)) {
            abort(403, 'Vous n\'avez pas la permission de modifier ce site.');
        }

        $validated = $request->validate([
            'longitude' => 'nullable|numeric|between:-180,180',
            'latitude' => 'nullable|numeric|between:-90,90',
            'geojson_data' => 'nullable|json',
            'photos.*' => 'nullable|image|max:5120',
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

        return redirect()->route('user.sites.edit', $site)
            ->with('success', 'Site mis à jour avec succès.');
    }

    /**
     * Delete a photo from the site.
     */
    public function deletePhoto(Request $request, Site $site)
    {
        $user = auth()->user();
        
        // Vérifier l'accès et la permission d'édition
        if (!$user->canEditSite($site)) {
            abort(403, 'Vous n\'avez pas la permission de modifier ce site.');
        }

        $request->validate([
            'photo_path' => 'required|string',
        ]);

        $photos = $site->photos ?? [];
        
        $index = array_search($request->photo_path, $photos);
        
        if ($index !== false) {
            Storage::disk('public')->delete($request->photo_path);
            unset($photos[$index]);
            $site->photos = array_values($photos);
            $site->save();
        }

        return redirect()->back()->with('success', 'Photo supprimée avec succès.');
    }
}
