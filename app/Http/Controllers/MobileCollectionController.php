<?php

namespace App\Http\Controllers;

use App\Models\MobileCollectionSubmission;
use App\Models\Commune;
use App\Models\MobileQuestionnaire;
use App\Models\MobileQuestionnaireSubmission;
use App\Models\OssatReport;
use App\Models\Province;
use App\Models\SiteMouvementPopulation;
use App\Models\ServiceProfile;
use App\Models\Site;
use App\Models\SiteGeography;
use App\Models\Territoire;
use App\Models\User;
use App\Services\MobileSiteAccessService;
use App\Services\GeoJsonService;
use App\Services\SitePopulationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MobileCollectionController extends Controller
{
    private const MAX_GEOGRAPHY_ACCURACY_METERS = 15;

    public function __construct(
        private readonly MobileSiteAccessService $mobileSiteAccess,
        private readonly GeoJsonService $geoJsonService
    ) {
    }

    protected array $sectorMap = [
        'wash' => [
            'wash_disponible',
            'wash_points_eau',
            'wash_litres_par_personne',
            'wash_latrines',
            'wash_douches',
            'wash_gestion_dechets',
            'wash_observations',
        ],
        'sante' => [
            'sante_disponible',
            'sante_structures_fonctionnelles',
            'sante_personnel_medical',
            'sante_services_offerts',
            'sante_consultations_mois',
            'sante_observations',
        ],
        'protection' => [
            'gestion_disponible',
            'gestion_comite_site',
            'gestion_membres_comite',
            'gestion_mecanisme_plainte',
            'gestion_reunions_mois',
            'gestion_partenaires',
            'gestion_observations',
        ],
        'education' => [
            'education_disponible',
            'education_ecoles_fonctionnelles',
            'education_enseignants',
            'education_eleves_inscrits',
            'education_salles_classe',
            'education_niveaux_offerts',
            'education_observations',
        ],
        'environnement' => [
            'environnement_disponible',
            'environnement_gestion_dechets',
            'environnement_drainage',
            'environnement_espaces_verts',
            'environnement_risques',
            'environnement_observations',
        ],
        'abri' => [
            'abri_ame_disponible',
            'abri_logements_fonctionnels',
            'abri_types',
            'abri_menages_ame',
            'abri_ame_distribues',
            'abri_observations',
        ],
    ];

    public function index()
    {
        $user = Auth::user();
        $siteQuery = Site::query();

        $hasSiteUserAccessTable = Schema::hasTable('site_user_access');
        $sitesHasOrganisationId = Schema::hasColumn('sites', 'organisation_id');
        $usersHasOrganisationId = Schema::hasColumn('users', 'organisation_id');

        if ($user) {
            $siteQuery->where(function ($query) use ($user, $hasSiteUserAccessTable, $sitesHasOrganisationId, $usersHasOrganisationId) {
                $hasOrgFilter = $sitesHasOrganisationId && $usersHasOrganisationId && !empty($user->organisation_id);

                if ($hasOrgFilter) {
                    $query->where('organisation_id', $user->organisation_id);
                }

                if ($hasSiteUserAccessTable) {
                    $siteIds = DB::table('site_user_access')
                        ->where('user_id', $user->id)
                        ->pluck('site_id');

                    if ($hasOrgFilter) {
                        $query->orWhereIn('id', $siteIds);
                    } else {
                        $query->whereIn('id', $siteIds);
                    }
                }
            });
        }

        if (Schema::hasColumn('sites', 'date_fermeture')) {
            $siteQuery->whereNull('date_fermeture');
        }

        $sites = $siteQuery->orderBy('nom')->get();

        return view('mobile.index', compact('sites'));
    }

    public function syncedData()
    {
        $mobileQuery = MobileCollectionSubmission::query()
            ->with([
                'user:id,name',
                'site:id,nom,code_site',
            ]);
        $this->scopeSyncedDataForAdmin($mobileQuery);
        $mobileSynced = $mobileQuery
            ->where('status', 'synced')
            ->orderByDesc('synced_at')
            ->orderByDesc('id')
            ->limit(300)
            ->get();

        $questionnaireQuery = MobileQuestionnaireSubmission::query()
            ->with([
                'user:id,name',
                'site:id,nom,code_site',
                'questionnaire:id,code,title',
            ]);
        $this->scopeSyncedDataForAdmin($questionnaireQuery);
        $questionnaireSynced = $questionnaireQuery
            ->whereNotNull('synced_at')
            ->orderByDesc('synced_at')
            ->orderByDesc('id')
            ->limit(300)
            ->get();

        $provinceMap = Province::query()->pluck('name', 'id');
        $territoireMap = Territoire::query()->pluck('name', 'id');
        $communeMap = Commune::query()->pluck('name', 'id');

        return view('mobile.synced-data', compact(
            'mobileSynced',
            'questionnaireSynced',
            'provinceMap',
            'territoireMap',
            'communeMap'
        ));
    }

    public function showSyncedData(string $source, int $id)
    {
        [$record, $normalizedSource] = $this->resolveSyncedRecord($source, $id);
        [$provinceMap, $territoireMap, $communeMap] = $this->geoMaps();
        $contentRows = $this->buildContentRows(
            $normalizedSource === 'mobile'
                ? (is_array($record->payload) ? $record->payload : [])
                : (is_array($record->answers) ? $record->answers : [])
        );
        $groupedContentSections = $this->buildGroupedContentSections($record, $normalizedSource);

        return view('mobile.synced-data-show', compact(
            'record',
            'normalizedSource',
            'provinceMap',
            'territoireMap',
            'communeMap',
            'contentRows',
            'groupedContentSections'
        ));
    }

    public function editSyncedData(string $source, int $id)
    {
        [$record, $normalizedSource] = $this->resolveSyncedRecord($source, $id);
        [$provinceMap, $territoireMap, $communeMap] = $this->geoMaps();
        $sites = Site::query()->select('id', 'nom', 'code_site')->orderBy('nom')->get();
        $contentRows = $this->buildContentRows(
            $normalizedSource === 'mobile'
                ? (is_array($record->payload) ? $record->payload : [])
                : (is_array($record->answers) ? $record->answers : [])
        );
        $groupedContentSections = $this->buildGroupedContentSections($record, $normalizedSource);

        return view('mobile.synced-data-edit', compact(
            'record',
            'normalizedSource',
            'provinceMap',
            'territoireMap',
            'communeMap',
            'sites',
            'contentRows',
            'groupedContentSections'
        ));
    }

    public function updateSyncedData(Request $request, string $source, int $id): RedirectResponse
    {
        [$record, $normalizedSource] = $this->resolveSyncedRecord($source, $id);

        if ($normalizedSource === 'mobile') {
            $validated = $request->validate([
                'site_id' => ['required', 'integer', 'exists:sites,id'],
                'date_collecte' => ['nullable', 'date'],
                'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
                'territoire_id' => ['nullable', 'integer', 'exists:territoires,id'],
                'commune_id' => ['nullable', 'integer', 'exists:communes,id'],
                'content' => ['nullable', 'array'],
                'content_multi' => ['nullable', 'array'],
                'content_types' => ['nullable', 'array'],
            ]);

            $payload = is_array($record->payload) ? $record->payload : [];
            $editedContent = $this->mergeEditedMultiContent(
                $validated['content'] ?? [],
                $validated['content_multi'] ?? []
            );
            $payload = $this->applyEditedContent($payload, $editedContent, $validated['content_types'] ?? []);

            $payload['site_id'] = (int) $validated['site_id'];
            $payload['date_collecte'] = $validated['date_collecte'] ?? ($payload['date_collecte'] ?? null);
            $payload['province_id'] = $validated['province_id'] ?? null;
            $payload['territoire_id'] = $validated['territoire_id'] ?? null;
            $payload['commune_id'] = $validated['commune_id'] ?? null;

            $record->site_id = (int) $validated['site_id'];
            $record->payload = $payload;
            $record->validation_status = 'pending';
            $record->validated_by = null;
            $record->validated_at = null;
            $record->save();
        } else {
            $validated = $request->validate([
                'site_id' => ['required', 'integer', 'exists:sites,id'],
                'date_collecte' => ['nullable', 'date'],
                'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
                'territoire_id' => ['nullable', 'integer', 'exists:territoires,id'],
                'commune_id' => ['nullable', 'integer', 'exists:communes,id'],
                'content' => ['nullable', 'array'],
                'content_multi' => ['nullable', 'array'],
                'content_types' => ['nullable', 'array'],
            ]);
            $answers = is_array($record->answers) ? $record->answers : [];
            $editedContent = $this->mergeEditedMultiContent(
                $validated['content'] ?? [],
                $validated['content_multi'] ?? []
            );
            $answers = $this->applyEditedContent($answers, $editedContent, $validated['content_types'] ?? []);

            $record->site_id = (int) $validated['site_id'];
            $record->date_collecte = $validated['date_collecte'] ?? null;
            $record->province_id = $validated['province_id'] ?? null;
            $record->territoire_id = $validated['territoire_id'] ?? null;
            $record->commune_id = $validated['commune_id'] ?? null;
            $record->answers = $answers;
            $record->validation_status = 'pending';
            $record->validated_by = null;
            $record->validated_at = null;
            $record->save();
        }

        return redirect()
            ->route('mobile.synced-data')
            ->with('success', 'Formulaire synchronisé mis à jour.');
    }

    public function validateSyncedData(string $source, int $id): RedirectResponse
    {
        [$record] = $this->resolveSyncedRecord($source, $id);

        $record->validation_status = 'validated';
        $record->validated_by = Auth::id();
        $record->validated_at = now();
        $record->save();

        return back()->with('success', 'Formulaire synchronisé validé.');
    }

    public function destroySyncedData(string $source, int $id): RedirectResponse
    {
        [$record] = $this->resolveSyncedRecord($source, $id);
        $record->delete();

        return redirect()
            ->route('mobile.synced-data')
            ->with('success', 'Formulaire synchronisé supprimé.');
    }

    public function exportSyncedDataExcel(): StreamedResponse
    {
        $mobileQuery = MobileCollectionSubmission::query()
            ->with(['user:id,name', 'site:id,nom,code_site', 'validatedBy:id,name']);
        $this->scopeSyncedDataForAdmin($mobileQuery);
        $mobileSynced = $mobileQuery
            ->where('status', 'synced')
            ->get();
        $questionnaireQuery = MobileQuestionnaireSubmission::query()
            ->with(['user:id,name', 'site:id,nom,code_site', 'questionnaire:id,code,title', 'validatedBy:id,name']);
        $this->scopeSyncedDataForAdmin($questionnaireQuery);
        $questionnaireSynced = $questionnaireQuery
            ->whereNotNull('synced_at')
            ->get();
        [$provinceMap, $territoireMap, $communeMap] = $this->geoMaps();

        $rows = collect();

        foreach ($mobileSynced as $item) {
            $payload = is_array($item->payload) ? $item->payload : [];
            $rows->push([
                'Source' => 'mobile_collection',
                'Record ID' => $item->id,
                'Type' => (string) ($item->type ?? ''),
                'Date collecte' => (string) ($payload['date_collecte'] ?? ''),
                'Province' => (string) ($provinceMap[(int) ($payload['province_id'] ?? 0)] ?? ''),
                'Territoire' => (string) ($territoireMap[(int) ($payload['territoire_id'] ?? 0)] ?? ''),
                'Commune' => (string) ($communeMap[(int) ($payload['commune_id'] ?? 0)] ?? ''),
                'Site ID' => (int) ($item->site_id ?? 0),
                'Site' => $item->site ? ($item->site->nom.' ('.($item->site->code_site ?? 'N/A').')') : '',
                'Utilisateur' => (string) ($item->user->name ?? ''),
                'Synced at' => optional($item->synced_at)->format('Y-m-d H:i:s') ?? '',
                'Validation' => $item->validation_status === 'validated' ? 'Validé' : 'En attente',
                'Validé par' => (string) ($item->validatedBy->name ?? ''),
                'Validé le' => optional($item->validated_at)->format('Y-m-d H:i:s') ?? '',
                'Contenu JSON' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);
        }

        foreach ($questionnaireSynced as $item) {
            $rows->push([
                'Source' => 'mobile_questionnaire',
                'Record ID' => $item->id,
                'Type' => (string) ($item->questionnaire->title ?? $item->questionnaire->code ?? 'questionnaire'),
                'Date collecte' => optional($item->date_collecte)->format('Y-m-d') ?? '',
                'Province' => (string) ($provinceMap[(int) ($item->province_id ?? 0)] ?? ''),
                'Territoire' => (string) ($territoireMap[(int) ($item->territoire_id ?? 0)] ?? ''),
                'Commune' => (string) ($communeMap[(int) ($item->commune_id ?? 0)] ?? ''),
                'Site ID' => (int) ($item->site_id ?? 0),
                'Site' => $item->site ? ($item->site->nom.' ('.($item->site->code_site ?? 'N/A').')') : '',
                'Utilisateur' => (string) ($item->user->name ?? ''),
                'Synced at' => optional($item->synced_at)->format('Y-m-d H:i:s') ?? '',
                'Validation' => $item->validation_status === 'validated' ? 'Validé' : 'En attente',
                'Validé par' => (string) ($item->validatedBy->name ?? ''),
                'Validé le' => optional($item->validated_at)->format('Y-m-d H:i:s') ?? '',
                'Contenu JSON' => json_encode($item->answers ?? [], JSON_UNESCAPED_UNICODE),
            ]);
        }

        $rows = $rows->sortByDesc('Synced at')->values();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Synced Data');

        $headers = [
            'Source', 'Record ID', 'Type', 'Date collecte', 'Province', 'Territoire', 'Commune',
            'Site ID', 'Site', 'Utilisateur', 'Synced at', 'Validation', 'Validé par', 'Validé le',
            'Contenu JSON'
        ];
        foreach ($headers as $index => $header) {
            $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
        }

        $rowNum = 2;
        foreach ($rows as $row) {
            foreach ($headers as $index => $header) {
                $sheet->setCellValueByColumnAndRow($index + 1, $rowNum, (string) ($row[$header] ?? ''));
            }
            $rowNum++;
        }

        foreach (range('A', 'L') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'mobile-synced-data-'.now()->format('Ymd-His').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $temp = tempnam(sys_get_temp_dir(), 'mobile-synced-');
        $writer->save($temp);

        return response()->download($temp, $filename)->deleteFileAfterSend(true);
    }

    private function resolveSyncedRecord(string $source, int $id): array
    {
        $normalizedSource = strtolower(trim($source));
        if (!in_array($normalizedSource, ['mobile', 'questionnaire'], true)) {
            abort(404);
        }

        if ($normalizedSource === 'mobile') {
            $query = MobileCollectionSubmission::query()
                ->with(['user:id,name', 'site:id,nom,code_site', 'geographyEntry', 'validatedBy:id,name']);
            $this->scopeSyncedDataForAdmin($query);
            $record = $query
                ->where('status', 'synced')
                ->findOrFail($id);
            return [$record, $normalizedSource];
        }

        $query = MobileQuestionnaireSubmission::query()
            ->with(['user:id,name', 'site:id,nom,code_site', 'questionnaire:id,code,title,survey,choices', 'validatedBy:id,name']);
        $this->scopeSyncedDataForAdmin($query);
        $record = $query
            ->whereNotNull('synced_at')
            ->findOrFail($id);

        return [$record, $normalizedSource];
    }

    private function scopeSyncedDataForAdmin(Builder $query): void
    {
        $user = Auth::user();
        if (! $user?->isAdminOrganisation()) {
            return;
        }

        if (empty($user->organisation_id)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereHas('user', function ($userQuery) use ($user) {
            $userQuery->where('organisation_id', $user->organisation_id);
        });
    }

    private function geoMaps(): array
    {
        return [
            Province::query()->pluck('name', 'id'),
            Territoire::query()->pluck('name', 'id'),
            Commune::query()->pluck('name', 'id'),
        ];
    }

    private function buildContentRows(array $data): array
    {
        $flattened = Arr::dot($data);
        if ($flattened === []) {
            return [];
        }

        $rows = [];
        foreach ($flattened as $path => $value) {
            $inputType = $this->resolveInputType($value);
            $rows[] = [
                'path' => (string) $path,
                'field' => $this->humanizeFieldPath((string) $path),
                'value' => $this->humanizeFieldValue($value),
                'raw_value' => $this->normalizeInputValue($value, $inputType),
                'input_type' => $inputType,
            ];
        }

        return $rows;
    }

    private function humanizeFieldPath(string $path): string
    {
        $parts = explode('.', $path);
        $display = array_map(function (string $part) {
            if (is_numeric($part)) {
                return '#'.((int) $part + 1);
            }

            return ucfirst(str_replace('_', ' ', $part));
        }, $parts);

        return implode(' > ', $display);
    }

    private function humanizeFieldValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Oui' : 'Non';
        }

        if ($value === null || $value === '') {
            return '-';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '-';
    }

    private function resolveInputType(mixed $value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_int($value) || is_float($value)) {
            return 'number';
        }

        return 'text';
    }

    private function normalizeInputValue(mixed $value, string $inputType): string
    {
        if ($inputType === 'boolean') {
            return $value ? '1' : '0';
        }

        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
    }

    private function applyEditedContent(array $original, array $edited, array $typeHints = []): array
    {
        if ($edited === []) {
            return $original;
        }

        $flatOriginal = Arr::dot($original);
        foreach ($edited as $path => $newValue) {
            $pathKey = (string) $path;
            $oldValue = $flatOriginal[$pathKey] ?? Arr::get($original, $pathKey);
            $typeHint = (string) ($typeHints[$pathKey] ?? '');
            if (is_bool($oldValue)) {
                Arr::set($original, $pathKey, (string) $newValue === '1');
                continue;
            }

            if (is_int($oldValue)) {
                if ($newValue === '' || $newValue === null) {
                    Arr::set($original, $pathKey, null);
                } else {
                    Arr::set($original, $pathKey, (int) $newValue);
                }
                continue;
            }

            if (is_float($oldValue)) {
                if ($newValue === '' || $newValue === null) {
                    Arr::set($original, $pathKey, null);
                } else {
                    Arr::set($original, $pathKey, (float) $newValue);
                }
                continue;
            }

            if ($typeHint === 'boolean') {
                Arr::set($original, $pathKey, (string) $newValue === '1');
                continue;
            }

            if ($typeHint === 'number') {
                if ($newValue === '' || $newValue === null) {
                    Arr::set($original, $pathKey, null);
                } else {
                    $valueString = trim((string) $newValue);
                    if (is_numeric($valueString)) {
                        Arr::set(
                            $original,
                            $pathKey,
                            str_contains($valueString, '.') ? (float) $valueString : (int) $valueString
                        );
                    } else {
                        Arr::set($original, $pathKey, $valueString);
                    }
                }
                continue;
            }

            Arr::set($original, $pathKey, $newValue === '' ? null : $newValue);
        }

        return $original;
    }

    private function buildQuestionnaireGroupedContent(MobileQuestionnaireSubmission $record): array
    {
        return $this->buildQuestionnaireGroupedContentFromParts(
            is_array($record->questionnaire?->survey) ? $record->questionnaire->survey : [],
            is_array($record->questionnaire?->choices) ? $record->questionnaire->choices : [],
            is_array($record->answers) ? $record->answers : []
        );
    }

    private function buildQuestionnaireGroupedContentFromParts(array $survey, array $choices, array $answers): array
    {
        $sections = [];
        $stack = [['key' => 'default', 'label' => 'Général']];

        foreach ($survey as $row) {
            $typeRaw = (string) ($row['type'] ?? '');
            $type = Str::lower(trim($typeRaw));

            if ($this->isGroupStartType($type)) {
                $groupKey = trim((string) ($row['name'] ?? ''));
                if ($groupKey === '') {
                    $groupKey = 'default';
                }
                $groupLabel = trim((string) ($row['label'] ?? $row['label_fr'] ?? $groupKey));
                $stack[] = ['key' => $groupKey, 'label' => $groupLabel];
                continue;
            }

            if ($this->isGroupEndType($type)) {
                if (count($stack) > 1) {
                    array_pop($stack);
                }
                continue;
            }

            if (in_array($type, ['start', 'end', 'today', 'deviceid', 'phonenumber', 'calculate', 'note'], true)) {
                continue;
            }

            $fieldName = trim((string) ($row['name'] ?? ''));
            if ($fieldName === '') {
                continue;
            }

            $groupSegment = $stack[1] ?? ['key' => 'default', 'label' => 'Général'];
            $groupKey = (string) ($groupSegment['key'] ?? 'default');
            $groupLabel = (string) ($groupSegment['label'] ?? 'Général');
            if (!array_key_exists($groupKey, $sections)) {
                $sections[$groupKey] = [
                    'key' => $groupKey,
                    'label' => $groupLabel,
                    'subgroups' => [],
                ];
            }

            $subgroupSegments = array_slice($stack, 2);
            $subgroupKey = $subgroupSegments === []
                ? 'parent'
                : implode('::', array_map(fn (array $segment) => (string) ($segment['key'] ?? ''), $subgroupSegments));
            $subgroupLabel = $subgroupSegments === []
                ? 'Principal'
                : implode(' > ', array_map(fn (array $segment) => (string) ($segment['label'] ?? $segment['key'] ?? ''), $subgroupSegments));

            if (!array_key_exists($subgroupKey, $sections[$groupKey]['subgroups'])) {
                $sections[$groupKey]['subgroups'][$subgroupKey] = [
                    'key' => $subgroupKey,
                    'label' => $subgroupLabel,
                    'questions' => [],
                ];
            }

            $value = array_key_exists($fieldName, $answers) ? $answers[$fieldName] : Arr::get($answers, $fieldName);
            $questionInputType = $this->resolveInputTypeFromQuestionType($typeRaw);
            $inputType = $value === null ? $questionInputType : $this->resolveInputType($value);
            if (in_array($questionInputType, ['select_one', 'select_multiple'], true)) {
                $inputType = $questionInputType;
            }
            $resolvedChoices = $this->resolveQuestionChoices(
                $choices,
                $row
            );
            $selectedValues = $this->parseSelectedValues($value);

            $sections[$groupKey]['subgroups'][$subgroupKey]['questions'][] = [
                'path' => $fieldName,
                'field' => trim((string) ($row['label'] ?? $row['label_fr'] ?? $fieldName)),
                'value' => $this->humanizeFieldValue($value),
                'raw_value' => $this->normalizeInputValue($value, $inputType),
                'input_type' => $inputType,
                'options' => $resolvedChoices,
                'selected_values' => $selectedValues,
            ];
        }

        if ($sections === []) {
            return [];
        }

        return array_values(array_map(function (array $section) {
            $section['subgroups'] = array_values($section['subgroups']);
            return $section;
        }, $sections));
    }

    private function buildGroupedContentSections(mixed $record, string $normalizedSource): array
    {
        if ($normalizedSource === 'questionnaire' && $record instanceof MobileQuestionnaireSubmission) {
            return $this->buildQuestionnaireGroupedContent($record);
        }

        if ($normalizedSource === 'mobile' && $record instanceof MobileCollectionSubmission) {
            $payload = is_array($record->payload) ? $record->payload : [];
            if (($record->type ?? '') !== 'questionnaire') {
                return [];
            }

            $questionnaireCode = (string) ($payload['questionnaire_code'] ?? 'service-cartography');
            $answers = is_array($payload['answers'] ?? null) ? $payload['answers'] : [];
            if ($answers === []) {
                return [];
            }

            $questionnaire = MobileQuestionnaire::query()
                ->where('code', $questionnaireCode)
                ->orderByDesc('is_active')
                ->orderByDesc('version')
                ->first();

            if (!$questionnaire || !is_array($questionnaire->survey)) {
                return [];
            }

            return $this->buildQuestionnaireGroupedContentFromParts(
                $questionnaire->survey ?? [],
                is_array($questionnaire->choices) ? $questionnaire->choices : [],
                $answers
            );
        }

        return [];
    }

    private function isGroupStartType(string $type): bool
    {
        return preg_match('/^begin[_ ](group|repeat)\b/i', trim($type)) === 1;
    }

    private function isGroupEndType(string $type): bool
    {
        return preg_match('/^end[_ ](group|repeat)\b/i', trim($type)) === 1;
    }

    private function resolveInputTypeFromQuestionType(string $questionType): string
    {
        $normalized = Str::lower(trim($questionType));
        if (preg_match('/^select[_ ]one\b/i', $normalized) === 1) {
            return 'select_one';
        }
        if (preg_match('/^select[_ ]multiple\b/i', $normalized) === 1) {
            return 'select_multiple';
        }
        if (str_starts_with($normalized, 'integer') || str_starts_with($normalized, 'decimal')) {
            return 'number';
        }

        return 'text';
    }

    private function resolveQuestionChoices(array $allChoices, array $questionRow): array
    {
        $listName = $this->extractQuestionListName($questionRow);
        if ($listName === '') {
            return [];
        }

        $normalized = Str::lower($listName);
        $options = [];
        foreach ($allChoices as $choice) {
            if (!is_array($choice)) {
                continue;
            }
            $choiceList = Str::lower(trim((string) ($choice['list_name'] ?? $choice['listName'] ?? '')));
            if ($choiceList !== $normalized) {
                continue;
            }

            $value = trim((string) ($choice['name'] ?? ''));
            if ($value === '') {
                continue;
            }

            $label = trim((string) ($choice['label'] ?? $choice['label_fr'] ?? $choice['label_en'] ?? $value));
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }

    private function extractQuestionListName(array $questionRow): string
    {
        $explicit = Str::lower(trim((string) ($questionRow['list_name'] ?? $questionRow['listName'] ?? '')));
        if ($explicit !== '') {
            return $explicit;
        }

        $type = trim((string) ($questionRow['type'] ?? ''));
        if (preg_match('/^select[_ ](?:one|multiple)\s+(.+)$/i', $type, $matches) === 1) {
            return Str::lower(trim((string) ($matches[1] ?? '')));
        }

        if (preg_match('/^select[_ ]one[_ ]from[_ ]file\s+(.+)$/i', $type, $matches) === 1) {
            return Str::lower(trim(str_replace('.csv', '_csv', (string) ($matches[1] ?? ''))));
        }

        return '';
    }

    private function parseSelectedValues(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(
                fn ($entry) => trim((string) $entry),
                $value
            ), fn ($entry) => $entry !== ''));
        }

        $string = trim((string) ($value ?? ''));
        if ($string === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(' ', $string)), fn ($entry) => $entry !== ''));
    }

    private function mergeEditedMultiContent(array $content, array $contentMulti): array
    {
        if ($contentMulti === []) {
            return $content;
        }

        foreach ($contentMulti as $path => $values) {
            $pathKey = (string) $path;
            if (!is_array($values)) {
                $content[$pathKey] = '';
                continue;
            }

            $normalized = array_values(array_filter(array_map(
                fn ($entry) => trim((string) $entry),
                $values
            ), fn ($entry) => $entry !== ''));
            $content[$pathKey] = implode(' ', $normalized);
        }

        return $content;
    }

    public function save(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'in:sector,geography'],
            'site_id' => ['required', 'integer'],
            'payload' => ['required', 'array'],
        ]);

        $payload = $request->input('payload');
        $submission = MobileCollectionSubmission::create([
            'user_id' => Auth::id(),
            'site_id' => $request->input('site_id'),
            'type' => $request->input('type'),
            'sector' => $request->input('sector'),
            'payload' => $payload,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'stored' => true,
            'id' => $submission->id,
            'status' => 'pending',
            'message' => 'Données enregistrées localement et prêtes pour synchronisation.',
        ]);
    }

    protected function resolveNativeUser(Request $request): User
    {
        return $request->user();
    }

    public function loginNative(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_uuid' => ['nullable', 'uuid'],
        ]);

        if (!Auth::attempt($request->only('email', 'password'), true)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants invalides.',
            ], 401);
        }

        $user = Auth::user();
        $tokenName = 'mobile-device:' . ($request->input('device_uuid') ?: Str::uuid()->toString());
        $user->tokens()->where('name', $tokenName)->delete();
        $apiToken = $user->createToken($tokenName, [
            'mobile-device:register',
            'mobile-collection',
        ])->plainTextToken;

        return response()->json([
            'success' => true,
            'api_token' => $apiToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'message' => 'Connexion native réussie.',
        ]);
    }

    public function uploadNativePhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'file', 'image', 'max:20000'],
        ]);

        $path = $request->file('photo')->store('mobile-photos', 'public');

        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => asset('storage/' . $path),
            'message' => 'Photo téléversée avec succès.',
        ]);
    }

    public function saveNative(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'in:sector,geography,ossat,questionnaire,movement'],
            'site_id' => ['nullable', 'integer'],
            'payload' => ['required', 'array'],
        ]);

        $user = $this->resolveNativeUser($request);
        $payload = $request->input('payload');
        $siteId = $this->resolveSubmissionSiteId(
            $request->input('site_id'),
            is_array($payload) ? $payload : [],
            $user
        );
        $submission = MobileCollectionSubmission::create([
            'user_id' => $user->id,
            'site_id' => $siteId,
            'type' => $request->input('type'),
            'sector' => $request->input('sector'),
            'payload' => $payload,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'stored' => true,
            'id' => $submission->id,
            'status' => 'pending',
            'message' => 'Données natives enregistrées et prêtes à synchroniser.',
        ]);
    }

    public function saveOssatNative(Request $request): JsonResponse
    {
        $request->validate([
            'site_id' => ['required', 'integer'],
            'payload' => ['required', 'array'],
        ]);

        $user = $this->resolveNativeUser($request);
        $site = Site::query()->findOrFail((int) $request->input('site_id'));
        $this->mobileSiteAccess->assertCanCollect($user, $site);
        $userId = $user->id;
        $payload = $request->input('payload');
        $payload['site_id'] = $request->input('site_id');
        $payload['created_by'] = $userId;
        $payload['statut_validation'] = ($payload['action'] ?? null) === 'soumettre' ? 'soumis' : 'brouillon';

        if (!empty($payload['site_id'])) {
            $site = Site::find($payload['site_id']);
            if ($site) {
                $payload['site_nom'] = $site->nom;
                $payload['site_code'] = $site->code_site;
            }
        }

        $booleans = [
            'fait_partie_agence','nouveau_site','agence_gestion','gestionnaire_dedie','gestionnaire_accepte_partage','agence_admin','admin_dedie','admin_accepte_partage','agence_coord','bureau_dedie','presence_comite','comites_elus','reunions_coordination','equipe_mobile_soutien','cci','mgp','pdi_nouvelles_arrivees','pdi_retours','reduction_prevue','mesures_incendie','eclairage_existant','qualite_eau','defecation_plein_air','savon_disponible','inondations_6mois','douches_separees','latrines_vidangees','eclairage_latrines','wash_adapte_handicapes','soin_sante_fonctionnel','soin_sante_interieur','services_urgences','services_chirurgicaux','services_pediatriques','services_prenataux','ambulance','stockage_magasin','restrictions_mouvement','sentiment_securite','services_handicapes','acces_tribunaux','ecole_primaire_presente','ecole_secondaire_presente','education_informelle','marche_interieur','enclos_betail'
        ];
        foreach ($booleans as $field) {
            if (array_key_exists($field, $payload)) {
                $payload[$field] = !empty($payload[$field]) ? true : false;
            }
        }

        $multiselects = ['comites','equipe_mobile','info_source','raisons_retours','types_abri','ame_prioritaires','ame_harmattan','ame_saison_seche','strategies_ame','sources_electricite','sources_eau','types_latrines','types_douches','problemes_sante','problemes_acces_sante','defis_alimentation','types_restrictions','acteurs_incidents','menaces_site','zones_dangereuses_femmes','zones_dangereuses_hommes','types_support_psy','obstacles_education','articles_non_disponibles','sources_subsistance'];
        foreach ($multiselects as $field) {
            if (!isset($payload[$field]) || !is_array($payload[$field])) {
                $payload[$field] = [];
            }
        }

        $report = \App\Models\OssatReport::create($payload);

        return response()->json([
            'success' => true,
            'stored' => true,
            'id' => $report->id,
            'status' => $report->statut_validation,
            'message' => 'Formulaire OSSAT enregistré depuis l’application mobile.',
        ]);
    }

    public function sync(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = $user->id;
        $records = $request->input('records', []);

        if (!is_array($records) || empty($records)) {
            $records = MobileCollectionSubmission::query()
                ->when($userId !== null, fn ($query) => $query->where('user_id', $userId))
                ->whereIn('status', ['pending', 'failed'])
                ->orderBy('created_at')
                ->get()->toArray();
        }

        $processed = 0;

        foreach ($records as $record) {
            $submission = null;
            $payload = is_array($record) ? ($record['payload'] ?? []) : [];
            $mobileRecordId = is_array($record) ? trim((string) ($record['id'] ?? '')) : '';

            if ($mobileRecordId !== '') {
                $submission = ctype_digit($mobileRecordId)
                    ? MobileCollectionSubmission::query()
                        ->where('user_id', $userId)
                        ->find((int) $mobileRecordId)
                    : MobileCollectionSubmission::query()
                        ->where('user_id', $userId)
                        ->where('type', $record['type'] ?? 'sector')
                        ->where('payload->mobile_record_id', $mobileRecordId)
                        ->first();
            }

            if (!$submission && isset($record['site_id'])) {
                $recordPayload = is_array($payload) ? $payload : [];
                if ($mobileRecordId !== '') {
                    $recordPayload['mobile_record_id'] = $mobileRecordId;
                }
                $submission = MobileCollectionSubmission::create([
                    'user_id' => Auth::id(),
                    'site_id' => $this->resolveSubmissionSiteId($record['site_id'], $recordPayload, $user),
                    'type' => $record['type'] ?? 'sector',
                    'sector' => $record['sector'] ?? null,
                    'payload' => $recordPayload,
                    'status' => 'pending',
                ]);
            }

            if (!$submission) {
                continue;
            }
            if ($submission->status === 'synced') {
                $processed++;
                continue;
            }

            if (is_array($record) && isset($record['site_id'])) {
                $this->refreshSubmissionFromSyncRecord($submission, $record, $payload, $mobileRecordId, $user);
            }

            try {
                $this->applySubmission($submission);
                $submission->status = 'synced';
                $submission->synced_at = now();
                $submission->sync_error = null;
                $submission->save();
                $processed++;
            } catch (\Throwable $e) {
                $submission->status = 'failed';
                $submission->sync_error = $e->getMessage();
                $submission->save();
            }
        }

        return response()->json([
            'success' => true,
            'processed' => $processed,
            'message' => $processed > 0
                ? 'Synchronisation terminée avec succès.'
                : 'Aucune donnée en attente de synchronisation.',
        ]);
    }

    public function syncNative(Request $request): JsonResponse
    {
        $user = $this->resolveNativeUser($request);
        $userId = $user->id;
        $records = $request->input('records', []);

        if (!is_array($records) || empty($records)) {
            $records = MobileCollectionSubmission::query()
                ->where('user_id', $userId)
                ->whereIn('status', ['pending', 'failed'])
                ->orderBy('created_at')
                ->get()->toArray();
        }

        $processed = 0;
        $errors = [];

        foreach ($records as $record) {
            $submission = null;
            $payload = is_array($record) ? ($record['payload'] ?? []) : [];
            $mobileRecordId = is_array($record) ? trim((string) ($record['id'] ?? '')) : '';

            if ($mobileRecordId !== '') {
                $submission = ctype_digit($mobileRecordId)
                    ? MobileCollectionSubmission::query()
                        ->where('user_id', $userId)
                        ->find((int) $mobileRecordId)
                    : MobileCollectionSubmission::query()
                        ->where('user_id', $userId)
                        ->where('type', $record['type'] ?? 'sector')
                        ->where('payload->mobile_record_id', $mobileRecordId)
                        ->first();
            }

            if (!$submission && isset($record['site_id'])) {
                $recordPayload = is_array($payload) ? $payload : [];
                if ($mobileRecordId !== '') {
                    $recordPayload['mobile_record_id'] = $mobileRecordId;
                }
                $submission = MobileCollectionSubmission::create([
                    'user_id' => $userId,
                    'site_id' => $this->resolveSubmissionSiteId($record['site_id'], $recordPayload, $user),
                    'type' => $record['type'] ?? 'sector',
                    'sector' => $record['sector'] ?? null,
                    'payload' => $recordPayload,
                    'status' => 'pending',
                ]);
            }

            if (!$submission) {
                continue;
            }
            if ($submission->status === 'synced') {
                $processed++;
                continue;
            }

            if (is_array($record) && isset($record['site_id'])) {
                $this->refreshSubmissionFromSyncRecord($submission, $record, $payload, $mobileRecordId, $user);
            }

            try {
                $this->applySubmission($submission);
                $submission->status = 'synced';
                $submission->synced_at = now();
                $submission->sync_error = null;
                $submission->save();
                $processed++;
            } catch (\Throwable $e) {
                $validationErrors = $e instanceof ValidationException ? $e->errors() : [];
                $message = $e instanceof ValidationException
                    ? (collect($validationErrors)->flatten()->first() ?? $e->getMessage())
                    : $e->getMessage();
                $fieldErrors = collect($validationErrors)
                    ->flatMap(function (array $messages, string $field): array {
                        return collect($messages)->map(function (string $fieldMessage) use ($field): array {
                            $detail = [
                                'field' => $field,
                                'message' => $fieldMessage,
                            ];

                            if (preg_match(
                                '/:\s*(.+?) disponible = (-?\d+), mouvement = (-?\d+), solde projeté = (-?\d+)/u',
                                $fieldMessage,
                                $matches
                            )) {
                                $detail['label'] = trim($matches[1]);
                                $detail['available'] = (int) $matches[2];
                                $detail['movement'] = (int) $matches[3];
                                $detail['projected'] = (int) $matches[4];
                            }

                            return $detail;
                        })->all();
                    })
                    ->values()
                    ->all();
                $submission->status = 'failed';
                $submission->sync_error = $message;
                $submission->save();
                $errors[] = [
                    'record_id' => is_array($record) ? ($record['id'] ?? null) : null,
                    'message' => $message,
                    'field_errors' => $fieldErrors,
                ];
            }
        }

        return response()->json([
            'success' => count($errors) === 0,
            'processed' => $processed,
            'failed' => count($errors),
            'errors' => $errors,
            'message' => count($errors) > 0
                ? 'Certaines données natives n’ont pas pu être synchronisées.'
                : ($processed > 0 ? 'Synchronisation native terminée.' : 'Aucune donnée native en attente.'),
        ], count($errors) > 0 ? 422 : 200);
    }

    private function refreshSubmissionFromSyncRecord(
        MobileCollectionSubmission $submission,
        array $record,
        mixed $payload,
        string $mobileRecordId,
        User $user
    ): void {
        $recordPayload = is_array($payload) ? $payload : [];
        if ($mobileRecordId !== '') {
            $recordPayload['mobile_record_id'] = $mobileRecordId;
        }

        $submission->site_id = $this->resolveSubmissionSiteId($record['site_id'], $recordPayload, $user);
        $submission->type = $record['type'] ?? $submission->type;
        $submission->sector = $record['sector'] ?? null;
        $submission->payload = $recordPayload;
        $submission->status = 'pending';
        $submission->synced_at = null;
        $submission->sync_error = null;
        $submission->save();
    }

    protected function resolveOrCreateSiteId(array $payload, ?int $fallbackSiteId, User $user): int
    {
        $siteId = (int) ($fallbackSiteId ?? ($payload['site_id'] ?? 0));
        if ($siteId > 0) {
            $site = Site::query()->findOrFail($siteId);
            $this->mobileSiteAccess->assertCanCollect($user, $site);

            return $siteId;
        }

        if (!empty($payload['is_new_site']) && is_array($payload['new_site'] ?? null)) {
            return (int) $this->createSiteFromPayload($payload['new_site'], $payload, $user)->id;
        }

        throw new \RuntimeException('Identifiant du site requis pour la synchronisation.');
    }

    protected function resolveSubmissionSiteId(mixed $siteId, array $payload, User $user): ?int
    {
        $isNewSite = filter_var($payload['is_new_site'] ?? false, FILTER_VALIDATE_BOOLEAN)
            || (isset($payload['new_site']) && is_array($payload['new_site']));
        if ($isNewSite) {
            return null;
        }

        if (is_numeric($siteId)) {
            $normalized = (int) $siteId;
            $site = $normalized > 0 ? Site::query()->find($normalized) : null;
            if ($site) {
                $this->mobileSiteAccess->assertCanCollect($user, $site);
                return $normalized;
            }
            return null;
        }

        if (is_numeric($payload['site_id'] ?? null)) {
            $normalized = (int) $payload['site_id'];
            $site = $normalized > 0 ? Site::query()->find($normalized) : null;
            if ($site) {
                $this->mobileSiteAccess->assertCanCollect($user, $site);
                return $normalized;
            }
            return null;
        }

        return null;
    }

    protected function createSiteFromPayload(array $newSite, array $payload, User $user): Site
    {
        return $this->mobileSiteAccess->createSiteForCollector($user, [
            'nom' => trim((string) ($newSite['nom'] ?? '')) !== ''
                ? trim((string) ($newSite['nom'] ?? ''))
                : 'Site mobile '.now()->format('YmdHis'),
            'code_site' => $this->nullableString($newSite['code_site'] ?? null),
            'type_site_id' => $this->nullableInt($newSite['type_site_id'] ?? null),
            'commune_id' => $this->nullableInt($newSite['commune_id'] ?? ($payload['commune_id'] ?? null)),
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
            'latitude' => $this->nullableFloat($newSite['latitude'] ?? $payload['latitude'] ?? null),
            'longitude' => $this->nullableFloat($newSite['longitude'] ?? $payload['longitude'] ?? null),
            'source' => $this->nullableString($newSite['source'] ?? 'mobile'),
            'round' => $this->nullableString($newSite['round'] ?? null),
            'type_gestion' => $this->nullableString($newSite['type_gestion'] ?? null),
            'date_mise_a_jour' => $this->nullableString($newSite['date_mise_a_jour'] ?? now()->toDateString()),
            'type_fichier' => $this->nullableString($newSite['type_fichier'] ?? null),
            'geometry_type' => $this->nullableString($newSite['geometry_type'] ?? $payload['geometry_type'] ?? 'point'),
            'collection_accuracy_m' => $this->nullableFloat($newSite['collection_accuracy_m'] ?? $payload['accuracy_meters'] ?? null),
            'geometry_collected_at' => now(),
            'date_fermeture' => $this->nullableString($newSite['date_fermeture'] ?? null),
            'raison_fermeture' => $this->nullableString($newSite['raison_fermeture'] ?? null),
            'commentaire_fermeture' => $this->nullableString($newSite['commentaire_fermeture'] ?? null),
            'document_fermeture' => $this->nullableString($newSite['document_fermeture'] ?? null),
        ]);
    }

    protected function nullableString(mixed $value): ?string
    {
        $string = trim((string) ($value ?? ''));
        return $string === '' ? null : $string;
    }

    protected function nullableInt(mixed $value): ?int
    {
        $string = trim((string) ($value ?? ''));
        if ($string === '' || !is_numeric($string)) {
            return null;
        }

        return (int) $string;
    }

    protected function nullableFloat(mixed $value): ?float
    {
        $string = trim((string) ($value ?? ''));
        if ($string === '' || !is_numeric($string)) {
            return null;
        }

        return (float) $string;
    }

    protected function computePolygonPrecisionMetrics(?array $geojson): array
    {
        $geometry = is_array($geojson['features'][0]['geometry'] ?? null)
            ? $geojson['features'][0]['geometry']
            : null;
        if (!$geometry || strtolower((string) ($geometry['type'] ?? '')) !== 'polygon') {
            return [
                'distances' => [],
                'min' => null,
                'max' => null,
                'avg' => null,
                'perimeter' => null,
                'point_count' => null,
            ];
        }

        $ring = $geometry['coordinates'][0] ?? [];
        if (!is_array($ring) || count($ring) < 3) {
            return [
                'distances' => [],
                'min' => null,
                'max' => null,
                'avg' => null,
                'perimeter' => null,
                'point_count' => is_array($ring) ? count($ring) : null,
            ];
        }

        $points = [];
        foreach ($ring as $coord) {
            if (!is_array($coord) || count($coord) < 2) {
                continue;
            }
            $lon = (float) $coord[0];
            $lat = (float) $coord[1];
            $points[] = [$lat, $lon];
        }

        if (count($points) < 3) {
            return [
                'distances' => [],
                'min' => null,
                'max' => null,
                'avg' => null,
                'perimeter' => null,
                'point_count' => count($points),
            ];
        }

        $first = $points[0];
        $last = $points[count($points) - 1];
        if (abs($first[0] - $last[0]) > 1e-9 || abs($first[1] - $last[1]) > 1e-9) {
            $points[] = $first;
        }

        $distances = [];
        for ($i = 0; $i < count($points) - 1; $i++) {
            $start = $points[$i];
            $end = $points[$i + 1];
            $distances[] = round($this->haversineMeters($start[0], $start[1], $end[0], $end[1]), 2);
        }

        if ($distances === []) {
            return [
                'distances' => [],
                'min' => null,
                'max' => null,
                'avg' => null,
                'perimeter' => null,
                'point_count' => count($points),
            ];
        }

        $perimeter = array_sum($distances);

        return [
            'distances' => $distances,
            'min' => min($distances),
            'max' => max($distances),
            'avg' => round($perimeter / count($distances), 2),
            'perimeter' => round($perimeter, 2),
            'point_count' => max(0, count($points) - 1),
        ];
    }

    protected function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos($lat1Rad) * cos($lat2Rad) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    protected function applySubmission(MobileCollectionSubmission $submission): void
    {
        $payload = $submission->payload ?? [];
        $user = User::query()->findOrFail($submission->user_id);
        $newSitePopulation = null;
        if (($submission->type ?? 'sector') === 'geography') {
            $this->assertAcceptableGeographyAccuracy($payload['accuracy_meters'] ?? null);
            if (
                filter_var($payload['is_new_site'] ?? false, FILTER_VALIDATE_BOOLEAN)
                && is_array($payload['new_site'] ?? null)
            ) {
                $newSitePopulation = $this->validateNewSitePopulation($payload['new_site']);
            }
        }

        $siteId = $this->resolveOrCreateSiteId($payload, $submission->site_id, $user);
        if ((int) ($submission->site_id ?? 0) !== (int) $siteId) {
            $submission->site_id = $siteId;
        }

        if (($submission->type ?? 'sector') === 'geography') {
            $site = Site::findOrFail($siteId);
            $geojson = $this->geoJsonService->normalize(
                is_array($payload['geojson'] ?? null) ? $payload['geojson'] : null
            );
            $featureProperties = is_array($geojson['features'][0]['properties'] ?? null)
                ? $geojson['features'][0]['properties']
                : [];
            $polygonMetrics = $this->computePolygonPrecisionMetrics($geojson);
            $latitude = $payload['latitude'] ?? null;
            $longitude = $payload['longitude'] ?? null;

            SiteGeography::query()->create([
                'site_id' => $site->id,
                'mobile_collection_submission_id' => $submission->id,
                'user_id' => $submission->user_id,
                'geometry_type' => $payload['geometry_type'] ?? null,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy_meters' => $payload['accuracy_meters'] ?? null,
                'point_category' => $payload['point_category'] ?? null,
                'point_category_other' => $payload['point_category_other'] ?? null,
                'polygon_category' => $payload['polygon_category'] ?? null,
                'polygon_block_name' => $payload['polygon_block_name'] ?? null,
                'geojson_data' => $geojson,
                'polygon_segment_distances_m' => $polygonMetrics['distances'],
                'polygon_segment_min_m' => $polygonMetrics['min'],
                'polygon_segment_max_m' => $polygonMetrics['max'],
                'polygon_segment_avg_m' => $polygonMetrics['avg'],
                'polygon_perimeter_m' => $polygonMetrics['perimeter'],
                'polygon_point_count' => $polygonMetrics['point_count'],
                'collected_at' => !empty($payload['date_collecte']) ? $payload['date_collecte'].' 00:00:00' : now(),
                'source' => 'mobile',
                'meta' => [
                    'campaign_id' => $payload['campaign_id'] ?? null,
                    'periode_collecte' => $payload['periode_collecte'] ?? null,
                    'province_id' => $payload['province_id'] ?? null,
                    'territoire_id' => $payload['territoire_id'] ?? null,
                    'commune_id' => $payload['commune_id'] ?? null,
                    'point_category' => $featureProperties['point_category'] ?? ($payload['point_category'] ?? null),
                    'polygon_category' => $featureProperties['polygon_category'] ?? ($payload['polygon_category'] ?? null),
                    'polygon_segment_distances_m' => $polygonMetrics['distances'],
                    'polygon_segment_avg_m' => $polygonMetrics['avg'],
                ],
            ]);

            if ($newSitePopulation !== null) {
                SiteMouvementPopulation::query()->firstOrCreate(
                    [
                        'site_id' => $site->id,
                        'source' => 'mobile_geography',
                        'round' => 'submission-'.$submission->id,
                    ],
                    [
                        ...$newSitePopulation,
                        'date_mouvement' => $payload['date_collecte'] ?? now()->toDateString(),
                        'type_mouvement' => 'recensement',
                        'periode' => $payload['periode_collecte'] ?? null,
                        'raison' => 'Population initiale collectée lors de la création mobile du site.',
                        'created_by' => $submission->user_id,
                        'statut' => 'en_attente',
                    ]
                );
            }

            // Garder les colonnes de synthèse du site pour compatibilité des écrans existants.
            $site->latitude = $latitude ?? $site->latitude;
            $site->longitude = $longitude ?? $site->longitude;
            $site->geojson_data = $geojson ?? $site->geojson_data;
            $site->geometry_type = $payload['geometry_type'] ?? $site->geometry_type;
            $site->collection_accuracy_m = $payload['accuracy_meters'] ?? $site->collection_accuracy_m;
            $site->geometry_collected_at = now();
            $site->date_mise_a_jour = now()->toDateString();
            $site->save();
            return;
        }

        if (($submission->type ?? 'sector') === 'ossat') {
            $payload['site_id'] = $siteId;
            $payload['created_by'] = $submission->user_id ?? Auth::id();
            $payload['statut_validation'] = $payload['statut'] ?? 'brouillon';
            if (!isset($payload['date_collecte']) && isset($payload['date'])) {
                $payload['date_collecte'] = $payload['date'];
            }

            OssatReport::create($payload);
            return;
        }

        if (($submission->type ?? 'sector') === 'questionnaire') {
            $questionnaireCode = (string) ($payload['questionnaire_code'] ?? 'service-cartography');
            $questionnaire = MobileQuestionnaire::query()
                ->where('code', $questionnaireCode)
                ->where('is_active', true)
                ->orderByDesc('version')
                ->first();

            if (! $questionnaire) {
                throw new \RuntimeException('Questionnaire mobile introuvable pour la synchronisation.');
            }

            MobileQuestionnaireSubmission::query()->create([
                'questionnaire_id' => $questionnaire->id,
                'user_id' => $submission->user_id,
                'site_id' => $siteId,
                'province_id' => $payload['province_id'] ?? null,
                'territoire_id' => $payload['territoire_id'] ?? null,
                'commune_id' => $payload['commune_id'] ?? null,
                'date_collecte' => $payload['date_collecte'] ?? now()->toDateString(),
                'answers' => $payload['answers'] ?? [],
                'status' => 'submitted',
                'synced_at' => now(),
            ]);
            return;
        }

        if (($submission->type ?? 'sector') === 'movement') {
            $populationFields = [
                'menages',
                'individus',
                'f_0_5',
                'f_6_17',
                'f_18_59',
                'f_60_plus',
                'h_0_5',
                'h_6_17',
                'h_18_59',
                'h_60_plus',
            ];
            $type = (string) ($payload['type_mouvement'] ?? '');
            $integerRule = in_array($type, ['depart', 'ajustement'], true)
                ? ['required', 'integer']
                : ['required', 'integer', 'min:0'];
            $rules = [
                'date_mouvement' => ['required', 'date'],
                'type_mouvement' => ['required', 'in:arrivee,depart,recensement,ajustement'],
                'raison_mouvement_id' => ['nullable', 'integer', 'exists:raison_mouvements,id'],
                'periode' => ['nullable', 'string', 'max:255'],
                'raison' => ['nullable', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'source' => ['nullable', 'string', 'max:255'],
                'round' => ['nullable', 'string', 'max:255'],
            ];
            foreach ($populationFields as $field) {
                $rules[$field] = $integerRule;
            }

            $validated = Validator::make($payload, $rules)->validate();
            if ($type === 'depart') {
                $values = collect($populationFields)->map(fn (string $field) => (int) $validated[$field]);
                $allNonPositive = $values->every(fn (int $value) => $value <= 0);
                $allNonNegative = $values->every(fn (int $value) => $value >= 0);
                if (! $allNonPositive && ! $allNonNegative) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'type_mouvement' => 'Un départ ne peut pas mélanger des effectifs positifs et négatifs.',
                    ]);
                }
            }
            $demographicTotal = collect([
                'f_0_5',
                'f_6_17',
                'f_18_59',
                'f_60_plus',
                'h_0_5',
                'h_6_17',
                'h_18_59',
                'h_60_plus',
            ])->sum(fn (string $field) => (int) $validated[$field]);

            if ((int) $validated['individus'] !== $demographicTotal) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'individus' => 'Le total des individus doit correspondre à la somme des groupes d’âge et de sexe.',
                ]);
            }

            foreach ($populationFields as $field) {
                $value = (int) $validated[$field];
                $validated[$field] = $type === 'depart' ? -abs($value) : ($type === 'ajustement' ? $value : abs($value));
            }

            $negativeProjections = app(SitePopulationService::class)
                ->negativeProjections((int) $siteId, $validated, $type);
            if ($negativeProjections !== []) {
                $siteName = Site::query()->whereKey($siteId)->value('nom') ?? "Site {$siteId}";
                throw ValidationException::withMessages(collect($negativeProjections)->mapWithKeys(
                    fn (array $violation): array => [
                        $violation['field'] => sprintf(
                        'Synchronisation refusée pour %s : %s disponible = %d, mouvement = %d, solde projeté = %d. Corrigez les effectifs avant de réessayer.',
                        $siteName,
                        $violation['label'],
                        $violation['current'],
                        $violation['movement'],
                        $violation['projected']
                        ),
                    ]
                )->all());
            }

            SiteMouvementPopulation::query()->create([
                ...$validated,
                'site_id' => $siteId,
                'source' => $validated['source'] ?? 'application_mobile',
                'created_by' => $submission->user_id,
                'statut' => 'en_attente',
            ]);
            return;
        }

        $sector = $submission->sector ?? ($payload['sector'] ?? 'wash');
        $profile = ServiceProfile::firstOrNew([
            'site_id' => $siteId,
            'date_collecte' => $payload['date_collecte'] ?? now()->toDateString(),
            'collecteur_id' => $submission->user_id,
        ]);

        $fieldMap = $this->sectorMap[$sector] ?? [];

        foreach ($fieldMap as $field) {
            if (array_key_exists($field, $payload)) {
                $profile->{$field} = $payload[$field];
            }
        }

        $profile->site_id = $siteId;
        $profile->date_collecte = $payload['date_collecte'] ?? now()->toDateString();
        $profile->collecteur_id = $submission->user_id;
        $profile->statut = $payload['statut'] ?? 'soumis';
        $profile->notes_generales = $payload['notes_generales'] ?? $profile->notes_generales;
        $groupKey = match ($sector) {
            'protection' => 'gestion',
            'abri' => 'abri_ame',
            default => $sector,
        };
        $profile->groupes_collectes = array_values(array_unique([
            ...$profile->collectedGroupKeys(),
            $groupKey,
        ]));
        $profile->save();
    }

    private function assertAcceptableGeographyAccuracy(mixed $accuracy): void
    {
        if (!is_numeric($accuracy) || (float) $accuracy > self::MAX_GEOGRAPHY_ACCURACY_METERS) {
            throw new \RuntimeException(
                'Précision GPS insuffisante : une mesure de 15 mètres ou moins est obligatoire.'
            );
        }
    }

    private function validateNewSitePopulation(array $newSite): array
    {
        $population = [];

        foreach (SitePopulationService::FIELDS as $field) {
            $value = $newSite[$field] ?? null;
            if (
                $value === null
                || $value === ''
                || filter_var($value, FILTER_VALIDATE_INT) === false
                || (int) $value < 0
            ) {
                throw new \RuntimeException(
                    'Population initiale invalide : tous les effectifs doivent être des entiers positifs ou nuls.'
                );
            }
            $population[$field] = (int) $value;
        }

        if ($population['menages'] <= 0 || $population['individus'] <= 0) {
            throw new \RuntimeException(
                'Population initiale invalide : les ménages et les individus doivent être supérieurs à zéro.'
            );
        }

        $demographicTotal = collect([
            'f_0_5',
            'f_6_17',
            'f_18_59',
            'f_60_plus',
            'h_0_5',
            'h_6_17',
            'h_18_59',
            'h_60_plus',
        ])->sum(fn (string $field): int => $population[$field]);

        if ($demographicTotal !== $population['individus']) {
            throw new \RuntimeException(
                'Population initiale incohérente : la somme des groupes d’âge et de sexe doit correspondre aux individus.'
            );
        }

        return $population;
    }
}
