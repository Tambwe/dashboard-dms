<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectActivity;
use App\Models\ProjectExecutionZone;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrganisationDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user->organisation_id) {
            abort(403, 'Vous devez être membre d\'une organisation.');
        }

        $orgId = $user->organisation_id;

        // ── KPIs globaux ────────────────────────────────────────────────
        $projects = Project::where('organisation_id', $orgId)->get();

        $totalProjects   = $projects->count();
        $totalFunding    = $projects->sum('funding_amount');
        $totalActivities = ProjectActivity::whereIn('project_id', $projects->pluck('id'))->count();

        // ── Bénéficiaires agrégés ────────────────────────────────────────
        $benef = [
            'filles_0_17'   => (int) $projects->sum('beneficiaries_female_0_17'),
            'femmes_18_59'  => (int) $projects->sum('beneficiaries_female_18_59'),
            'femmes_60plus' => (int) $projects->sum('beneficiaries_female_60_plus'),
            'garcons_0_17'  => (int) $projects->sum('beneficiaries_male_0_17'),
            'hommes_18_59'  => (int) $projects->sum('beneficiaries_male_18_59'),
            'hommes_60plus' => (int) $projects->sum('beneficiaries_male_60_plus'),
        ];
        $totalBenef = array_sum($benef);

        // ── Projets par province (via execution zones) ───────────────────
        $projectsByProvince = ProjectExecutionZone::select('province_id', DB::raw('COUNT(DISTINCT project_id) as nb_projets'))
            ->whereIn('project_id', $projects->pluck('id'))
            ->whereNotNull('province_id')
            ->groupBy('province_id')
            ->with('province:id,name,pcode,center_lat,center_lon')
            ->get()
            ->map(fn ($row) => [
                'province_id'   => $row->province_id,
                'province_name' => $row->province?->name ?? 'Inconnu',
                'pcode'         => $row->province?->pcode,
                'center_lat'    => $row->province?->center_lat,
                'center_lon'    => $row->province?->center_lon,
                'nb_projets'    => $row->nb_projets,
            ]);

        // ── Projets par bailleur ─────────────────────────────────────────
        $donorMap = [];
        foreach ($projects as $project) {
            foreach ($project->donors_list as $donor) {
                $donor = trim($donor);
                if ($donor === '') continue;
                $donorMap[$donor] = ($donorMap[$donor] ?? 0) + 1;
            }
        }
        arsort($donorMap);
        $projectsByDonor = array_map(
            fn ($k, $v) => ['donor' => $k, 'count' => $v],
            array_keys($donorMap),
            array_values($donorMap)
        );

        // ── Évolution consommation financement vs activités réalisées ────
        // Grouper les activités par mois (reporting_date)
        $activitiesRaw = ProjectActivity::select(
                DB::raw("DATE_FORMAT(reporting_date, '%Y-%m') as month"),
                DB::raw('SUM(activity_cost) as cost'),
                DB::raw('COUNT(*) as nb')
            )
            ->whereIn('project_id', $projects->pluck('id'))
            ->whereNotNull('reporting_date')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $evolutionLabels  = $activitiesRaw->pluck('month')->toArray();
        $evolutionCosts   = $activitiesRaw->map(fn ($r) => (float) $r->cost)->toArray();
        $evolutionCumCost = [];
        $cumul = 0;
        foreach ($evolutionCosts as $c) {
            $cumul += $c;
            $evolutionCumCost[] = round($cumul, 2);
        }
        $evolutionNbActs = $activitiesRaw->pluck('nb')->toArray();

        // Ratio consommation globale
        $totalConsumed  = array_sum($evolutionCosts);
        $consumptionRate = $totalFunding > 0 ? round(($totalConsumed / $totalFunding) * 100, 1) : 0;

        return view('organisation.dashboard', compact(
            'totalProjects',
            'totalFunding',
            'totalActivities',
            'totalBenef',
            'consumptionRate',
            'totalConsumed',
            'benef',
            'projectsByProvince',
            'projectsByDonor',
            'evolutionLabels',
            'evolutionCumCost',
            'evolutionNbActs'
        ));
    }
}
