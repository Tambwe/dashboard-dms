<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commune;
use App\Models\MobileQuestionnaire;
use App\Models\MobileQuestionnaireSubmission;
use App\Models\Province;
use App\Models\RaisonMouvement;
use App\Models\Site;
use App\Models\Territoire;
use App\Models\User;
use App\Services\MobileSiteAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class MobileQuestionnaireController extends Controller
{
    public function __construct(private readonly MobileSiteAccessService $mobileSiteAccess)
    {
    }

    public function active(Request $request): JsonResponse
    {
        $user = $request->user();
        $code = (string) $request->input('code', 'service-cartography');

        $questionnaire = MobileQuestionnaire::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();

        if (! $questionnaire) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun questionnaire actif trouvé.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'questionnaire' => [
                'id' => $questionnaire->id,
                'code' => $questionnaire->code,
                'title' => $questionnaire->title,
                'description' => $questionnaire->description,
                'version' => $questionnaire->version,
                'survey' => $questionnaire->survey ?? [],
                'choices' => $this->mergeChoicesWithReferences($questionnaire->choices ?? [], $user),
                'settings' => $questionnaire->settings ?? [],
            ],
            'references' => [
                'provinces' => Province::query()->select('id', 'name')->orderBy('name')->get(),
                'territoires' => Territoire::query()->select('id', 'name', 'province_id')->orderBy('name')->get(),
                'communes' => Commune::query()->select('id', 'name', 'territoire_id', 'province_id')->orderBy('name')->get(),
                'sites' => $this->getReferenceSites($user),
                'movement_reasons' => RaisonMouvement::query()
                    ->with('categorieMouvement:id,name,code')
                    ->select('id', 'categorie_mouvement_id', 'name', 'code')
                    ->orderBy('name')
                    ->get()
                    ->map(fn (RaisonMouvement $reason) => [
                        'id' => $reason->id,
                        'name' => $reason->name,
                        'code' => $reason->code,
                        'category_name' => $reason->categorieMouvement?->name,
                        'category_code' => $reason->categorieMouvement?->code,
                    ])
                    ->values(),
            ],
        ]);
    }

    public function submit(Request $request): JsonResponse
    {
        $normalizedAnswers = $request->input('answers');
        if (! is_array($normalizedAnswers)) {
            $answerAlias = $request->input('answer');
            $normalizedAnswers = is_array($answerAlias) ? $answerAlias : [];
        }
        $request->merge(['answers' => $normalizedAnswers]);

        $request->validate([
            'questionnaire_code' => ['required', 'string'],
            'site_id' => ['nullable', 'integer'],
            'is_new_site' => ['nullable', 'boolean'],
            'new_site' => ['nullable', 'array'],
            'new_site.nom' => ['required_if:is_new_site,1', 'string', 'max:255'],
            'new_site.latitude' => ['nullable', 'numeric'],
            'new_site.longitude' => ['nullable', 'numeric'],
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
            'territoire_id' => ['nullable', 'integer', 'exists:territoires,id'],
            'commune_id' => ['nullable', 'integer', 'exists:communes,id'],
            'date_collecte' => ['nullable', 'date'],
            'answers' => ['nullable', 'array'],
        ]);

        $questionnaire = MobileQuestionnaire::query()
            ->where('code', $request->string('questionnaire_code'))
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();

        if (! $questionnaire) {
            return response()->json([
                'success' => false,
                'message' => 'Questionnaire actif introuvable.',
            ], 404);
        }

        $user = $request->user();

        $siteId = $request->input('site_id');
        if ($request->boolean('is_new_site')) {
            $siteId = $this->createSiteFromMobilePayload(
                (array) $request->input('new_site', []),
                $request,
                $user
            )->id;
        } else {
            $siteId = $this->resolveExistingSiteId($siteId, $user);
        }

        if (empty($siteId)) {
            return response()->json([
                'success' => false,
                'message' => 'Site requis pour enregistrer la soumission.',
            ], 422);
        }

        $submission = MobileQuestionnaireSubmission::query()->create([
            'questionnaire_id' => $questionnaire->id,
            'user_id' => $user->id,
            'province_id' => $request->input('province_id'),
            'territoire_id' => $request->input('territoire_id'),
            'commune_id' => $request->input('commune_id'),
            'site_id' => $siteId,
            'date_collecte' => $request->input('date_collecte'),
            'answers' => $request->input('answers', []),
            'status' => 'submitted',
            'synced_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'stored' => true,
            'id' => $submission->id,
            'message' => 'Questionnaire mobile enregistré.',
        ]);
    }

    private function mergeChoicesWithReferences(array $choices, User $user): array
    {
        $choices = array_values(array_filter(
            $choices,
            fn (mixed $choice): bool => ! is_array($choice)
                || (string) ($choice['list_name'] ?? $choice['listName'] ?? '') !== 'camps_csv'
        ));
        $referenceChoices = [];

        foreach (Province::query()->select('id', 'name')->orderBy('name')->get() as $province) {
            $referenceChoices[] = [
                'list_name' => 'provinces_csv',
                'name' => (string) $province->id,
                'label' => $province->name,
                'province' => '',
                'territoire' => '',
                'zs' => '',
            ];
        }

        foreach (Territoire::query()->select('id', 'name', 'province_id')->orderBy('name')->get() as $territoire) {
            $referenceChoices[] = [
                'list_name' => 'territoires_csv',
                'name' => (string) $territoire->id,
                'label' => $territoire->name,
                'province' => (string) $territoire->province_id,
                'territoire' => '',
                'zs' => '',
            ];
        }

        foreach (Commune::query()->select('id', 'name', 'province_id', 'territoire_id')->orderBy('name')->get() as $commune) {
            $referenceChoices[] = [
                'list_name' => 'zs_csv',
                'name' => (string) $commune->id,
                'label' => $commune->name,
                'province' => (string) $commune->province_id,
                'territoire' => (string) $commune->territoire_id,
                'zs' => '',
            ];
        }

        $siteSelectColumns = ['id'];
        foreach (['nom', 'commune_id', 'zone_sante'] as $column) {
            if (Schema::hasColumn('sites', $column)) {
                $siteSelectColumns[] = $column;
            }
        }

        foreach ($this->mobileSiteAccess->accessibleSitesQuery($user)->select($siteSelectColumns)->orderBy('nom')->get() as $site) {
            $zsReference = '';
            if (Schema::hasColumn('sites', 'commune_id')) {
                $zsReference = (string) ($site->commune_id ?? '');
            } elseif (Schema::hasColumn('sites', 'zone_sante')) {
                $zsReference = trim((string) ($site->zone_sante ?? ''));
            }

            $referenceChoices[] = [
                'list_name' => 'camps_csv',
                'name' => (string) $site->id,
                'label' => $site->nom ?? ('Site '.$site->id),
                'province' => '',
                'territoire' => '',
                'zs' => $zsReference,
            ];
        }

        return array_values(array_merge($choices, $referenceChoices));
    }

    private function getReferenceSites(User $user)
    {
        $siteSelectColumns = ['id'];
        foreach ([
            'nom',
            'code_site',
            'province',
            'territoire',
            'commune_id',
            'zone_sante',
            'latitude',
            'longitude',
            'geometry_type',
            'geojson_data',
        ] as $column) {
            if (Schema::hasColumn('sites', $column)) {
                $siteSelectColumns[] = $column;
            }
        }

        return $this->mobileSiteAccess->accessibleSitesQuery($user)
            ->select($siteSelectColumns)
            ->orderBy('nom')
            ->get();
    }

    private function createSiteFromMobilePayload(array $newSite, Request $request, User $user): Site
    {
        $siteData = [
            'nom' => trim((string) ($newSite['nom'] ?? '')) !== ''
                ? trim((string) ($newSite['nom'] ?? ''))
                : 'Site mobile '.now()->format('YmdHis'),
            'code_site' => $this->nullableString($newSite['code_site'] ?? null),
            'type_site_id' => $this->nullableInt($newSite['type_site_id'] ?? null),
            'commune_id' => $this->nullableInt($newSite['commune_id'] ?? $request->input('commune_id')),
            'gestionnaire_id' => $this->nullableInt($newSite['gestionnaire_id'] ?? null),
            'coordinateur_id' => $this->nullableInt($newSite['coordinateur_id'] ?? null),
            'categorie_site_id' => $this->nullableInt($newSite['categorie_site_id'] ?? null),
            'province' => $this->nullableString($newSite['province'] ?? null),
            'code_province' => $this->nullableString($newSite['code_province'] ?? null),
            'territoire' => $this->nullableString($newSite['territoire'] ?? null),
            'code_territoire' => $this->nullableString($newSite['code_territoire'] ?? null),
            'zone_sante' => $this->nullableString($newSite['zone_sante'] ?? null),
            'code_zone_sante' => $this->nullableString($newSite['code_zone_sante'] ?? null),
            'aire_sante' => $this->nullableString($newSite['aire_sante'] ?? null),
            'code_aire_sante' => $this->nullableString($newSite['code_aire_sante'] ?? null),
            'latitude' => $this->nullableFloat($newSite['latitude'] ?? null),
            'longitude' => $this->nullableFloat($newSite['longitude'] ?? null),
            'source' => $this->nullableString($newSite['source'] ?? 'mobile'),
            'round' => $this->nullableString($newSite['round'] ?? null),
            'type_gestion' => $this->nullableString($newSite['type_gestion'] ?? null),
            'date_mise_a_jour' => $this->nullableString($newSite['date_mise_a_jour'] ?? now()->toDateString()),
            'type_fichier' => $this->nullableString($newSite['type_fichier'] ?? null),
            'geometry_type' => $this->nullableString($newSite['geometry_type'] ?? 'point'),
            'collection_accuracy_m' => $this->nullableFloat($newSite['collection_accuracy_m'] ?? null),
            'geometry_collected_at' => now(),
            'date_fermeture' => $this->nullableString($newSite['date_fermeture'] ?? null),
            'raison_fermeture' => $this->nullableString($newSite['raison_fermeture'] ?? null),
            'commentaire_fermeture' => $this->nullableString($newSite['commentaire_fermeture'] ?? null),
            'document_fermeture' => $this->nullableString($newSite['document_fermeture'] ?? null),
        ];

        return $this->mobileSiteAccess->createSiteForCollector($user, $siteData);
    }

    private function resolveExistingSiteId(mixed $siteId, User $user): ?int
    {
        if (!is_numeric($siteId)) {
            return null;
        }

        $normalizedSiteId = (int) $siteId;
        if ($normalizedSiteId <= 0) {
            return null;
        }

        $site = Site::query()->find($normalizedSiteId);
        if (! $site) {
            return null;
        }

        $this->mobileSiteAccess->assertCanCollect($user, $site);

        return $normalizedSiteId;
    }

    private function nullableString(mixed $value): ?string
    {
        $string = trim((string) ($value ?? ''));
        return $string === '' ? null : $string;
    }

    private function nullableInt(mixed $value): ?int
    {
        $string = trim((string) ($value ?? ''));
        if ($string === '' || !is_numeric($string)) {
            return null;
        }

        return (int) $string;
    }

    private function nullableFloat(mixed $value): ?float
    {
        $string = trim((string) ($value ?? ''));
        if ($string === '' || !is_numeric($string)) {
            return null;
        }

        return (float) $string;
    }
}
