<?php

namespace App\Http\Controllers;

use App\Models\Province;
use App\Models\MobileQuestionnaire;
use App\Models\MobileQuestionnaireSubmission;
use App\Models\ProfileQuestionPreference;
use App\Models\ServiceProfile;
use App\Models\Site;
use App\Services\QuestionnaireProfileService;
use App\Services\HumanitarianStandardComparisonService;
use Illuminate\Support\Carbon;
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

    public function show(
        Request $request,
        Site $site,
        QuestionnaireProfileService $questionnaireProfiles,
        HumanitarianStandardComparisonService $standardComparisons
    )
    {
        $request->validate([
            'periode' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $provinces = Province::orderBy('name')->get();
        $site->load(['commune.territoire.province', 'organisation', 'typeSite']);

        $profilesQuery = ServiceProfile::query()
            ->where('site_id', $site->id)
            ->whereIn('statut', ['soumis', 'valide']);

        $legacyPeriods = (clone $profilesQuery)
            ->select('date_collecte')
            ->distinct()
            ->orderByDesc('date_collecte')
            ->get()
            ->pluck('date_collecte');

        $questionnaire = null;
        $questionnairePeriods = collect();
        if (Schema::hasTable('mobile_questionnaires') && Schema::hasTable('mobile_questionnaire_submissions')) {
            $questionnaire = MobileQuestionnaire::query()
                ->where('code', 'service-cartography')
                ->orderByDesc('version')
                ->first();

            if ($questionnaire) {
                $questionnairePeriods = MobileQuestionnaireSubmission::query()
                    ->where('site_id', $site->id)
                    ->whereHas('questionnaire', fn ($query) => $query->where('code', 'service-cartography'))
                    ->whereNotNull('synced_at')
                    ->pluck('date_collecte');
            }
        }

        $periods = $legacyPeriods
            ->concat($questionnairePeriods)
            ->filter()
            ->map(fn ($period) => Carbon::parse($period)->startOfDay())
            ->unique(fn (Carbon $period): string => $period->format('Y-m-d'))
            ->sortByDesc(fn (Carbon $period): string => $period->format('Y-m-d'))
            ->values();

        $selectedPeriod = $request->string('periode')->toString()
            ?: optional($periods->first())->format('Y-m-d');

        $questionnaireSubmission = null;
        if ($questionnaire && $selectedPeriod !== '') {
            $questionnaireSubmission = MobileQuestionnaireSubmission::query()
                ->with(['user:id,name', 'questionnaire'])
                ->where('site_id', $site->id)
                ->whereHas('questionnaire', fn ($query) => $query->where('code', 'service-cartography'))
                ->whereDate('date_collecte', $selectedPeriod)
                ->whereNotNull('synced_at')
                ->latest('synced_at')
                ->first();
        }

        $periodProfiles = collect();
        if (! $questionnaireSubmission && $selectedPeriod !== '') {
            $periodProfiles = (clone $profilesQuery)
                ->whereDate('date_collecte', $selectedPeriod)
                ->with('collecteur:id,name')
                ->orderByRaw("CASE WHEN statut = 'valide' THEN 0 ELSE 1 END")
                ->orderByDesc('updated_at')
                ->get();
        }

        $questionnaireBasedProfile = $questionnaire !== null;
        $questionPreference = $questionnaire && $request->user()
            ? ProfileQuestionPreference::query()
                ->where('user_id', $request->user()->id)
                ->where('questionnaire_id', $questionnaire->id)
                ->first()
            : null;
        $serviceGroups = $questionnaire
            ? $questionnaireProfiles->groups(
                $questionnaireSubmission?->questionnaire ?? $questionnaire,
                $questionnaireSubmission,
                $questionPreference?->visible_question_keys
            )
            : $this->serviceGroups($periodProfiles);
        if ($questionnaire) {
            $population = Schema::hasTable('site_mouvements_population')
                ? (int) $site->individus
                : 0;
            $serviceGroups = $standardComparisons->annotate($serviceGroups, $population);
        }

        return view('public.site-profil', compact(
            'provinces',
            'site',
            'periods',
            'selectedPeriod',
            'serviceGroups',
            'questionnaireBasedProfile',
            'questionPreference'
        ));
    }

    public function updateQuestionPreferences(
        Request $request,
        Site $site,
        QuestionnaireProfileService $questionnaireProfiles
    ) {
        $questionnaire = MobileQuestionnaire::query()
            ->where('code', 'service-cartography')
            ->orderByDesc('version')
            ->firstOrFail();
        $validated = $request->validate([
            'question_keys' => ['nullable', 'array'],
            'question_keys.*' => ['string'],
            'periode' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $allowedKeys = $questionnaireProfiles->questionKeys($questionnaire);
        $visibleKeys = array_values(array_intersect(
            $validated['question_keys'] ?? [],
            $allowedKeys
        ));

        ProfileQuestionPreference::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'questionnaire_id' => $questionnaire->id,
            ],
            ['visible_question_keys' => $visibleKeys]
        );

        return redirect()
            ->route('public.site.show', array_filter([
                'site' => $site,
                'periode' => $validated['periode'] ?? null,
            ]))
            ->with('success', 'Votre sélection de questions a été enregistrée.');
    }

    private function serviceGroups($profiles): array
    {
        $definitions = [
            'sante' => [
                'title' => 'Santé',
                'icon' => '🏥',
                'available_field' => 'sante_disponible',
                'fields' => [
                    ['Structures fonctionnelles', 'sante_structures_fonctionnelles', 'number'],
                    ['Personnel médical', 'sante_personnel_medical', 'number'],
                    ['Services offerts', 'sante_services_offerts', 'list'],
                    ['Consultations par mois', 'sante_consultations_mois', 'number'],
                    ['Observations', 'sante_observations', 'text'],
                ],
            ],
            'education' => [
                'title' => 'Éducation',
                'icon' => '🎓',
                'available_field' => 'education_disponible',
                'fields' => [
                    ['Écoles fonctionnelles', 'education_ecoles_fonctionnelles', 'number'],
                    ['Enseignants', 'education_enseignants', 'number'],
                    ['Élèves inscrits', 'education_eleves_inscrits', 'number'],
                    ['Salles de classe', 'education_salles_classe', 'number'],
                    ['Niveaux offerts', 'education_niveaux_offerts', 'list'],
                    ['Observations', 'education_observations', 'text'],
                ],
            ],
            'wash' => [
                'title' => 'Eau, hygiène et assainissement (WASH)',
                'icon' => '💧',
                'available_field' => 'wash_disponible',
                'fields' => [
                    ['Points d’eau', 'wash_points_eau', 'number'],
                    ['Litres par personne/jour', 'wash_litres_par_personne', 'number'],
                    ['Latrines', 'wash_latrines', 'number'],
                    ['Douches', 'wash_douches', 'number'],
                    ['Gestion des déchets', 'wash_gestion_dechets', 'boolean'],
                    ['Observations', 'wash_observations', 'text'],
                ],
            ],
            'environnement' => [
                'title' => 'Environnement',
                'icon' => '🌿',
                'available_field' => 'environnement_disponible',
                'fields' => [
                    ['Gestion des déchets', 'environnement_gestion_dechets', 'boolean'],
                    ['Drainage', 'environnement_drainage', 'boolean'],
                    ['Espaces verts', 'environnement_espaces_verts', 'boolean'],
                    ['Risques identifiés', 'environnement_risques', 'list'],
                    ['Observations', 'environnement_observations', 'text'],
                ],
            ],
            'abri_ame' => [
                'title' => 'Abri et AME',
                'icon' => '🏕️',
                'available_field' => 'abri_ame_disponible',
                'fields' => [
                    ['Logements fonctionnels', 'abri_logements_fonctionnels', 'number'],
                    ['Types d’abris', 'abri_types', 'list'],
                    ['Ménages ayant reçu des AME', 'abri_menages_ame', 'number'],
                    ['AME distribués', 'abri_ame_distribues', 'list'],
                    ['Observations', 'abri_observations', 'text'],
                ],
            ],
            'gestion' => [
                'title' => 'Gestion et coordination',
                'icon' => '🤝',
                'available_field' => 'gestion_disponible',
                'fields' => [
                    ['Comité de site', 'gestion_comite_site', 'boolean'],
                    ['Membres du comité', 'gestion_membres_comite', 'number'],
                    ['Mécanisme de plainte', 'gestion_mecanisme_plainte', 'boolean'],
                    ['Réunions par mois', 'gestion_reunions_mois', 'number'],
                    ['Partenaires actifs', 'gestion_partenaires', 'list'],
                    ['Observations', 'gestion_observations', 'text'],
                ],
            ],
        ];

        foreach ($definitions as $key => &$definition) {
            $definition['profile'] = $profiles->first(
                fn (ServiceProfile $profile): bool => $profile->hasCollectedGroup($key)
            );
        }
        unset($definition);

        return $definitions;
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
