<?php

namespace App\Http\Controllers;

use App\Models\ProjectStatus;
use App\Models\Project;
use App\Models\Cluster;
use App\Models\ProgramActivity;
use App\Models\ProgramIndicator;
use App\Models\ProgramSubActivity;
use App\Models\Province;
use App\Models\Site;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrganisationProjectController extends Controller
{
    /**
     * Display a listing of the organisation's projects.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user->organisation_id) {
            abort(403, 'Vous devez être membre d\'une organisation pour accéder à cette page.');
        }

        $query = Project::with(['executionZones.province', 'executionZones.territoire', 'executionZones.commune'])
            ->where('organisation_id', $user->organisation_id)
            ->latest();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('code', 'like', '%' . $request->search . '%');
            });
        }

        $projects = $query->paginate(15);

        $provinces = Province::orderBy('name')->get(['id', 'name']);
        $sites = Site::where('organisation_id', $user->organisation_id)
            ->orderBy('nom')
            ->get(['id', 'nom', 'code_site', 'commune_id']);

        $programHierarchy = ProgramIndicator::with(['activities.subActivities'])
            ->orderBy('code')
            ->get(['id', 'code', 'label'])
            ->map(function ($indicator) {
                return [
                    'id' => $indicator->id,
                    'code' => $indicator->code,
                    'label' => $indicator->label,
                    'activities' => $indicator->activities->map(function ($activity) {
                        return [
                            'id' => $activity->id,
                            'code' => $activity->code,
                            'label' => $activity->label,
                            'sub_activities' => $activity->subActivities->map(function ($sub) {
                                return [
                                    'id' => $sub->id,
                                    'code' => $sub->code,
                                    'label' => $sub->label,
                                ];
                            })->values(),
                        ];
                    })->values(),
                ];
            })
            ->values();

        return view('organisation.projects.index', [
            'projects' => $projects,
            'provinces' => $provinces,
            'sites' => $sites,
            'programHierarchy' => $programHierarchy,
        ]);
    }

    private function buildProgramHierarchyByCluster(int $organisationId): array
    {
        $clusters = Cluster::whereHas('organisations', fn ($q) => $q->where('organisations.id', $organisationId))
            ->where('is_active', true)
            ->with([
                'sectorObjectives.strategicObjectives.indicators.activities.subActivities',
            ])
            ->orderBy('name')
            ->get();

        return $clusters->map(function ($cluster) {
            $indicators = $cluster->sectorObjectives
                ->flatMap(fn ($so) => $so->strategicObjectives)
                ->flatMap(fn ($strat) => $strat->indicators)
                ->map(fn ($indicator) => [
                    'id'         => $indicator->id,
                    'code'       => $indicator->code,
                    'label'      => $indicator->label,
                    'activities' => $indicator->activities->map(fn ($act) => [
                        'id'             => $act->id,
                        'code'           => $act->code,
                        'label'          => $act->label,
                        'sub_activities' => $act->subActivities->map(fn ($sub) => [
                            'id'    => $sub->id,
                            'code'  => $sub->code,
                            'label' => $sub->label,
                        ])->values()->toArray(),
                    ])->values()->toArray(),
                ])
                ->values()
                ->toArray();

            return [
                'id'         => $cluster->id,
                'code'       => $cluster->code,
                'name'       => $cluster->name,
                'indicators' => $indicators,
            ];
        })->values()->toArray();
    }

    /**
     * Show the form for creating a new project.
     */
    public function create()
    {
        $this->ensureUserHasOrganisation();
        $orgId = auth()->user()->organisation_id;

        return view('organisation.projects.create', [
            'provinces'               => Province::orderBy('name')->get(['id', 'name']),
            'availableStatuses'       => ProjectStatus::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'programIndicators'       => collect(),
            'sites'                   => Site::where('organisation_id', $orgId)->orderBy('nom')->get(['id', 'nom', 'code_site']),
            'programHierarchyByCluster' => $this->buildProgramHierarchyByCluster($orgId),
        ]);
    }

    /**
     * Store a newly created project in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $this->ensureUserHasOrganisation();

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'cluster_id'     => ['required', 'exists:clusters,id'],
            'code'           => [
                'nullable', 'string', 'max:50',
                Rule::unique('projects', 'code')->where(fn ($q) => $q->where('organisation_id', $user->organisation_id)),
            ],
            'description'    => ['nullable', 'string'],
            'start_date'     => ['nullable', 'date'],
            'end_date'       => ['nullable', 'date', 'after_or_equal:start_date'],
            'funding_amount' => ['nullable', 'numeric', 'min:0'],
            'status'         => ['required', 'exists:project_statuses,code'],
            'donors'         => ['nullable', 'array'],
            'donors.*'       => ['string', 'max:255'],
            'beneficiaries_female_0_17'   => ['nullable', 'integer', 'min:0'],
            'beneficiaries_female_18_59'  => ['nullable', 'integer', 'min:0'],
            'beneficiaries_female_60_plus'=> ['nullable', 'integer', 'min:0'],
            'beneficiaries_male_0_17'     => ['nullable', 'integer', 'min:0'],
            'beneficiaries_male_18_59'    => ['nullable', 'integer', 'min:0'],
            'beneficiaries_male_60_plus'  => ['nullable', 'integer', 'min:0'],
            'execution_zones'             => ['required', 'array', 'min:1'],
            'execution_zones.*.province_id'   => ['required', 'exists:provinces,id'],
            'execution_zones.*.territoire_id' => ['required', 'exists:territoires,id'],
            'execution_zones.*.commune_id'    => ['required', 'exists:communes,id'],
            'activities' => ['nullable', 'array'],
            'activities.*.activity_name' => ['nullable', 'string', 'max:255'],
            'activities.*.program_indicator_id' => ['nullable', 'exists:program_indicators,id'],
            'activities.*.program_activity_id' => ['nullable', 'exists:program_activities,id'],
            'activities.*.program_sub_activity_ids' => ['nullable', 'array'],
            'activities.*.program_sub_activity_ids.*' => ['nullable', 'exists:program_sub_activities,id'],
            'activities.*.activity_cost' => ['nullable', 'numeric', 'min:0'],
            'activities.*.site_id' => ['nullable', 'exists:sites,id'],
            'activities.*.province_id' => ['nullable', 'exists:provinces,id'],
            'activities.*.territoire_id' => ['nullable', 'exists:territoires,id'],
            'activities.*.commune_id' => ['nullable', 'exists:communes,id'],
            'activities.*.statut_beneficiaire' => ['nullable', 'array'],
            'activities.*.statut_beneficiaire.*' => ['string', Rule::in(['pdi', 'retourne', 'refugie', 'communaute_hote', 'autre'])],
            'activities.*.beneficiaries_by_status' => ['nullable', 'array'],
            'activities.*.beneficiaries_by_status.*.girls_0_17' => ['nullable', 'integer', 'min:0'],
            'activities.*.beneficiaries_by_status.*.girls_18_59' => ['nullable', 'integer', 'min:0'],
            'activities.*.beneficiaries_by_status.*.girls_60_plus' => ['nullable', 'integer', 'min:0'],
            'activities.*.beneficiaries_by_status.*.boys_0_17' => ['nullable', 'integer', 'min:0'],
            'activities.*.beneficiaries_by_status.*.boys_18_59' => ['nullable', 'integer', 'min:0'],
            'activities.*.beneficiaries_by_status.*.boys_60_plus' => ['nullable', 'integer', 'min:0'],
            'activities.*.girls_0_17' => ['nullable', 'integer', 'min:0'],
            'activities.*.girls_18_59' => ['nullable', 'integer', 'min:0'],
            'activities.*.girls_60_plus' => ['nullable', 'integer', 'min:0'],
            'activities.*.boys_0_17' => ['nullable', 'integer', 'min:0'],
            'activities.*.boys_18_59' => ['nullable', 'integer', 'min:0'],
            'activities.*.boys_60_plus' => ['nullable', 'integer', 'min:0'],
            'activities.*.persons_with_disabilities' => ['nullable', 'integer', 'min:0'],
            'activities.*.comment' => ['nullable', 'string'],
            'activities.*.reporting_date' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($validated, $user) {
            $donors = array_values(array_filter(array_map('trim', $validated['donors'] ?? [])));
            $zones  = collect($validated['execution_zones'])
                ->unique(fn ($z) => $z['province_id'].':'.$z['territoire_id'].':'.$z['commune_id'])
                ->values();
            $activities = $this->expandActivitiesPayload($validated['activities'] ?? []);

            $project = Project::create([
                'organisation_id'             => $user->organisation_id,
                'cluster_id'                  => $validated['cluster_id'],
                'name'                        => $validated['name'],
                'code'                        => $validated['code'] ?? null,
                'description'                 => $validated['description'] ?? null,
                'status'                      => $validated['status'],
                'start_date'                  => $validated['start_date'] ?? null,
                'end_date'                    => $validated['end_date'] ?? null,
                'funding_amount'              => $validated['funding_amount'] ?? null,
                'donors_json'                 => $donors ?: null,
                'beneficiaries_female_0_17'   => $validated['beneficiaries_female_0_17'] ?? 0,
                'beneficiaries_female_18_59'  => $validated['beneficiaries_female_18_59'] ?? 0,
                'beneficiaries_female_60_plus'=> $validated['beneficiaries_female_60_plus'] ?? 0,
                'beneficiaries_male_0_17'     => $validated['beneficiaries_male_0_17'] ?? 0,
                'beneficiaries_male_18_59'    => $validated['beneficiaries_male_18_59'] ?? 0,
                'beneficiaries_male_60_plus'  => $validated['beneficiaries_male_60_plus'] ?? 0,
            ]);

            $project->executionZones()->createMany($zones->all());
            if ($activities->isNotEmpty()) {
                $project->activities()->createMany($activities->all());
            }
        });

        return redirect()->route('organisation.projects.index')
            ->with('success', 'Projet créé avec succès.');
    }

    /**
     * Show the form for editing the specified project.
     */
    public function edit(Project $project)
    {
        $this->authorizeProjectAccess($project);
        $orgId = auth()->user()->organisation_id;

        $project->load(['executionZones.province', 'executionZones.territoire', 'executionZones.commune', 'activities.site']);

        return view('organisation.projects.edit', [
            'project'                 => $project,
            'provinces'               => Province::orderBy('name')->get(['id', 'name']),
            'availableStatuses'       => ProjectStatus::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'programIndicators'       => collect(),
            'sites'                   => Site::where('organisation_id', $orgId)->orderBy('nom')->get(['id', 'nom', 'code_site']),
            'programHierarchyByCluster' => $this->buildProgramHierarchyByCluster($orgId),
        ]);
    }

    /**
     * Update the specified project in storage.
     */
    public function update(Request $request, Project $project)
    {
        $user = auth()->user();
        $this->authorizeProjectAccess($project);

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'cluster_id'     => ['required', 'exists:clusters,id'],
            'code'           => [
                'nullable', 'string', 'max:50',
                Rule::unique('projects', 'code')
                    ->where(fn ($q) => $q->where('organisation_id', $user->organisation_id))
                    ->ignore($project->id),
            ],
            'description'    => ['nullable', 'string'],
            'start_date'     => ['nullable', 'date'],
            'end_date'       => ['nullable', 'date', 'after_or_equal:start_date'],
            'funding_amount' => ['nullable', 'numeric', 'min:0'],
            'status'         => ['required', 'exists:project_statuses,code'],
            'donors'         => ['nullable', 'array'],
            'donors.*'       => ['string', 'max:255'],
            'beneficiaries_female_0_17'   => ['nullable', 'integer', 'min:0'],
            'beneficiaries_female_18_59'  => ['nullable', 'integer', 'min:0'],
            'beneficiaries_female_60_plus'=> ['nullable', 'integer', 'min:0'],
            'beneficiaries_male_0_17'     => ['nullable', 'integer', 'min:0'],
            'beneficiaries_male_18_59'    => ['nullable', 'integer', 'min:0'],
            'beneficiaries_male_60_plus'  => ['nullable', 'integer', 'min:0'],
            'execution_zones'             => ['required', 'array', 'min:1'],
            'execution_zones.*.province_id'   => ['required', 'exists:provinces,id'],
            'execution_zones.*.territoire_id' => ['required', 'exists:territoires,id'],
            'execution_zones.*.commune_id'    => ['required', 'exists:communes,id'],
            'activities' => ['nullable', 'array'],
            'activities.*.activity_name' => ['nullable', 'string', 'max:255'],
            'activities.*.program_indicator_id' => ['nullable', 'exists:program_indicators,id'],
            'activities.*.program_activity_id' => ['nullable', 'exists:program_activities,id'],
            'activities.*.program_sub_activity_ids' => ['nullable', 'array'],
            'activities.*.program_sub_activity_ids.*' => ['nullable', 'exists:program_sub_activities,id'],
            'activities.*.activity_cost' => ['nullable', 'numeric', 'min:0'],
            'activities.*.site_id' => ['nullable', 'exists:sites,id'],
            'activities.*.province_id' => ['nullable', 'exists:provinces,id'],
            'activities.*.territoire_id' => ['nullable', 'exists:territoires,id'],
            'activities.*.commune_id' => ['nullable', 'exists:communes,id'],
            'activities.*.statut_beneficiaire' => ['nullable', 'array'],
            'activities.*.statut_beneficiaire.*' => ['string', Rule::in(['pdi', 'retourne', 'refugie', 'communaute_hote', 'autre'])],
            'activities.*.beneficiaries_by_status' => ['nullable', 'array'],
            'activities.*.beneficiaries_by_status.*.girls_0_17' => ['nullable', 'integer', 'min:0'],
            'activities.*.beneficiaries_by_status.*.girls_18_59' => ['nullable', 'integer', 'min:0'],
            'activities.*.beneficiaries_by_status.*.girls_60_plus' => ['nullable', 'integer', 'min:0'],
            'activities.*.beneficiaries_by_status.*.boys_0_17' => ['nullable', 'integer', 'min:0'],
            'activities.*.beneficiaries_by_status.*.boys_18_59' => ['nullable', 'integer', 'min:0'],
            'activities.*.beneficiaries_by_status.*.boys_60_plus' => ['nullable', 'integer', 'min:0'],
            'activities.*.girls_0_17' => ['nullable', 'integer', 'min:0'],
            'activities.*.girls_18_59' => ['nullable', 'integer', 'min:0'],
            'activities.*.girls_60_plus' => ['nullable', 'integer', 'min:0'],
            'activities.*.boys_0_17' => ['nullable', 'integer', 'min:0'],
            'activities.*.boys_18_59' => ['nullable', 'integer', 'min:0'],
            'activities.*.boys_60_plus' => ['nullable', 'integer', 'min:0'],
            'activities.*.persons_with_disabilities' => ['nullable', 'integer', 'min:0'],
            'activities.*.comment' => ['nullable', 'string'],
            'activities.*.reporting_date' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($project, $validated) {
            $donors = array_values(array_filter(array_map('trim', $validated['donors'] ?? [])));
            $zones  = collect($validated['execution_zones'])
                ->unique(fn ($z) => $z['province_id'].':'.$z['territoire_id'].':'.$z['commune_id'])
                ->values();
            $activities = $this->expandActivitiesPayload($validated['activities'] ?? []);

            $project->update([
                'cluster_id'                  => $validated['cluster_id'],
                'name'                        => $validated['name'],
                'code'                        => $validated['code'] ?? null,
                'description'                 => $validated['description'] ?? null,
                'status'                      => $validated['status'],
                'start_date'                  => $validated['start_date'] ?? null,
                'end_date'                    => $validated['end_date'] ?? null,
                'funding_amount'              => $validated['funding_amount'] ?? null,
                'donors_json'                 => $donors ?: null,
                'beneficiaries_female_0_17'   => $validated['beneficiaries_female_0_17'] ?? 0,
                'beneficiaries_female_18_59'  => $validated['beneficiaries_female_18_59'] ?? 0,
                'beneficiaries_female_60_plus'=> $validated['beneficiaries_female_60_plus'] ?? 0,
                'beneficiaries_male_0_17'     => $validated['beneficiaries_male_0_17'] ?? 0,
                'beneficiaries_male_18_59'    => $validated['beneficiaries_male_18_59'] ?? 0,
                'beneficiaries_male_60_plus'  => $validated['beneficiaries_male_60_plus'] ?? 0,
            ]);

            $project->executionZones()->delete();
            $project->executionZones()->createMany($zones->all());
            $project->activities()->delete();
            if ($activities->isNotEmpty()) {
                $project->activities()->createMany($activities->all());
            }
        });

        return redirect()->route('organisation.projects.index')
            ->with('success', 'Projet mis à jour avec succès.');
    }

    public function activitiesData(Project $project)
    {
        $this->authorizeProjectAccess($project);

        $project->load(['activities']);

        return response()->json([
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'activities' => $project->activities->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'activity_name' => $activity->activity_name,
                    'program_indicator_id' => $activity->program_indicator_id,
                    'program_activity_id' => $activity->program_activity_id,
                    'program_sub_activity_id' => $activity->program_sub_activity_id,
                    'program_sub_activity_ids' => $activity->program_sub_activity_id ? [$activity->program_sub_activity_id] : [],
                    'activity_cost' => $activity->activity_cost,
                    'site_id' => $activity->site_id,
                    'province_id' => $activity->province_id,
                    'territoire_id' => $activity->territoire_id,
                    'commune_id' => $activity->commune_id,
                    'statut_beneficiaire' => $this->parseStatutBeneficiaire($activity->statut_beneficiaire),
                    'beneficiaries_by_status' => $activity->beneficiaries_by_status ?? [],
                    'girls_0_17' => $activity->girls_0_17,
                    'girls_18_59' => $activity->girls_18_59,
                    'girls_60_plus' => $activity->girls_60_plus,
                    'boys_0_17' => $activity->boys_0_17,
                    'boys_18_59' => $activity->boys_18_59,
                    'boys_60_plus' => $activity->boys_60_plus,
                    'persons_with_disabilities' => $activity->persons_with_disabilities,
                    'comment' => $activity->comment,
                    'reporting_date' => optional($activity->reporting_date)->format('Y-m-d'),
                ];
            })->values(),
        ]);
    }

    public function updateActivities(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);

        $validated = $request->validate([
            'activities' => ['nullable', 'array'],
            'activities.*.activity_name' => ['nullable', 'string', 'max:255'],
            'activities.*.program_indicator_id' => ['nullable', 'exists:program_indicators,id'],
            'activities.*.program_activity_id' => ['nullable', 'exists:program_activities,id'],
            'activities.*.program_sub_activity_ids' => ['nullable', 'array'],
            'activities.*.program_sub_activity_ids.*' => ['nullable', 'exists:program_sub_activities,id'],
            'activities.*.activity_cost' => ['nullable', 'numeric', 'min:0'],
            'activities.*.site_id' => ['nullable', 'exists:sites,id'],
            'activities.*.province_id' => ['nullable', 'exists:provinces,id'],
            'activities.*.territoire_id' => ['nullable', 'exists:territoires,id'],
            'activities.*.commune_id' => ['nullable', 'exists:communes,id'],
            'activities.*.statut_beneficiaire' => ['nullable', 'array'],
            'activities.*.statut_beneficiaire.*' => ['string', Rule::in(['pdi', 'retourne', 'refugie', 'communaute_hote', 'autre'])],
            'activities.*.beneficiaries_by_status' => ['nullable', 'array'],
            'activities.*.beneficiaries_by_status.*.girls_0_17' => ['nullable', 'integer', 'min:0'],
            'activities.*.beneficiaries_by_status.*.girls_18_59' => ['nullable', 'integer', 'min:0'],
            'activities.*.beneficiaries_by_status.*.girls_60_plus' => ['nullable', 'integer', 'min:0'],
            'activities.*.beneficiaries_by_status.*.boys_0_17' => ['nullable', 'integer', 'min:0'],
            'activities.*.beneficiaries_by_status.*.boys_18_59' => ['nullable', 'integer', 'min:0'],
            'activities.*.beneficiaries_by_status.*.boys_60_plus' => ['nullable', 'integer', 'min:0'],
            'activities.*.girls_0_17' => ['nullable', 'integer', 'min:0'],
            'activities.*.girls_18_59' => ['nullable', 'integer', 'min:0'],
            'activities.*.girls_60_plus' => ['nullable', 'integer', 'min:0'],
            'activities.*.boys_0_17' => ['nullable', 'integer', 'min:0'],
            'activities.*.boys_18_59' => ['nullable', 'integer', 'min:0'],
            'activities.*.boys_60_plus' => ['nullable', 'integer', 'min:0'],
            'activities.*.persons_with_disabilities' => ['nullable', 'integer', 'min:0'],
            'activities.*.comment' => ['nullable', 'string'],
            'activities.*.reporting_date' => ['nullable', 'date'],
        ]);

        $activities = $this->expandActivitiesPayload($validated['activities'] ?? []);

        DB::transaction(function () use ($project, $activities) {
            $project->activities()->delete();
            if ($activities->isNotEmpty()) {
                $project->activities()->createMany($activities->all());
            }
        });

        return redirect()->route('organisation.projects.index')
            ->with('success', 'Activites du projet mises a jour avec succes.');
    }

    /**
     * Remove the specified project from storage.
     */
    public function destroy(Project $project)
    {
        $this->authorizeProjectAccess($project);

        $project->delete();

        return redirect()->route('organisation.projects.index')
            ->with('success', 'Projet supprimé avec succès.');
    }

    private function ensureUserHasOrganisation(): void
    {
        if (!auth()->user()->organisation_id) {
            abort(403, 'Vous devez être membre d\'une organisation pour accéder à cette page.');
        }
    }

    private function authorizeProjectAccess(Project $project): void
    {
        $user = auth()->user();

        if (!$user->organisation_id || $project->organisation_id !== $user->organisation_id) {
            abort(403, 'Vous n\'avez pas accès à ce projet.');
        }
    }

    private function expandActivitiesPayload(array $activities): \Illuminate\Support\Collection
    {
        $activities = collect($activities ?? []);

        $subIds = $activities
            ->flatMap(fn ($a) => $a['program_sub_activity_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $activityIds = $activities
            ->pluck('program_activity_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $subMap = ProgramSubActivity::with('activity')
            ->whereIn('id', $subIds)
            ->get(['id', 'label', 'program_activity_id'])
            ->keyBy('id');

        $activityMap = ProgramActivity::whereIn('id', $activityIds)
            ->get(['id', 'label', 'program_indicator_id'])
            ->keyBy('id');

        return $activities
            ->flatMap(function ($a) use ($subMap, $activityMap) {
                $selectedStatuses = $this->parseStatutBeneficiaire(
                    $this->normalizeStatutBeneficiaire($a['statut_beneficiaire'] ?? null)
                );
                $beneficiariesByStatus = $this->normalizeBeneficiariesByStatus(
                    $a['beneficiaries_by_status'] ?? [],
                    $selectedStatuses,
                    $a
                );
                $totals = $this->sumBeneficiariesByStatus($beneficiariesByStatus);

                $base = [
                    'activity_cost' => $a['activity_cost'] ?? null,
                    'site_id' => $a['site_id'] ?? null,
                    'province_id' => $a['province_id'] ?? null,
                    'territoire_id' => $a['territoire_id'] ?? null,
                    'commune_id' => $a['commune_id'] ?? null,
                    'statut_beneficiaire' => $this->normalizeStatutBeneficiaire($a['statut_beneficiaire'] ?? null),
                    'beneficiaries_by_status' => $beneficiariesByStatus,
                    'girls_0_17' => $totals['girls_0_17'],
                    'girls_18_59' => $totals['girls_18_59'],
                    'girls_60_plus' => $totals['girls_60_plus'],
                    'boys_0_17' => $totals['boys_0_17'],
                    'boys_18_59' => $totals['boys_18_59'],
                    'boys_60_plus' => $totals['boys_60_plus'],
                    'persons_with_disabilities' => $a['persons_with_disabilities'] ?? 0,
                    'comment' => $a['comment'] ?? null,
                    'reporting_date' => $a['reporting_date'] ?? null,
                ];

                $selectedSubIds = collect($a['program_sub_activity_ids'] ?? [])
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                if ($selectedSubIds->isNotEmpty()) {
                    return $selectedSubIds
                        ->map(function ($subId) use ($subMap, $activityMap, $base) {
                            $sub = $subMap->get($subId);
                            if (!$sub) {
                                return null;
                            }

                            $activity = $activityMap->get((int) $sub->program_activity_id);

                            return array_merge($base, [
                                'activity_name' => trim((string) $sub->label),
                                'program_indicator_id' => $activity?->program_indicator_id,
                                'program_activity_id' => $sub->program_activity_id,
                                'program_sub_activity_id' => $sub->id,
                            ]);
                        })
                        ->filter()
                        ->values();
                }

                $activityId = !empty($a['program_activity_id']) ? (int) $a['program_activity_id'] : null;
                $indicatorId = !empty($a['program_indicator_id']) ? (int) $a['program_indicator_id'] : null;
                $activity = $activityId ? $activityMap->get($activityId) : null;
                $activityName = trim((string) ($a['activity_name'] ?? ($activity?->label ?? '')));

                if ($activityName === '' && !$activityId && !$indicatorId) {
                    return [];
                }

                return [array_merge($base, [
                    'activity_name' => $activityName !== '' ? $activityName : ($activity?->label ?? 'Activite non specifiee'),
                    'program_indicator_id' => $indicatorId ?: ($activity?->program_indicator_id),
                    'program_activity_id' => $activityId,
                    'program_sub_activity_id' => null,
                ])];
            })
            ->values();
    }

    private function normalizeStatutBeneficiaire(array|string|null $value): ?string
    {
        $allowed = ['pdi', 'retourne', 'refugie', 'communaute_hote', 'autre'];

        if ($value === null) {
            return null;
        }

        $items = is_array($value)
            ? $value
            : preg_split('/\s*,\s*/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);

        $normalized = collect($items)
            ->map(fn ($item) => trim((string) $item))
            ->filter(fn ($item) => in_array($item, $allowed, true))
            ->unique()
            ->values();

        return $normalized->isEmpty() ? null : $normalized->implode(',');
    }

    private function parseStatutBeneficiaire(?string $value): array
    {
        if (!$value) {
            return [];
        }

        return collect(preg_split('/\s*,\s*/', $value, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeBeneficiariesByStatus(array $input, array $selectedStatuses, array $legacy): array
    {
        $allowed = ['pdi', 'retourne', 'refugie', 'communaute_hote', 'autre'];
        $keys = ['girls_0_17', 'girls_18_59', 'girls_60_plus', 'boys_0_17', 'boys_18_59', 'boys_60_plus'];

        $normalized = collect($input)
            ->filter(fn ($_, $status) => in_array((string) $status, $allowed, true))
            ->map(function ($values) use ($keys) {
                return collect($keys)->mapWithKeys(function ($key) use ($values) {
                    return [$key => max(0, (int) ($values[$key] ?? 0))];
                })->all();
            })
            ->all();

        if (!empty($normalized)) {
            return $normalized;
        }

        $legacyTotals = collect($keys)
            ->mapWithKeys(fn ($key) => [$key => max(0, (int) ($legacy[$key] ?? 0))])
            ->all();

        $hasLegacy = collect($legacyTotals)->sum() > 0;
        if (!$hasLegacy) {
            return [];
        }

        $targetStatus = $selectedStatuses[0] ?? 'autre';

        return [
            $targetStatus => $legacyTotals,
        ];
    }

    private function sumBeneficiariesByStatus(array $data): array
    {
        $keys = ['girls_0_17', 'girls_18_59', 'girls_60_plus', 'boys_0_17', 'boys_18_59', 'boys_60_plus'];
        $totals = array_fill_keys($keys, 0);

        foreach ($data as $values) {
            foreach ($keys as $key) {
                $totals[$key] += max(0, (int) ($values[$key] ?? 0));
            }
        }

        return $totals;
    }
}
