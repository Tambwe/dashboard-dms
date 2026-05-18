<?php

namespace App\Http\Controllers;

use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\Site;
use App\Models\Province;
use App\Models\Territoire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Services\FingerprintMatcher;

class HouseholdController extends Controller
{
    /**
     * Affiche la liste des ménages
     */
    public function index(Request $request)
    {
        $query = Household::with(['site', 'enregistrePar']);

        // Filtrer par site si spécifié
        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        // Filtrer par niveau si spécifié
        if ($request->filled('niveau')) {
            $query->where('niveau_enregistrement', $request->niveau);
        }

        // Filtrer par statut si spécifié
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        // Recherche par nom du chef
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('chef_nom', 'like', "%{$search}%")
                  ->orWhere('chef_prenom', 'like', "%{$search}%")
                  ->orWhere('numero_menage', 'like', "%{$search}%");
            });
        }

        // Filtrer par accès utilisateur si non super_admin
        $user = Auth::user();
        if ($user->role !== 'super_admin') {
            $accessibleSiteIds = $this->getAccessibleSiteIds($user);
            $query->whereIn('site_id', $accessibleSiteIds);
        }

        $households = $query->latest('date_enregistrement')->paginate(20);
        $sites = $this->getAccessibleSites($user);

        return view('households.index', compact('households', 'sites'));
    }

    /**
     * Affiche la liste des ménages de Niveau 2 avec leurs membres
     */
    public function indexLevel2(Request $request)
    {
        $query = Household::with(['site', 'members', 'enregistrePar'])
            ->where('niveau_enregistrement', '2');

        // Filtrer par site si spécifié
        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        // Filtrer par statut si spécifié
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        // Recherche par nom du chef
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('chef_nom', 'like', "%{$search}%")
                  ->orWhere('chef_prenom', 'like', "%{$search}%")
                  ->orWhere('numero_menage', 'like', "%{$search}%");
            });
        }

        // Filtrer par accès utilisateur si non super_admin
        $user = Auth::user();
        if ($user->role !== 'super_admin') {
            $accessibleSiteIds = $this->getAccessibleSiteIds($user);
            $query->whereIn('site_id', $accessibleSiteIds);
        }

        $households = $query->latest('date_enregistrement')->paginate(20);
        $sites = $this->getAccessibleSites($user);

        // Calculer les statistiques
        $queryTotal = Household::where('niveau_enregistrement', '2');
        if ($user->role !== 'super_admin') {
            $accessibleSiteIds = $this->getAccessibleSiteIds($user);
            $queryTotal->whereIn('site_id', $accessibleSiteIds);
        }
        $totalMenages = $queryTotal->count();
        $totalMembres = HouseholdMember::whereIn('household_id', $queryTotal->pluck('id'))->count();

        return view('households.level2.index', compact('households', 'sites', 'totalMenages', 'totalMembres'));
    }

    /**
     * Affiche le formulaire de création (Niveau 1)
     */
    public function create()
    {
        $user = Auth::user();
        $sites = $this->getAccessibleSites($user);
        $provinces = Province::orderBy('name')->get();

        return view('households.create', compact('sites', 'provinces'));
    }

    /**
     * Enregistre un nouveau ménage (Niveau 1)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            
            // Chef de ménage
            'chef_nom' => 'required|string|max:255',
            'chef_postnom' => 'nullable|string|max:255',
            'chef_prenom' => 'nullable|string|max:255',
            'chef_sexe' => 'required|in:M,F',
            'chef_date_naissance' => 'nullable|date|before:today',
            'chef_age' => 'nullable|integer|min:0|max:150',
            'chef_lieu_naissance' => 'nullable|string|max:255',
            'chef_nationalite' => 'nullable|string|max:255',
            'chef_etat_civil' => 'nullable|string',
            'chef_telephone' => 'nullable|string|max:255',
            'chef_email' => 'nullable|email|max:255',
            'chef_type_document' => 'nullable|string',
            'chef_numero_document' => 'nullable|string|max:255',
            'chef_photo' => 'nullable|string', // Base64
            'chef_empreinte'   => 'nullable|string',
            'chef_empreinte_2' => 'nullable|string',
            'chef_empreinte_3' => 'nullable|string',

            // Origine
            'province_origine_id' => 'nullable|exists:provinces,id',
            'territoire_origine_id' => 'nullable|exists:territoires,id',
            'commune_origine' => 'nullable|string|max:255',
            'village_origine' => 'nullable|string|max:255',
            'raison_deplacement' => 'nullable|string',
            'date_arrivee_site' => 'nullable|date',
            
            // Composition (Niveau 1)
            'nombre_hommes' => 'required|integer|min:0',
            'nombre_femmes' => 'required|integer|min:0',
            'nombre_garcons' => 'required|integer|min:0',
            'nombre_filles' => 'required|integer|min:0',
            
            // Vulnérabilités
            'nombre_femmes_enceintes' => 'nullable|integer|min:0',
            'nombre_femmes_allaitantes' => 'nullable|integer|min:0',
            'nombre_personnes_handicapees' => 'nullable|integer|min:0',
            'nombre_personnes_agees' => 'nullable|integer|min:0',
            'nombre_enfants_orphelins' => 'nullable|integer|min:0',
            'nombre_enfants_separes' => 'nullable|integer|min:0',
            'nombre_malades_chroniques' => 'nullable|integer|min:0',
            
            // Conditions de vie
            'type_abri' => 'nullable|string',
            'acces_eau_potable' => 'nullable|boolean',
            'acces_latrines' => 'nullable|boolean',
            'acces_electricite' => 'nullable|boolean',
            
            // Assistance
            'recu_kits_nfi' => 'nullable|boolean',
            'recu_assistance_alimentaire' => 'nullable|boolean',
            'recu_soins_sante' => 'nullable|boolean',
            
            'observations' => 'nullable|string',
        ]);

        $validated['niveau_enregistrement'] = '1';
        $validated['enregistre_par'] = Auth::id();
        $validated['statut'] = 'actif';

        // Calculer le nombre total
        $validated['nombre_total_personnes'] =
            $validated['nombre_hommes'] +
            $validated['nombre_femmes'] +
            $validated['nombre_garcons'] +
            $validated['nombre_filles'];

        // Calculer les hashes SHA-256 pour détection de doublons
        // ET supprimer les images base64 (preview uniquement, trop volumineuses pour la DB)
        foreach ([1 => 'chef_empreinte', 2 => 'chef_empreinte_2', 3 => 'chef_empreinte_3'] as $i => $field) {
            if (!empty($validated[$field])) {
                $data = json_decode($validated[$field], true);

                // Récupérer TOUS les templates de la pose (un par doigt capturé)
                $templates = array_values(array_filter(
                    array_column($data['fingers'] ?? [], 'templateBase64')
                ));

                if (!empty($templates)) {
                    // Hash sur templates FMR_ISO (matching biométrique complet)
                    sort($templates);
                    $validated['chef_empreinte_hash_' . $i] = hash('sha256', implode('|', $templates));
                } else {
                    // Fallback : hash sur images JPG (DactyMatch absent → détection exacte uniquement)
                    $images = array_values(array_filter(
                        array_column($data['fingers'] ?? [], 'imageBase64')
                    ));
                    if (!empty($images)) {
                        sort($images);
                        $validated['chef_empreinte_hash_' . $i] = hash('sha256', implode('|', $images));
                    }
                }

                // Retirer imageBase64 de chaque doigt avant persistance (trop volumineux)
                if (isset($data['fingers']) && is_array($data['fingers'])) {
                    $data['fingers'] = array_map(function ($f) {
                        unset($f['imageBase64']);
                        return $f;
                    }, $data['fingers']);
                    $validated[$field] = json_encode($data);
                }
            }
        }

        // Générer le numéro de ménage (suffixe aléatoire 4 chiffres → collisions pratiquement impossibles)
        $validated['numero_menage'] = Household::generateNumeroMenage($validated['site_id']);

        // Sauvegarder la photo si présente
        if ($request->filled('chef_photo')) {
            $validated['chef_photo'] = $this->savePhoto($request->chef_photo, $validated['numero_menage']);
        }

        $household = Household::create($validated);

        return redirect()->route('households.show', $household)
            ->with('success', 'Ménage enregistré avec succès (Niveau 1)');
    }

    /**
     * Affiche les détails d'un ménage
     */
    public function show(Household $household)
    {
        $household->load(['site', 'provinceOrigine', 'territoireOrigine', 'enregistrePar', 'verifiePar', 'members']);

        return view('households.show', compact('household'));
    }

    /**
     * Affiche le formulaire d'édition
     */
    public function edit(Household $household)
    {
        $user = Auth::user();
        $sites = $this->getAccessibleSites($user);
        $provinces = Province::orderBy('name')->get();
        $territoires = Territoire::orderBy('name')->get();

        return view('households.edit', compact('household', 'sites', 'provinces', 'territoires'));
    }

    /**
     * Met à jour un ménage
     */
    public function update(Request $request, Household $household)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            
            // Chef de ménage - mêmes validations que store
            'chef_nom' => 'required|string|max:255',
            'chef_postnom' => 'nullable|string|max:255',
            'chef_prenom' => 'nullable|string|max:255',
            'chef_sexe' => 'required|in:M,F',
            'chef_date_naissance' => 'nullable|date|before:today',
            'chef_age' => 'nullable|integer|min:0|max:150',
            'chef_lieu_naissance' => 'nullable|string|max:255',
            'chef_nationalite' => 'nullable|string|max:255',
            'chef_etat_civil' => 'nullable|string',
            'chef_telephone' => 'nullable|string|max:255',
            'chef_email' => 'nullable|email|max:255',
            'chef_type_document' => 'nullable|string',
            'chef_numero_document' => 'nullable|string|max:255',
            
            // Origine
            'province_origine_id' => 'nullable|exists:provinces,id',
            'territoire_origine_id' => 'nullable|exists:territoires,id',
            'commune_origine' => 'nullable|string|max:255',
            'village_origine' => 'nullable|string|max:255',
            'raison_deplacement' => 'nullable|string',
            'date_arrivee_site' => 'nullable|date',
            
            // Composition
            'nombre_hommes' => 'required|integer|min:0',
            'nombre_femmes' => 'required|integer|min:0',
            'nombre_garcons' => 'required|integer|min:0',
            'nombre_filles' => 'required|integer|min:0',
            
            // Vulnérabilités
            'nombre_femmes_enceintes' => 'nullable|integer|min:0',
            'nombre_femmes_allaitantes' => 'nullable|integer|min:0',
            'nombre_personnes_handicapees' => 'nullable|integer|min:0',
            'nombre_personnes_agees' => 'nullable|integer|min:0',
            'nombre_enfants_orphelins' => 'nullable|integer|min:0',
            'nombre_enfants_separes' => 'nullable|integer|min:0',
            'nombre_malades_chroniques' => 'nullable|integer|min:0',
            
            // Conditions de vie
            'type_abri' => 'nullable|string',
            'acces_eau_potable' => 'nullable|boolean',
            'acces_latrines' => 'nullable|boolean',
            'acces_electricite' => 'nullable|boolean',
            
            // Assistance
            'recu_kits_nfi' => 'nullable|boolean',
            'recu_assistance_alimentaire' => 'nullable|boolean',
            'recu_soins_sante' => 'nullable|boolean',
            
            'statut' => 'required|string',
            'observations' => 'nullable|string',
        ]);

        // Mettre à jour la photo si fournie
        if ($request->filled('chef_photo') && $request->chef_photo !== $household->chef_photo) {
            $validated['chef_photo'] = $this->savePhoto($request->chef_photo, $household->numero_menage);
        }

        // Mettre à jour l'empreinte si fournie
        if ($request->filled('chef_empreinte')) {
            $validated['chef_empreinte'] = $request->chef_empreinte;
        }

        $household->update($validated);
        $household->updateNombreTotalPersonnes();

        return redirect()->route('households.show', $household)
            ->with('success', 'Ménage mis à jour avec succès');
    }

    /**
     * Supprime un ménage
     */
    public function destroy(Household $household)
    {
        // Vérifier les permissions
        $user = Auth::user();
        if ($user->role !== 'super_admin') {
            return redirect()->back()->with('error', 'Seul un super administrateur peut supprimer un ménage');
        }

        $household->delete();

        return redirect()->route('households.index')
            ->with('success', 'Ménage supprimé avec succès');
    }

    /**
     * Affiche le formulaire pour passer au niveau 2
     */
    public function upgradeToLevel2(Household $household)
    {
        if ($household->niveau_enregistrement === '2') {
            return redirect()->route('households.show', $household)
                ->with('info', 'Ce ménage est déjà au niveau 2');
        }

        return view('households.upgrade', compact('household'));
    }

    /**
     * Passe le ménage au niveau 2
     */
    public function processUpgradeToLevel2(Household $household)
    {
        $household->update(['niveau_enregistrement' => '2']);

        return redirect()->route('households.members.create', $household)
            ->with('success', 'Ménage passé au niveau 2. Vous pouvez maintenant enregistrer les membres');
    }

    /**
     * Affiche le formulaire d'ajout de membre (Niveau 2)
     */
    public function createMember(Household $household)
    {
        if ($household->niveau_enregistrement !== '2') {
            return redirect()->route('households.upgrade-to-level2', $household)
                ->with('info', 'Vous devez d\'abord passer le ménage au niveau 2');
        }

        return view('households.members.create', compact('household'));
    }

    /**
     * Enregistre un nouveau membre du ménage
     */
    public function storeMember(Request $request, Household $household)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'postnom' => 'nullable|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'sexe' => 'required|in:M,F',
            'date_naissance' => 'nullable|date|before:today',
            'age' => 'nullable|integer|min:0|max:150',
            'lieu_naissance' => 'nullable|string|max:255',
            'nationalite' => 'nullable|string|max:255',
            'lien_avec_chef' => 'required|string',
            'etat_civil' => 'nullable|string',
            'type_document' => 'nullable|string',
            'numero_document' => 'nullable|string|max:255',
            'photo' => 'nullable|string',
            'empreinte' => 'nullable|string',
            'niveau_education' => 'nullable|string',
            'scolarise_actuellement' => 'nullable|boolean',
            'profession' => 'nullable|string|max:255',
            'handicap' => 'nullable|boolean',
            'type_handicap' => 'nullable|string|max:255',
            'maladie_chronique' => 'nullable|boolean',
            'type_maladie' => 'nullable|string|max:255',
            'femme_enceinte' => 'nullable|boolean',
            'femme_allaitante' => 'nullable|boolean',
            'enfant_orphelin' => 'nullable|boolean',
            'enfant_separe' => 'nullable|boolean',
            'personne_agee' => 'nullable|boolean',
            'telephone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'observations' => 'nullable|string',
        ]);

        $validated['household_id'] = $household->id;
        $validated['statut'] = 'actif';

        // Sauvegarder la photo si présente
        if ($request->filled('photo')) {
            $memberNumber = $household->members()->count() + 1;
            $validated['photo'] = $this->savePhoto($request->photo, $household->numero_menage . '_M' . $memberNumber);
        }

        $member = HouseholdMember::create($validated);

        return redirect()->route('households.show', $household)
            ->with('success', 'Membre ajouté avec succès');
    }

    /**
     * Affiche le formulaire d'édition d'un membre
     */
    public function editMember(Household $household, HouseholdMember $member)
    {
        return view('households.members.edit', compact('household', 'member'));
    }

    /**
     * Met à jour un membre du ménage
     */
    public function updateMember(Request $request, Household $household, HouseholdMember $member)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'postnom' => 'nullable|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'sexe' => 'required|in:M,F',
            'date_naissance' => 'nullable|date|before:today',
            'age' => 'nullable|integer|min:0|max:150',
            'lieu_naissance' => 'nullable|string|max:255',
            'nationalite' => 'nullable|string|max:255',
            'lien_avec_chef' => 'required|string',
            'etat_civil' => 'nullable|string',
            'type_document' => 'nullable|string',
            'numero_document' => 'nullable|string|max:255',
            'niveau_education' => 'nullable|string',
            'scolarise_actuellement' => 'nullable|boolean',
            'profession' => 'nullable|string|max:255',
            'handicap' => 'nullable|boolean',
            'type_handicap' => 'nullable|string|max:255',
            'maladie_chronique' => 'nullable|boolean',
            'type_maladie' => 'nullable|string|max:255',
            'femme_enceinte' => 'nullable|boolean',
            'femme_allaitante' => 'nullable|boolean',
            'enfant_orphelin' => 'nullable|boolean',
            'enfant_separe' => 'nullable|boolean',
            'personne_agee' => 'nullable|boolean',
            'telephone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'statut' => 'required|string',
            'observations' => 'nullable|string',
        ]);

        // Mettre à jour la photo si fournie
        if ($request->filled('photo') && $request->photo !== $member->photo) {
            $validated['photo'] = $this->savePhoto($request->photo, $household->numero_menage . '_M' . $member->id);
        }

        // Mettre à jour l'empreinte si fournie
        if ($request->filled('empreinte')) {
            $validated['empreinte'] = $request->empreinte;
        }

        $member->update($validated);

        return redirect()->route('households.show', $household)
            ->with('success', 'Membre mis à jour avec succès');
    }

    /**
     * Supprime un membre du ménage
     */
    public function destroyMember(Household $household, HouseholdMember $member)
    {
        $member->delete();

        return redirect()->route('households.show', $household)
            ->with('success', 'Membre supprimé avec succès');
    }

    /**
     * Capture de photo via webcam
     */
    public function capturePhoto(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Photo capturée avec succès'
        ]);
    }

    /**
     * Capture d'empreinte digitale via lecteur Thales
     */
    public function captureFingerprint(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Empreinte capturée avec succès'
        ]);
    }

    /**
     * Affiche la page d'analyse de doublons biométriques (formulaire uniquement).
     */
    public function fingerprintDuplicates()
    {
        $total = Household::where(function ($q) {
            $q->whereNotNull('chef_empreinte')
              ->orWhereNotNull('chef_empreinte_2')
              ->orWhereNotNull('chef_empreinte_3');
        })->count();

        $comparisons = $total > 1 ? intdiv($total * ($total - 1), 2) : 0;
        $threshold        = FingerprintMatcher::MATCH_THRESHOLD;
        $dactyMissing     = false;
        $dactyNowAvailable = $this->checkGbfrswAvailable();

        return view('households.fingerprint-duplicates', compact('total', 'comparisons', 'threshold', 'dactyMissing', 'dactyNowAvailable'));
    }

    /**
     * Teste en live si le webapi Thales est accessible et si GBFRSW répond.
     * Timeout court (2 s) pour ne pas bloquer la page.
     */
    private function checkGbfrswAvailable(): bool
    {
        try {
            $ctx = stream_context_create(['http' => [
                'timeout'         => 2,
                'ignore_errors'   => true,
            ]]);
            $json = @file_get_contents('http://localhost:8090/devices', false, $ctx);
            if ($json === false) return false;
            $devices = json_decode($json, true);
            return is_array($devices) && count($devices) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Lance le scan de doublons biométriques N×N sur tous les ménages.
     *
     * Algorithme :
     *   1. Hash SHA-256 identique → score 1.0 (exact, instantané)
     *   2. Hash d'image (fallback si templates FMR_ISO absents → DactyMatch non installé)
     *   3. Matching minuties FMR_ISO (FingerprintMatcher) → score 0-1
     *
     * Complexité : O(n²) en temps, O(n) en mémoire.
     */
    public function runFingerprintDuplicates(Request $request)
    {
        set_time_limit(300); // 5 min max pour de grandes bases

        $threshold = (float) $request->input('threshold', FingerprintMatcher::MATCH_THRESHOLD);
        $threshold = max(0.10, min(1.0, $threshold));

        // ── Chargement ──────────────────────────────────────────────────────
        $households = Household::where(function ($q) {
                $q->whereNotNull('chef_empreinte')
                  ->orWhereNotNull('chef_empreinte_2')
                  ->orWhereNotNull('chef_empreinte_3');
            })
            ->select('id', 'numero_menage', 'chef_nom', 'chef_postnom', 'chef_prenom',
                     'site_id', 'chef_empreinte', 'chef_empreinte_2', 'chef_empreinte_3',
                     'chef_empreinte_hash_1', 'chef_empreinte_hash_2', 'chef_empreinte_hash_3',
                     'date_enregistrement')
            ->with('site:id,nom')
            ->get();

        // Vrai total (ménages avec au moins un champ empreinte)
        $total = $households->count();

        // ── Extraire templates + hashes image (fallback) ─────────────────────
        $items         = [];
        $dactyMissing  = false; // détecte si DactyMatch n'est pas installé

        foreach ($households as $hh) {
            $templates   = [];
            $imageHashes = [];

            foreach (['chef_empreinte', 'chef_empreinte_2', 'chef_empreinte_3'] as $field) {
                if (empty($hh->$field)) {
                    continue;
                }
                $data = json_decode($hh->$field, true);
                foreach ($data['fingers'] ?? [] as $finger) {
                    // Template FMR_ISO (besoin de DactyMatch)
                    if (!empty($finger['templateBase64'])) {
                        $templates[] = $finger['templateBase64'];
                    }
                    // Détecter l'erreur DactyMatch
                    if (!empty($finger['sdkError']) && str_contains($finger['sdkError'], 'DactyMatch')) {
                        $dactyMissing = true;
                    }
                    // Fallback : hash SHA-256 de l'imageBase64 (même capture exacte)
                    if (!empty($finger['imageBase64'])) {
                        $imageHashes[] = hash('sha256', $finger['imageBase64']);
                    }
                }
            }

            // Inclure même sans templates si on a des hashes d'image
            $storedHashes = array_values(array_filter([
                $hh->chef_empreinte_hash_1,
                $hh->chef_empreinte_hash_2,
                $hh->chef_empreinte_hash_3,
            ]));
            $allHashes = array_unique(array_merge($storedHashes, $imageHashes));

            if (!empty($templates) || !empty($allHashes)) {
                $items[] = [
                    'household' => $hh,
                    'templates' => $templates,
                    'hashes'    => $allHashes,
                ];
            }
        }

        // ── Comparaison N×N ─────────────────────────────────────────────────
        $duplicates  = [];
        $n           = count($items);
        $comparisons = $n > 1 ? intdiv($n * ($n - 1), 2) : 0;

        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $a = $items[$i];
                $b = $items[$j];

                // Test 1 : hash exact (templates SHA-256 ou image SHA-256)
                $hashMatch = !empty($a['hashes']) && !empty($b['hashes'])
                    && !empty(array_intersect($a['hashes'], $b['hashes']));

                if ($hashMatch) {
                    $duplicates[] = [
                        'a'     => $a['household'],
                        'b'     => $b['household'],
                        'score' => 1.0,
                        'exact' => true,
                    ];
                    continue;
                }

                // Test 2 : matching minuties FMR_ISO (seulement si templates disponibles)
                if (!empty($a['templates']) && !empty($b['templates'])) {
                    $score = FingerprintMatcher::bestScore($a['templates'], $b['templates'], $threshold);
                    if ($score >= $threshold) {
                        $duplicates[] = [
                            'a'     => $a['household'],
                            'b'     => $b['household'],
                            'score' => $score,
                            'exact' => false,
                        ];
                    }
                }
            }
        }

        // Tri : score le plus élevé en premier
        usort($duplicates, static fn ($x, $y) => $y['score'] <=> $x['score']);

        return view('households.fingerprint-duplicates',
            compact('duplicates', 'threshold', 'total', 'comparisons', 'dactyMissing'));
    }

    /**
     * Page dédiée de diagnostic SDK Thales (module séparé de l'enregistrement).
     */
    public function fingerprintSdkTest()
    {
        $defaults = [
            'attempts' => 10,
            'objectId' => 22,
            'timeout'  => 45,
        ];

        return view('households.fingerprint-sdk-test', [
            'config' => $defaults,
            'report' => null,
        ]);
    }

    /**
     * Exécute un test opérateur multi-captures et retourne un rapport chiffré.
     */
    public function runFingerprintSdkTest(Request $request)
    {
        $attempts = (int) $request->input('attempts', 10);
        $objectId = (int) $request->input('objectId', 22);
        $timeout  = (int) $request->input('timeout', 45);

        $attempts = max(1, min(30, $attempts));
        $objectId = in_array($objectId, [21, 22, 23], true) ? $objectId : 22;
        $timeout  = max(10, min(90, $timeout));

        $report = [
            'startedAt'      => now()->toDateTimeString(),
            'apiBase'        => 'http://localhost:8090',
            'attempts'       => $attempts,
            'objectId'       => $objectId,
            'captureTimeout' => $timeout,
            'deviceSerial'   => null,
            'summary'        => [
                'attemptHttpOk'         => 0,
                'attemptWithTemplate'   => 0,
                'attemptLowQualityOnly' => 0,
                'attemptFetchedValue'   => 0,
                'attemptTimeout'        => 0,
                'attemptOtherError'     => 0,
                'totalFingers'          => 0,
                'fingersWithTemplate'   => 0,
                'fingersLowQuality'     => 0,
                'avgCaptureMs'          => 0,
            ],
            'attemptDetails' => [],
        ];

        $devices = $this->thalesHttp('GET', '/devices', 8);
        if (!$devices['ok']) {
            $report['fatal'] = 'WebAPI Thales inaccessible sur le port 8090.';
            return view('households.fingerprint-sdk-test', [
                'config' => compact('attempts', 'objectId', 'timeout'),
                'report' => $report,
            ]);
        }

        $decodedDevices = json_decode($devices['body'], true);
        if (!is_array($decodedDevices) || empty($decodedDevices[0]['SerialNumber'])) {
            $report['fatal'] = 'Aucun scanner détecté par le WebAPI.';
            return view('households.fingerprint-sdk-test', [
                'config' => compact('attempts', 'objectId', 'timeout'),
                'report' => $report,
            ]);
        }

        $serial = (string) $decodedDevices[0]['SerialNumber'];
        $report['deviceSerial'] = $serial;

        $captureMsTotal = 0;
        for ($i = 1; $i <= $attempts; $i++) {
            $entry = [
                'attempt'          => $i,
                'activateOk'       => false,
                'httpOk'           => false,
                'captureMs'        => 0,
                'fingerCount'      => 0,
                'templateCount'    => 0,
                'lowQualityCount'  => 0,
                'fetchedValue'     => false,
                'timeout'          => false,
                'error'            => null,
            ];

            $activate = $this->thalesHttp('PUT', '/devices/' . $serial, 8);
            $entry['activateOk'] = $activate['ok'];
            if (!$activate['ok']) {
                $entry['error'] = 'Activation scanner impossible';
                $report['summary']['attemptOtherError']++;
                $report['attemptDetails'][] = $entry;
                continue;
            }

            $capture = $this->thalesHttp(
                'GET',
                '/fingerprints/' . $objectId . '?outputFormats=JPG&outputFormats=FMR_ISO',
                $timeout
            );

            $entry['captureMs'] = $capture['durationMs'];
            $captureMsTotal += $capture['durationMs'];

            if (!$capture['ok']) {
                $msg = strtolower($capture['error'] ?: $capture['body']);
                $entry['error'] = $capture['error'] ?: 'Erreur capture';
                $entry['timeout'] = str_contains($msg, 'timed out') || str_contains($msg, 'timeout');
                $entry['fetchedValue'] = str_contains($msg, 'fetchedvalue') || str_contains($msg, 'disposed');

                if ($entry['timeout']) {
                    $report['summary']['attemptTimeout']++;
                } elseif ($entry['fetchedValue']) {
                    $report['summary']['attemptFetchedValue']++;
                } else {
                    $report['summary']['attemptOtherError']++;
                }

                $report['attemptDetails'][] = $entry;
                continue;
            }

            $entry['httpOk'] = true;
            $report['summary']['attemptHttpOk']++;

            $rows = json_decode($capture['body'], true);
            if (!is_array($rows)) {
                $entry['error'] = 'Réponse non JSON';
                $report['summary']['attemptOtherError']++;
                $report['attemptDetails'][] = $entry;
                continue;
            }

            foreach ($rows as $fp) {
                $fmr = null;
                foreach (($fp['Outputs'] ?? []) as $out) {
                    if (($out['Format'] ?? null) === 'FMR_ISO') {
                        $fmr = $out;
                        break;
                    }
                }
                if (!$fmr) {
                    continue;
                }

                $entry['fingerCount']++;
                $report['summary']['totalFingers']++;

                if (!empty($fmr['Base64Data'])) {
                    $entry['templateCount']++;
                    $report['summary']['fingersWithTemplate']++;
                } else {
                    $err = strtolower((string)($fmr['ErrorMessage'] ?? ''));
                    if (str_contains($err, 'low quality')) {
                        $entry['lowQualityCount']++;
                        $report['summary']['fingersLowQuality']++;
                    }
                    if (str_contains($err, 'fetchedvalue') || str_contains($err, 'disposed')) {
                        $entry['fetchedValue'] = true;
                    }
                }
            }

            if ($entry['templateCount'] > 0) {
                $report['summary']['attemptWithTemplate']++;
            }
            if ($entry['fingerCount'] > 0 && $entry['templateCount'] === 0 && $entry['lowQualityCount'] > 0) {
                $report['summary']['attemptLowQualityOnly']++;
            }
            if ($entry['fetchedValue']) {
                $report['summary']['attemptFetchedValue']++;
            }

            $report['attemptDetails'][] = $entry;
        }

        if ($attempts > 0) {
            $report['summary']['avgCaptureMs'] = (int) round($captureMsTotal / $attempts);
        }

        $report['rates'] = [
            'attemptSuccessRate'      => $attempts > 0 ? round($report['summary']['attemptWithTemplate'] * 100 / $attempts, 2) : 0,
            'attemptHttpOkRate'       => $attempts > 0 ? round($report['summary']['attemptHttpOk'] * 100 / $attempts, 2) : 0,
            'fingerTemplateRate'      => $report['summary']['totalFingers'] > 0
                ? round($report['summary']['fingersWithTemplate'] * 100 / $report['summary']['totalFingers'], 2)
                : 0,
            'fingerLowQualityRate'    => $report['summary']['totalFingers'] > 0
                ? round($report['summary']['fingersLowQuality'] * 100 / $report['summary']['totalFingers'], 2)
                : 0,
            'timeoutRate'             => $attempts > 0 ? round($report['summary']['attemptTimeout'] * 100 / $attempts, 2) : 0,
            'fetchedValueRate'        => $attempts > 0 ? round($report['summary']['attemptFetchedValue'] * 100 / $attempts, 2) : 0,
        ];

        return view('households.fingerprint-sdk-test', [
            'config' => compact('attempts', 'objectId', 'timeout'),
            'report' => $report,
        ]);
    }

    /**
     * Appel HTTP simple au WebAPI Thales avec durée d'exécution.
     *
     * @return array{ok:bool,status:int,body:string,error:?string,durationMs:int}
     */

    /**
     * Endpoint AJAX : exécute une seule capture et retourne le résultat JSON.
     */
    public function sdkTestCapture(Request $request): \Illuminate\Http\JsonResponse
    {
        $objectId = (int) $request->input('objectId', 22);
        $timeout  = (int) $request->input('timeout', 45);
        $objectId = in_array($objectId, [21, 22, 23], true) ? $objectId : 22;
        $timeout  = max(10, min(90, $timeout));

        set_time_limit(0);

        // 1. Récupérer le scanner
        $devices = $this->thalesHttp('GET', '/devices', 8);
        if (!$devices['ok']) {
            return response()->json(['error' => 'WebAPI inaccessible', 'fatal' => true]);
        }
        $decodedDevices = json_decode($devices['body'], true);
        if (!is_array($decodedDevices) || empty($decodedDevices[0]['SerialNumber'])) {
            return response()->json(['error' => 'Aucun scanner détecté', 'fatal' => true]);
        }
        $serial = (string) $decodedDevices[0]['SerialNumber'];

        // 2. Activer le scanner
        $activate = $this->thalesHttp('PUT', '/devices/' . $serial, 8);
        if (!$activate['ok']) {
            return response()->json([
                'activateOk' => false,
                'httpOk'     => false,
                'captureMs'  => 0,
                'fingerCount'     => 0,
                'templateCount'   => 0,
                'lowQualityCount' => 0,
                'fetchedValue'    => false,
                'timeout'         => false,
                'error'           => 'Activation scanner impossible',
                'serial'          => $serial,
            ]);
        }

        // 3. Capturer
        $capture = $this->thalesHttp(
            'GET',
            '/fingerprints/' . $objectId . '?outputFormats=JPG&outputFormats=FMR_ISO',
            $timeout
        );

        $result = [
            'activateOk'      => true,
            'httpOk'          => false,
            'captureMs'       => $capture['durationMs'],
            'fingerCount'     => 0,
            'templateCount'   => 0,
            'lowQualityCount' => 0,
            'fetchedValue'    => false,
            'timeout'         => false,
            'error'           => null,
            'serial'          => $serial,
        ];

        if (!$capture['ok']) {
            $msg = strtolower($capture['error'] ?: $capture['body']);
            $result['error']        = $capture['error'] ?: 'Erreur capture';
            $result['timeout']      = str_contains($msg, 'timed out') || str_contains($msg, 'timeout');
            $result['fetchedValue'] = str_contains($msg, 'fetchedvalue') || str_contains($msg, 'disposed');
            return response()->json($result);
        }

        $result['httpOk'] = true;
        $rows = json_decode($capture['body'], true);
        if (!is_array($rows)) {
            $result['error'] = 'Réponse non JSON';
            return response()->json($result);
        }

        foreach ($rows as $fp) {
            $fmr = null;
            foreach (($fp['Outputs'] ?? []) as $out) {
                if (($out['Format'] ?? null) === 'FMR_ISO') {
                    $fmr = $out;
                    break;
                }
            }
            if (!$fmr) continue;

            $result['fingerCount']++;
            if (!empty($fmr['Base64Data'])) {
                $result['templateCount']++;
            } else {
                $err = strtolower((string)($fmr['ErrorMessage'] ?? ''));
                if (str_contains($err, 'low quality')) {
                    $result['lowQualityCount']++;
                }
                if (str_contains($err, 'fetchedvalue') || str_contains($err, 'disposed')) {
                    $result['fetchedValue'] = true;
                }
            }
        }

        return response()->json($result);
    }

    private function thalesHttp(string $method, string $path, int $timeout): array
    {
        $url = 'http://localhost:8090' . $path;
        $start = microtime(true);

        $headers = [
            'Accept: application/json',
            'Connection: close',
        ];

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => $method,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_CONNECTTIMEOUT => min(8, $timeout),
                CURLOPT_TIMEOUT        => $timeout,
            ]);

            $body = curl_exec($ch);
            $errno = curl_errno($ch);
            $err = $errno ? curl_error($ch) : null;
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            return [
                'ok'         => $errno === 0 && $status >= 200 && $status < 300,
                'status'     => $status,
                'body'       => is_string($body) ? $body : '',
                'error'      => $err,
                'durationMs' => (int) round((microtime(true) - $start) * 1000),
            ];
        }

        $ctx = stream_context_create([
            'http' => [
                'method'        => $method,
                'timeout'       => $timeout,
                'ignore_errors' => true,
                'header'        => implode("\r\n", $headers),
            ],
        ]);

        try {
            $body = @file_get_contents($url, false, $ctx);
            $status = 0;
            foreach ($http_response_header ?? [] as $line) {
                if (preg_match('#HTTP/\S+\s+(\d{3})#', $line, $m)) {
                    $status = (int) $m[1];
                    break;
                }
            }

            return [
                'ok'         => $body !== false && $status >= 200 && $status < 300,
                'status'     => $status,
                'body'       => $body !== false ? $body : '',
                'error'      => $body === false ? 'HTTP request failed' : null,
                'durationMs' => (int) round((microtime(true) - $start) * 1000),
            ];
        } catch (\Throwable $e) {
            return [
                'ok'         => false,
                'status'     => 0,
                'body'       => '',
                'error'      => $e->getMessage(),
                'durationMs' => (int) round((microtime(true) - $start) * 1000),
            ];
        }
    }

    /**
     * Vérifie si une empreinte existe déjà en base via matching biométrique réel.
     *
     * Étape 1 : comparaison par hash SHA-256 (captures identiques bit-à-bit) → O(1)
     * Étape 2 : matching par minuties FMR_ISO (mêmes doigts, captures différentes) → O(n)
     *
     * Le matching minuties utilise FingerprintMatcher::bestScore() qui implémente
     * un algorithme de proximité spatial + angulaire sur les minuties ISO 19794-2.
     * Seuil par défaut : 35 % de minuties correspondantes.
     */
    public function checkFingerprintDuplicate(Request $request)
    {
        $request->validate([
            'templates'   => 'nullable|array',
            'templates.*' => 'nullable|string',
            'images'      => 'nullable|array',
            'images.*'    => 'nullable|string',
        ]);

        $newTemplates = array_values(array_filter($request->templates));
        $newImages = array_values(array_filter($request->images));

        if (empty($newTemplates) && empty($newImages)) {
            return response()->json([
                'duplicate' => false,
                'reason'    => 'no-biometrics',
            ]);
        }

        // ── Étape 1 : hash exact (même capture) ──────────────────────────────
        // Priorité au template FMR_ISO si présent, sinon fallback JPG.
        $sorted = !empty($newTemplates) ? $newTemplates : $newImages;
        sort($sorted);
        $hash = hash('sha256', implode('|', $sorted));

        $hashMatch = Household::where(function ($q) use ($hash) {
                $q->where('chef_empreinte_hash_1', $hash)
                  ->orWhere('chef_empreinte_hash_2', $hash)
                  ->orWhere('chef_empreinte_hash_3', $hash);
            })
            ->select('id', 'numero_menage', 'chef_nom', 'chef_postnom', 'chef_prenom', 'site_id')
            ->with('site:id,nom')
            ->first();

        if ($hashMatch) {
            return $this->duplicateResponse($hashMatch, 1.0);
        }

        // Sans template FMR_ISO, on ne peut pas faire de matching biométrique avancé.
        // Le mode JPG est un fallback en détection exacte uniquement.
        if (empty($newTemplates)) {
            return response()->json([
                'duplicate' => false,
                'reason'    => 'jpg-exact-only-no-match',
            ]);
        }

        // ── Étape 2 : matching biométrique par minuties FMR_ISO ──────────────
        // Charge tous les ménages ayant au moins une pose enregistrée.
        // Pour de très grandes bases (> 50 000 ménages), envisager un AFIS dédié.
        $households = Household::where(function ($q) {
                $q->whereNotNull('chef_empreinte')
                  ->orWhereNotNull('chef_empreinte_2')
                  ->orWhereNotNull('chef_empreinte_3');
            })
            ->select('id', 'numero_menage', 'chef_nom', 'chef_postnom', 'chef_prenom',
                     'site_id', 'chef_empreinte', 'chef_empreinte_2', 'chef_empreinte_3')
            ->with('site:id,nom')
            ->get();

        $threshold = FingerprintMatcher::MATCH_THRESHOLD;

        foreach ($households as $hh) {
            foreach (['chef_empreinte', 'chef_empreinte_2', 'chef_empreinte_3'] as $poseField) {
                if (empty($hh->$poseField)) {
                    continue;
                }

                $data = json_decode($hh->$poseField, true);
                $storedTemplates = array_values(array_filter(
                    array_column($data['fingers'] ?? [], 'templateBase64')
                ));

                if (empty($storedTemplates)) {
                    continue;
                }

                $score = FingerprintMatcher::bestScore($newTemplates, $storedTemplates, $threshold);
                if ($score >= $threshold) {
                    return $this->duplicateResponse($hh, $score);
                }
            }
        }

        return response()->json(['duplicate' => false]);
    }

    /**
     * Formate la réponse JSON pour un doublon trouvé.
     */
    private function duplicateResponse($household, float $score): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'duplicate' => true,
            'score'     => round($score * 100),
            'household' => [
                'id'            => $household->id,
                'numero_menage' => $household->numero_menage,
                'nom_complet'   => trim($household->chef_nom . ' ' . $household->chef_postnom . ' ' . $household->chef_prenom),
                'site'          => $household->site->nom ?? '—',
            ],
        ]);
    }

    /**
     * Obtient les sites accessibles par l'utilisateur
     */
    private function getAccessibleSites($user)
    {
        if ($user->role === 'super_admin') {
            return Site::orderBy('nom')->get();
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
     * Obtient les IDs des sites accessibles
     */
    private function getAccessibleSiteIds($user)
    {
        return $this->getAccessibleSites($user)->pluck('id')->toArray();
    }

    /**
     * Sauvegarde une photo en base64
     */
    private function savePhoto($base64Photo, $identifier)
    {
        // Si c'est déjà un chemin, le retourner
        if (!str_starts_with($base64Photo, 'data:image')) {
            return $base64Photo;
        }

        // Extraire les données de l'image
        $image = str_replace('data:image/png;base64,', '', $base64Photo);
        $image = str_replace('data:image/jpeg;base64,', '', $image);
        $image = str_replace(' ', '+', $image);
        $imageName = 'household_' . $identifier . '_' . time() . '.png';
        
        // Sauvegarder dans storage/app/public/households
        Storage::disk('public')->put('households/' . $imageName, base64_decode($image));
        
        return 'households/' . $imageName;
    }
}
