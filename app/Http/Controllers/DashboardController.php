<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Site;
use App\Models\SiteMouvementPopulation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    private function resolveSelectedPeriod(?string $period): ?Carbon
    {
        if ($period && preg_match('/^\d{2}\/\d{4}$/', $period)) {
            return Carbon::createFromFormat('m/Y', $period)->startOfMonth();
        }

        if ($period && preg_match('/^\d{4}-\d{2}$/', $period)) {
            return Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        }

        $latestDate = SiteMouvementPopulation::query()
            ->where('type_mouvement', 'recensement')
            ->max('date_mouvement');

        return $latestDate ? Carbon::parse($latestDate)->startOfMonth() : null;
    }

    private function buildStatsFromMovements($mouvements): array
    {
        $totalMenages = $mouvements->sum('menages');
        $totalIndividus = $mouvements->sum('individus');
        $f_0_5 = $mouvements->sum('f_0_5');
        $f_6_17 = $mouvements->sum('f_6_17');
        $f_18_59 = $mouvements->sum('f_18_59');
        $f_60_plus = $mouvements->sum('f_60_plus');
        $h_0_5 = $mouvements->sum('h_0_5');
        $h_6_17 = $mouvements->sum('h_6_17');
        $h_18_59 = $mouvements->sum('h_18_59');
        $h_60_plus = $mouvements->sum('h_60_plus');

        return [
            'total_pdi' => $totalIndividus,
            'hommes' => $h_0_5 + $h_6_17 + $h_18_59 + $h_60_plus,
            'femmes' => $f_0_5 + $f_6_17 + $f_18_59 + $f_60_plus,
            'personnes_handicap' => 0,
            'menages' => $totalMenages,
            'enfants' => $f_0_5 + $h_0_5 + $f_6_17 + $h_6_17,
            'adultes' => $f_18_59 + $h_18_59,
            'personnes_agees' => $f_60_plus + $h_60_plus,
        ];
    }

    private function buildDeltas(array $currentStats, array $previousStats): array
    {
        $deltas = [];

        foreach ($currentStats as $key => $value) {
            $previous = $previousStats[$key] ?? 0;
            $deltaValue = $value - $previous;
            $deltaPercent = $previous != 0 ? round(($deltaValue / $previous) * 100, 1) : null;

            $deltas[$key] = [
                'value' => $deltaValue,
                'percent' => $deltaPercent,
            ];
        }

        return $deltas;
    }

    /**
     * Display the dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $selectedPeriodDate = $this->resolveSelectedPeriod($request->input('periode')) ?? now()->startOfMonth();
        $selectedPeriod = $selectedPeriodDate->format('m/Y');
        $year = $selectedPeriodDate->format('Y');
        $month = $selectedPeriodDate->format('m');

        $mouvements = SiteMouvementPopulation::query()
            ->where('type_mouvement', 'recensement')
            ->whereYear('date_mouvement', (int) $year)
            ->whereMonth('date_mouvement', (int) $month)
            ->get();

        $previousPeriod = $selectedPeriodDate->copy()->subMonthNoOverflow();
        $mouvementsPrecedents = SiteMouvementPopulation::query()
            ->where('type_mouvement', 'recensement')
            ->whereYear('date_mouvement', (int) $previousPeriod->format('Y'))
            ->whereMonth('date_mouvement', (int) $previousPeriod->format('m'))
            ->get();

        $stats = $this->buildStatsFromMovements($mouvements);
        $previousStats = $this->buildStatsFromMovements($mouvementsPrecedents);
        $deltas = $this->buildDeltas($stats, $previousStats);

        $ageDistribution = [
            '0-5 ans' => $mouvements->sum('f_0_5') + $mouvements->sum('h_0_5'),
            '6-17 ans' => $mouvements->sum('f_6_17') + $mouvements->sum('h_6_17'),
            '18-59 ans' => $mouvements->sum('f_18_59') + $mouvements->sum('h_18_59'),
            '60+ ans' => $mouvements->sum('f_60_plus') + $mouvements->sum('h_60_plus'),
            'Non spécifié' => max(0, $stats['total_pdi'] - (
                $mouvements->sum('f_0_5') + $mouvements->sum('h_0_5') +
                $mouvements->sum('f_6_17') + $mouvements->sum('h_6_17') +
                $mouvements->sum('f_18_59') + $mouvements->sum('h_18_59') +
                $mouvements->sum('f_60_plus') + $mouvements->sum('h_60_plus')
            )),
        ];

        $provinceDistribution = SiteMouvementPopulation::query()
            ->join('sites', 'site_mouvements_population.site_id', '=', 'sites.id')
            ->where('site_mouvements_population.type_mouvement', 'recensement')
            ->whereYear('site_mouvements_population.date_mouvement', (int) $year)
            ->whereMonth('site_mouvements_population.date_mouvement', (int) $month)
            ->select('sites.province', DB::raw('SUM(site_mouvements_population.individus) as total'))
            ->groupBy('sites.province')
            ->pluck('total', 'sites.province')
            ->toArray();

        return view('dashboard', compact('stats', 'ageDistribution', 'provinceDistribution', 'selectedPeriod', 'deltas'));
    }
}
