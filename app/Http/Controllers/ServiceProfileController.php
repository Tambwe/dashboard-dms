<?php

namespace App\Http\Controllers;

use App\Models\ServiceProfile;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceProfileController extends Controller
{
    /**
     * Afficher la liste des profils de services
     */
    public function index()
    {
        $profiles = ServiceProfile::with(['site', 'collecteur'])
            ->when(Auth::user()->role !== 'super_admin', function($query) {
                // Les utilisateurs non super admin ne voient que leurs propres collectes
                // ou celles des sites de leur organisation
                $query->where('collecteur_id', Auth::id())
                    ->orWhereHas('site', function($q) {
                        $q->where('organisation_id', Auth::user()->organisation_id);
                    });
            })
            ->orderBy('date_collecte', 'desc')
            ->paginate(20);

        return view('service-profiles.index', compact('profiles'));
    }

    /**
     * Afficher le formulaire de création
     */
    public function create(Request $request)
    {
        // Récupérer les sites accessibles par l'utilisateur
        $sites = $this->getAccessibleSites();
        
        // Si un site est pré-sélectionné
        $selectedSite = null;
        if ($request->has('site_id')) {
            $selectedSite = Site::find($request->site_id);
        }

        return view('service-profiles.create', compact('sites', 'selectedSite'));
    }

    /**
     * Enregistrer un nouveau profil de services
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'date_collecte' => 'required|date',
            
            // Santé
            'sante_disponible' => 'boolean',
            'sante_structures_fonctionnelles' => 'nullable|integer|min:0',
            'sante_personnel_medical' => 'nullable|integer|min:0',
            'sante_services_offerts' => 'nullable|array',
            'sante_consultations_mois' => 'nullable|integer|min:0',
            'sante_observations' => 'nullable|string',
            
            // Éducation
            'education_disponible' => 'boolean',
            'education_ecoles_fonctionnelles' => 'nullable|integer|min:0',
            'education_enseignants' => 'nullable|integer|min:0',
            'education_eleves_inscrits' => 'nullable|integer|min:0',
            'education_salles_classe' => 'nullable|integer|min:0',
            'education_niveaux_offerts' => 'nullable|array',
            'education_observations' => 'nullable|string',
            
            // WASH
            'wash_disponible' => 'boolean',
            'wash_points_eau' => 'nullable|integer|min:0',
            'wash_litres_par_personne' => 'nullable|numeric|min:0',
            'wash_latrines' => 'nullable|integer|min:0',
            'wash_douches' => 'nullable|integer|min:0',
            'wash_gestion_dechets' => 'boolean',
            'wash_observations' => 'nullable|string',
            
            // Environnement
            'environnement_disponible' => 'boolean',
            'environnement_gestion_dechets' => 'boolean',
            'environnement_drainage' => 'boolean',
            'environnement_espaces_verts' => 'boolean',
            'environnement_risques' => 'nullable|array',
            'environnement_observations' => 'nullable|string',
            
            // Abri et AME
            'abri_ame_disponible' => 'boolean',
            'abri_logements_fonctionnels' => 'nullable|integer|min:0',
            'abri_types' => 'nullable|array',
            'abri_menages_ame' => 'nullable|integer|min:0',
            'abri_ame_distribues' => 'nullable|array',
            'abri_observations' => 'nullable|string',
            
            // Gestion et coordination
            'gestion_disponible' => 'boolean',
            'gestion_comite_site' => 'boolean',
            'gestion_membres_comite' => 'nullable|integer|min:0',
            'gestion_mecanisme_plainte' => 'boolean',
            'gestion_reunions_mois' => 'nullable|integer|min:0',
            'gestion_partenaires' => 'nullable|array',
            'gestion_observations' => 'nullable|string',
            
            // Métadonnées
            'notes_generales' => 'nullable|string',
        ]);

        // Vérifier l'accès au site
        if (!$this->userCanAccessSite($request->site_id)) {
            return back()->with('error', 'Vous n\'avez pas accès à ce site.');
        }

        $validated['collecteur_id'] = Auth::id();
        $validated['statut'] = 'brouillon';

        $profile = ServiceProfile::create($validated);

        return redirect()->route('service-profiles.show', $profile)
            ->with('success', 'Profil de services créé avec succès.');
    }

    /**
     * Afficher un profil de services
     */
    public function show(ServiceProfile $serviceProfile)
    {
        $serviceProfile->load(['site', 'collecteur']);
        
        // Vérifier l'accès
        if (!$this->userCanViewProfile($serviceProfile)) {
            abort(403, 'Vous n\'avez pas accès à ce profil.');
        }

        return view('service-profiles.show', compact('serviceProfile'));
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(ServiceProfile $serviceProfile)
    {
        // Vérifier l'accès
        if (!$this->userCanEditProfile($serviceProfile)) {
            abort(403, 'Vous ne pouvez pas modifier ce profil.');
        }

        $sites = $this->getAccessibleSites();

        return view('service-profiles.edit', compact('serviceProfile', 'sites'));
    }

    /**
     * Mettre à jour un profil de services
     */
    public function update(Request $request, ServiceProfile $serviceProfile)
    {
        // Vérifier l'accès
        if (!$this->userCanEditProfile($serviceProfile)) {
            abort(403, 'Vous ne pouvez pas modifier ce profil.');
        }

        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'date_collecte' => 'required|date',
            
            // Santé
            'sante_disponible' => 'boolean',
            'sante_structures_fonctionnelles' => 'nullable|integer|min:0',
            'sante_personnel_medical' => 'nullable|integer|min:0',
            'sante_services_offerts' => 'nullable|array',
            'sante_consultations_mois' => 'nullable|integer|min:0',
            'sante_observations' => 'nullable|string',
            
            // Éducation
            'education_disponible' => 'boolean',
            'education_ecoles_fonctionnelles' => 'nullable|integer|min:0',
            'education_enseignants' => 'nullable|integer|min:0',
            'education_eleves_inscrits' => 'nullable|integer|min:0',
            'education_salles_classe' => 'nullable|integer|min:0',
            'education_niveaux_offerts' => 'nullable|array',
            'education_observations' => 'nullable|string',
            
            // WASH
            'wash_disponible' => 'boolean',
            'wash_points_eau' => 'nullable|integer|min:0',
            'wash_litres_par_personne' => 'nullable|numeric|min:0',
            'wash_latrines' => 'nullable|integer|min:0',
            'wash_douches' => 'nullable|integer|min:0',
            'wash_gestion_dechets' => 'boolean',
            'wash_observations' => 'nullable|string',
            
            // Environnement
            'environnement_disponible' => 'boolean',
            'environnement_gestion_dechets' => 'boolean',
            'environnement_drainage' => 'boolean',
            'environnement_espaces_verts' => 'boolean',
            'environnement_risques' => 'nullable|array',
            'environnement_observations' => 'nullable|string',
            
            // Abri et AME
            'abri_ame_disponible' => 'boolean',
            'abri_logements_fonctionnels' => 'nullable|integer|min:0',
            'abri_types' => 'nullable|array',
            'abri_menages_ame' => 'nullable|integer|min:0',
            'abri_ame_distribues' => 'nullable|array',
            'abri_observations' => 'nullable|string',
            
            // Gestion et coordination
            'gestion_disponible' => 'boolean',
            'gestion_comite_site' => 'boolean',
            'gestion_membres_comite' => 'nullable|integer|min:0',
            'gestion_mecanisme_plainte' => 'boolean',
            'gestion_reunions_mois' => 'nullable|integer|min:0',
            'gestion_partenaires' => 'nullable|array',
            'gestion_observations' => 'nullable|string',
            
            // Métadonnées
            'notes_generales' => 'nullable|string',
        ]);

        $serviceProfile->update($validated);

        return redirect()->route('service-profiles.show', $serviceProfile)
            ->with('success', 'Profil de services mis à jour avec succès.');
    }

    /**
     * Soumettre un profil pour validation
     */
    public function submit(ServiceProfile $serviceProfile)
    {
        if (!$this->userCanEditProfile($serviceProfile)) {
            abort(403, 'Vous ne pouvez pas soumettre ce profil.');
        }

        if ($serviceProfile->statut !== 'brouillon') {
            return back()->with('error', 'Seuls les brouillons peuvent être soumis.');
        }

        $serviceProfile->update(['statut' => 'soumis']);

        return back()->with('success', 'Profil soumis pour validation avec succès.');
    }

    /**
     * Valider un profil (super admin uniquement)
     */
    public function approve(ServiceProfile $serviceProfile)
    {
        if (Auth::user()->role !== 'super_admin') {
            abort(403, 'Action non autorisée.');
        }

        if ($serviceProfile->statut !== 'soumis') {
            return back()->with('error', 'Seuls les profils soumis peuvent être validés.');
        }

        $serviceProfile->update(['statut' => 'valide']);

        return back()->with('success', 'Profil validé avec succès.');
    }

    /**
     * Rejeter un profil (super admin uniquement)
     */
    public function reject(Request $request, ServiceProfile $serviceProfile)
    {
        if (Auth::user()->role !== 'super_admin') {
            abort(403, 'Action non autorisée.');
        }

        if ($serviceProfile->statut !== 'soumis') {
            return back()->with('error', 'Seuls les profils soumis peuvent être rejetés.');
        }

        $serviceProfile->update([
            'statut' => 'rejete',
            'notes_generales' => $request->input('raison_rejet', $serviceProfile->notes_generales)
        ]);

        return back()->with('success', 'Profil rejeté.');
    }

    /**
     * Supprimer un profil
     */
    public function destroy(ServiceProfile $serviceProfile)
    {
        if (!$this->userCanEditProfile($serviceProfile)) {
            abort(403, 'Vous ne pouvez pas supprimer ce profil.');
        }

        $serviceProfile->delete();

        return redirect()->route('service-profiles.index')
            ->with('success', 'Profil supprimé avec succès.');
    }

    /**
     * Récupérer les sites accessibles par l'utilisateur
     */
    private function getAccessibleSites()
    {
        $user = Auth::user();
        
        if ($user->role === 'super_admin') {
            return Site::with(['commune', 'organisation'])
                ->orderBy('nom')
                ->get();
        }
        
        // Sites de l'organisation + sites assignés individuellement
        return Site::with(['commune', 'organisation'])
            ->where(function($query) use ($user) {
                $query->where('organisation_id', $user->organisation_id)
                    ->orWhereHas('assignedUsers', function($q) use ($user) {
                        $q->where('users.id', $user->id)
                          ->where('site_user_access.can_collect', true);
                    });
            })
            ->orderBy('nom')
            ->get();
    }

    /**
     * Vérifier si l'utilisateur peut accéder à un site
     */
    private function userCanAccessSite($siteId)
    {
        $user = Auth::user();
        
        if ($user->role === 'super_admin') {
            return true;
        }
        
        $site = Site::find($siteId);
        if (!$site) {
            return false;
        }
        
        // Vérifier si l'utilisateur appartient à l'organisation du site
        if ($site->organisation_id === $user->organisation_id) {
            return true;
        }
        
        // Vérifier si l'utilisateur a un accès individuel avec permission de collecte
        return $site->assignedUsers()
            ->where('users.id', $user->id)
            ->where('site_user_access.can_collect', true)
            ->exists();
    }

    /**
     * Vérifier si l'utilisateur peut voir un profil
     */
    private function userCanViewProfile(ServiceProfile $profile)
    {
        $user = Auth::user();
        
        if ($user->role === 'super_admin') {
            return true;
        }
        
        // Le collecteur peut voir son propre profil
        if ($profile->collecteur_id === $user->id) {
            return true;
        }
        
        // Les membres de l'organisation peuvent voir les profils de leur organisation
        if ($profile->site->organisation_id === $user->organisation_id) {
            return true;
        }
        
        return false;
    }

    /**
     * Vérifier si l'utilisateur peut modifier un profil
     */
    private function userCanEditProfile(ServiceProfile $profile)
    {
        $user = Auth::user();
        
        // Super admin peut tout modifier
        if ($user->role === 'super_admin') {
            return true;
        }
        
        // Seul le collecteur peut modifier son profil (et uniquement en brouillon)
        if ($profile->collecteur_id === $user->id && $profile->statut === 'brouillon') {
            return true;
        }
        
        return false;
    }
}
