<?php

namespace App\Http\Controllers;

use App\Models\OssatReport;
use App\Models\OssatChoix;
use App\Models\Province;
use App\Models\Territoire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OssatReportController extends Controller
{
    /**
     * Charge les listes de choix depuis la base de données (avec cache).
     * Remplace l'ancien tableau statique $choices.
     */
    private function getChoices(): array
    {
        return OssatChoix::allGrouped();
    }

    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = OssatReport::with(['province', 'territoire', 'createdBy'])
            ->orderByDesc('created_at');

        if (!$user->isSuperAdmin()) {
            // Récupérer les IDs des sites accessibles à l'utilisateur
            $siteIds = collect();

            if ($user->organisation_id) {
                // Sites directement attribués à l'organisation
                $orgSiteIds = \App\Models\Site::where('organisation_id', $user->organisation_id)
                    ->pluck('id');
                $siteIds = $siteIds->merge($orgSiteIds);

                // Sites attribués à des utilisateurs de l'organisation
                $orgUserIds = \App\Models\User::where('organisation_id', $user->organisation_id)->pluck('id');
                $accessSiteIds = DB::table('site_user_access')
                    ->whereIn('user_id', $orgUserIds)
                    ->pluck('site_id');
                $siteIds = $siteIds->merge($accessSiteIds);
            } else {
                // Utilisateur sans organisation : seulement ses propres sites assignés
                $accessSiteIds = DB::table('site_user_access')
                    ->where('user_id', $user->id)
                    ->pluck('site_id');
                $siteIds = $siteIds->merge($accessSiteIds);
            }

            if ($siteIds->isNotEmpty()) {
                $query->whereIn('site_id', $siteIds->unique());
            } else {
                // Aucun site accessible : montrer uniquement les rapports créés par l'utilisateur
                $query->where('created_by', $user->id);
            }
        }

        $reports = $query->paginate(20);

        return view('ossat.index', compact('reports'));
    }

    public function create(Request $request)
    {
        $provinces = Province::orderBy('name')->get();
        $territoires = Territoire::orderBy('name')->get();
        $choices = $this->getChoices();
        $preselectedSite = $request->filled('site_id')
            ? \App\Models\Site::with('commune.territoire.province')->find($request->site_id)
            : null;

        return view('ossat.create', compact('provinces', 'territoires', 'choices', 'preselectedSite'));
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $this->prepareCheckboxes($data);
        $this->prepareMultiselects($data);

        if (!empty($data['site_id'])) {
            $site = \App\Models\Site::find($data['site_id']);
            if ($site) {
                $data['site_nom'] = $site->nom;
                $data['site_code'] = $site->code_site;
            }
        }

        $data['created_by'] = Auth::id();
        $data['statut_validation'] = $request->input('action') === 'soumettre' ? 'soumis' : 'brouillon';

        $report = OssatReport::create($data);

        $msg = $data['statut_validation'] === 'soumis' ? 'Rapport soumis avec succès.' : 'Brouillon sauvegardé.';

        return redirect()->route('ossat.show', $report)->with('success', $msg);
    }

    public function show(OssatReport $ossat)
    {
        $ossat->load(['province', 'territoire', 'site', 'createdBy', 'validePar']);

        return view('ossat.show', compact('ossat'));
    }

    public function edit(OssatReport $ossat)
    {
        $provinces = Province::orderBy('name')->get();
        $territoires = Territoire::orderBy('name')->get();
        $choices = $this->getChoices();

        return view('ossat.edit', compact('ossat', 'provinces', 'territoires', 'choices'));
    }

    public function update(Request $request, OssatReport $ossat)
    {
        $data = $request->all();
        $this->prepareCheckboxes($data);
        $this->prepareMultiselects($data);

        if (!empty($data['site_id'])) {
            $site = \App\Models\Site::find($data['site_id']);
            if ($site) {
                $data['site_nom'] = $site->nom;
                $data['site_code'] = $site->code_site;
            }
        }

        $data['statut_validation'] = $request->input('action') === 'soumettre' ? 'soumis' : 'brouillon';

        $ossat->update($data);

        return redirect()->route('ossat.show', $ossat)->with('success', 'Rapport mis à jour.');
    }

    public function destroy(OssatReport $ossat)
    {
        $ossat->delete();

        return redirect()->route('ossat.index')->with('success', 'Rapport supprimé.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function prepareCheckboxes(array &$data): void
    {
        $booleans = [
            'fait_partie_agence','nouveau_site','agence_gestion','gestionnaire_dedie',
            'gestionnaire_accepte_partage','agence_admin','admin_dedie','admin_accepte_partage',
            'agence_coord','bureau_dedie','presence_comite','comites_elus','reunions_coordination',
            'equipe_mobile_soutien','cci','mgp','pdi_nouvelles_arrivees','pdi_retours',
            'reduction_prevue','mesures_incendie','eclairage_existant','qualite_eau',
            'defecation_plein_air','savon_disponible','inondations_6mois','douches_separees',
            'latrines_vidangees','eclairage_latrines','wash_adapte_handicapes',
            'soin_sante_fonctionnel','soin_sante_interieur','services_urgences',
            'services_chirurgicaux','services_pediatriques','services_prenataux','ambulance',
            'stockage_magasin','restrictions_mouvement','sentiment_securite','services_handicapes',
            'acces_tribunaux','ecole_primaire_presente','ecole_secondaire_presente',
            'education_informelle','marche_interieur','enclos_betail',
            'partenaires_protection_presence','partenaires_gbv_presence',
            'partenaires_enfance_presence','partenaires_education_presence',
            'partenaires_abri_presence','partenaires_eau_presence',
            'partenaires_assainissement_presence','partenaires_dechets_presence',
            'partenaires_sante_primaire_presence','partenaires_sante_secondaire_presence',
            'partenaires_mhpss_presence','partenaires_nutrition_presence',
            'partenaires_alimentaire_presence','partenaires_cohesion_presence',
            'partenaires_subsistance_presence','partenaires_communication_presence',
        ];

        foreach ($booleans as $field) {
            $data[$field] = isset($data[$field]) && $data[$field] ? true : false;
        }
    }

    private function prepareMultiselects(array &$data): void
    {
        $multiselects = [
            'comites','equipe_mobile','info_source','raisons_retours','types_abri',
            'ame_prioritaires','ame_harmattan','ame_saison_seche','strategies_ame',
            'sources_electricite','sources_eau','types_latrines','types_douches',
            'problemes_sante','problemes_acces_sante','defis_alimentation',
            'types_restrictions','acteurs_incidents','menaces_site',
            'zones_dangereuses_femmes','zones_dangereuses_hommes','types_support_psy',
            'obstacles_education','articles_non_disponibles','sources_subsistance',
        ];

        foreach ($multiselects as $field) {
            if (!isset($data[$field]) || !is_array($data[$field])) {
                $data[$field] = [];
            }
        }
    }
}
