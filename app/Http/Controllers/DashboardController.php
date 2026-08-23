<?php

namespace App\Http\Controllers;

use App\Models\CategorieSite;
use App\Models\Commune;
use App\Models\Coordinateur;
use App\Models\Gestionnaire;
use App\Models\Province;
use App\Models\Territoire;
use Illuminate\Http\Request;
use App\Models\Site;
use App\Models\SiteMouvementPopulation;
use App\Services\SitePopulationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;

class DashboardController extends Controller
{
    private array $movementStatFields = [
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

    private function movementPopulationTableExists(): bool
    {
        return Schema::hasTable((new SiteMouvementPopulation())->getTable());
    }

    private function siteHasClosureDateColumn(): bool
    {
        return Schema::hasColumn((new Site())->getTable(), 'date_fermeture');
    }

    private function siteHasColumn(string $column): bool
    {
        return Schema::hasColumn((new Site())->getTable(), $column);
    }

    private function applyActiveSiteFilterToMovementQuery($query)
    {
        if (! $this->siteHasClosureDateColumn()) {
            return $query;
        }

        return $query->whereHas('site', function ($siteQuery) {
            $siteQuery->where(function ($q) {
                $q->whereNull('date_fermeture')
                    ->orWhereColumn('date_fermeture', '>', 'site_mouvements_population.date_mouvement');
            });
        });
    }

    private function applySiteFiltersToMovementQuery($query, Request $request)
    {
        $this->applyActiveSiteFilterToMovementQuery($query);

        if ($request->filled('province_id')) {
            $provinceId = (int) $request->province_id;
            $province = Province::query()->select('id', 'name', 'pcode')->find($provinceId);
            $query->whereHas('site', function ($siteQuery) use ($provinceId, $province) {
                $siteQuery->where(function ($q) use ($provinceId, $province) {
                    $matchedBySiteColumn = false;
                    if ($province && $this->siteHasColumn('province')) {
                        $q->whereRaw('LOWER(TRIM(province)) = ?', [mb_strtolower(trim($province->name))]);
                        if ($province->pcode && $this->siteHasColumn('code_province')) {
                            $q->orWhere('code_province', $province->pcode);
                        }
                        $matchedBySiteColumn = true;
                    }

                    $method = $matchedBySiteColumn ? 'orWhereHas' : 'whereHas';
                    $q->{$method}('commune.territoire.province', function ($subQuery) use ($provinceId) {
                            $subQuery->where('provinces.id', $provinceId);
                        });
                });
            });
        }

        if ($request->filled('territoire_id')) {
            $territoireId = (int) $request->territoire_id;
            $territoire = Territoire::query()->select('id', 'name', 'pcode')->find($territoireId);
            $query->whereHas('site', function ($siteQuery) use ($territoireId, $territoire) {
                $siteQuery->where(function ($q) use ($territoireId, $territoire) {
                    $matchedBySiteColumn = false;
                    if ($territoire && $this->siteHasColumn('territoire')) {
                        $q->whereRaw('LOWER(TRIM(territoire)) = ?', [mb_strtolower(trim($territoire->name))]);
                        if ($territoire->pcode && $this->siteHasColumn('code_territoire')) {
                            $q->orWhere('code_territoire', $territoire->pcode);
                        }
                        $matchedBySiteColumn = true;
                    }

                    $method = $matchedBySiteColumn ? 'orWhereHas' : 'whereHas';
                    $q->{$method}('commune.territoire', function ($subQuery) use ($territoireId) {
                            $subQuery->where('territoires.id', $territoireId);
                        });
                });
            });
        }

        if ($request->filled('commune_id') && $this->siteHasColumn('commune_id')) {
            $communeId = (int) $request->commune_id;
            $commune = Commune::query()->select('id', 'name', 'pcode')->find($communeId);
            $query->whereHas('site', function ($siteQuery) use ($communeId, $commune) {
                $siteQuery->where(function ($q) use ($communeId, $commune) {
                    $q->where('commune_id', $communeId);
                    if ($commune && $this->siteHasColumn('zone_sante')) {
                        $q->orWhereRaw('LOWER(TRIM(zone_sante)) = ?', [mb_strtolower(trim($commune->name))]);
                    }
                    if ($commune?->pcode && $this->siteHasColumn('code_zone_sante')) {
                        $q->orWhere('code_zone_sante', $commune->pcode);
                    }
                });
            });
        }

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->filled('coordinateur_id') && $this->siteHasColumn('coordinateur_id')) {
            $query->whereHas('site', function ($siteQuery) use ($request) {
                $siteQuery->where('coordinateur_id', $request->coordinateur_id);
            });
        }

        if ($request->filled('gestionnaire_id') && $this->siteHasColumn('gestionnaire_id')) {
            $query->whereHas('site', function ($siteQuery) use ($request) {
                $siteQuery->where('gestionnaire_id', $request->gestionnaire_id);
            });
        }

        if ($request->filled('categorie_site_id') && $this->siteHasColumn('categorie_site_id')) {
            $query->whereHas('site', function ($siteQuery) use ($request) {
                $siteQuery->where('categorie_site_id', $request->categorie_site_id);
            });
        }

        return $query;
    }

    private function resolveSelectedPeriod(?string $period): ?Carbon
    {
        if (!$this->movementPopulationTableExists()) {
            return null;
        }

        $currentPeriod = now()->startOfMonth();

        if ($period && preg_match('/^\d{2}\/\d{4}$/', $period)) {
            $parsed = Carbon::createFromFormat('m/Y', $period)->startOfMonth();
            return $parsed->gt($currentPeriod) ? $currentPeriod : $parsed;
        }

        if ($period && preg_match('/^\d{4}-\d{2}$/', $period)) {
            $parsed = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
            return $parsed->gt($currentPeriod) ? $currentPeriod : $parsed;
        }

        $latestQuery = SiteMouvementPopulation::query()
            ->where('statut', 'valide');

        if ($this->siteHasClosureDateColumn()) {
            $latestQuery->whereHas('site', function ($siteQuery) {
                $siteQuery->whereNull('date_fermeture');
            });
        }

        $latestDate = $latestQuery->max('date_mouvement');

        return $latestDate ? Carbon::parse($latestDate)->startOfMonth() : null;
    }

    private function findLatestAvailablePeriodUpTo(Carbon $maxPeriodDate, ?Request $request = null): ?Carbon
    {
        if (!$this->movementPopulationTableExists()) {
            return null;
        }

        $query = SiteMouvementPopulation::query()
            ->where('statut', 'valide')
            ->whereDate('date_mouvement', '<=', $maxPeriodDate->copy()->endOfMonth()->toDateString());

        if ($request) {
            $this->applySiteFiltersToMovementQuery($query, $request);
        } else {
            $this->applyActiveSiteFilterToMovementQuery($query);
        }

        $latestDate = $query->max('date_mouvement');

        return $latestDate ? Carbon::parse($latestDate)->startOfMonth() : null;
    }

    private function findLatestAvailablePeriodBefore(Carbon $periodDate, ?Request $request = null): ?Carbon
    {
        return $this->findLatestAvailablePeriodUpTo($periodDate->copy()->subMonthNoOverflow(), $request);
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

    private function normalizeMovementsForClosure($mouvements, Carbon $periodEnd)
    {
        return $mouvements->map(function ($mouvement) use ($periodEnd) {
            $closureDate = $mouvement->site?->date_fermeture;

            if ($closureDate && Carbon::parse($closureDate)->lte($periodEnd)) {
                foreach ($this->movementStatFields as $field) {
                    $mouvement->{$field} = 0;
                }
            }

            return $mouvement;
        });
    }

    private function resolveDashboardPeriod(Carbon $selectedPeriodDate, ?Request $request = null): array
    {
        if (!$this->movementPopulationTableExists()) {
            return [$selectedPeriodDate, false, 'Aucune donnée disponible : le registre des mouvements de population n’est pas configuré.'];
        }

        $selectedQuery = SiteMouvementPopulation::query()
            ->where('statut', 'valide')
            ->whereYear('date_mouvement', (int) $selectedPeriodDate->format('Y'))
            ->whereMonth('date_mouvement', (int) $selectedPeriodDate->format('m'));

        if ($request) {
            $this->applySiteFiltersToMovementQuery($selectedQuery, $request);
        } else {
            $this->applyActiveSiteFilterToMovementQuery($selectedQuery);
        }

        if ($selectedQuery->exists()) {
            return [$selectedPeriodDate, false, null];
        }

        $latestAvailablePeriod = $this->findLatestAvailablePeriodUpTo($selectedPeriodDate, $request);

        if ($latestAvailablePeriod && !$latestAvailablePeriod->equalTo($selectedPeriodDate)) {
            return [$latestAvailablePeriod, true, sprintf(
                'Aucune donnée disponible pour la période sélectionnée. Données considérées: %s.',
                $latestAvailablePeriod->format('m/Y')
            )];
        }

        return [$selectedPeriodDate, false, sprintf(
            'Aucune donnée disponible pour la période sélectionnée. Données considérées: %s.',
            $selectedPeriodDate->format('m/Y')
        )];
    }

    private function buildDashboardPayload(Carbon $selectedPeriodDate, ?Request $request = null): array
    {
        [$consideredPeriodDate, $usedFallback, $fallbackNote] = $this->resolveDashboardPeriod($selectedPeriodDate, $request);

        if (!$this->movementPopulationTableExists()) {
            $emptyStats = [
                'total_pdi' => 0,
                'hommes' => 0,
                'femmes' => 0,
                'personnes_handicap' => 0,
                'menages' => 0,
                'enfants' => 0,
                'adultes' => 0,
                'personnes_agees' => 0,
            ];

            return [
                'stats' => $emptyStats,
                'ageDistribution' => [
                    '0-5 ans' => 0,
                    '6-17 ans' => 0,
                    '18-59 ans' => 0,
                    '60+ ans' => 0,
                    'Non spécifié' => 0,
                ],
                'provinceDistribution' => [],
                'mapPoints' => [],
                'selectedPeriod' => $selectedPeriodDate->format('m/Y'),
                'consideredPeriod' => $consideredPeriodDate->format('m/Y'),
                'comparisonPeriod' => null,
                'deltas' => array_fill_keys(array_keys($emptyStats), ['value' => 0, 'percent' => null]),
                'fallbackNote' => $fallbackNote,
                'usedFallback' => true,
            ];
        }

        $year = $consideredPeriodDate->format('Y');
        $month = $consideredPeriodDate->format('m');

        $mouvements = SiteMouvementPopulation::query()
            ->with('site')
            ->where('statut', 'valide')
            ->whereDate('date_mouvement', '<=', $consideredPeriodDate->copy()->endOfMonth()->toDateString());

        if ($request) {
            $this->applySiteFiltersToMovementQuery($mouvements, $request);
        } else {
            $this->applyActiveSiteFilterToMovementQuery($mouvements);
        }

        $mouvements = app(SitePopulationService::class)->snapshots($mouvements->get());
        $mouvements = $this->normalizeMovementsForClosure($mouvements, $consideredPeriodDate->copy()->endOfMonth());

        $comparisonPeriodDate = $this->findLatestAvailablePeriodBefore($consideredPeriodDate, $request);
        $mouvementsPrecedents = collect();

        if ($comparisonPeriodDate) {
            $mouvementsPrecedents = SiteMouvementPopulation::query()
                ->with('site')
                ->where('statut', 'valide')
                ->whereDate('date_mouvement', '<=', $comparisonPeriodDate->copy()->endOfMonth()->toDateString());

            if ($request) {
                $this->applySiteFiltersToMovementQuery($mouvementsPrecedents, $request);
            } else {
                $this->applyActiveSiteFilterToMovementQuery($mouvementsPrecedents);
            }

            $mouvementsPrecedents = $this->normalizeMovementsForClosure(
                app(SitePopulationService::class)->snapshots($mouvementsPrecedents->get()),
                $comparisonPeriodDate->copy()->endOfMonth()
            );
        }

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

        $provinceDistribution = $mouvements
            ->groupBy(function ($mouvement) {
                return $mouvement->site->province ?? 'Non spécifié';
            })
            ->map(function ($items) {
                return $items->sum('individus');
            })
            ->toArray();

        $mapPoints = $mouvements
            ->groupBy('site_id')
            ->map(function ($siteMovements) {
                $latestMovement = $siteMovements->sortByDesc('date_mouvement')->first();
                $site = $latestMovement?->site;

                if (!$site || $site->latitude === null || $site->longitude === null) {
                    return null;
                }

                $latitude = (float) $site->latitude;
                $longitude = (float) $site->longitude;
                if (abs($latitude) < 0.000001 || abs($longitude) < 0.000001) {
                    return null;
                }

                return [
                    'site' => (string) ($site->nom ?? 'Site'),
                    'province' => (string) ($site->province ?? 'Non spécifié'),
                    'territoire' => (string) ($site->territoire ?? 'Non spécifié'),
                    'zone_sante' => (string) ($site->zone_sante ?? 'Non spécifié'),
                    'latitude' => round($latitude, 6),
                    'longitude' => round($longitude, 6),
                    'population' => (int) $siteMovements->sum('individus'),
                ];
            })
            ->filter()
            ->sortByDesc('population')
            ->values()
            ->all();

        return [
            'stats' => $stats,
            'ageDistribution' => $ageDistribution,
            'provinceDistribution' => $provinceDistribution,
            'mapPoints' => $mapPoints,
            'selectedPeriod' => $selectedPeriodDate->format('m/Y'),
            'consideredPeriod' => $consideredPeriodDate->format('m/Y'),
            'comparisonPeriod' => $comparisonPeriodDate?->format('m/Y'),
            'deltas' => $deltas,
            'fallbackNote' => $fallbackNote,
            'usedFallback' => $usedFallback,
        ];
    }

    private function buildDashboardFilters(Request $request): array
    {
        $province = $request->filled('province_id') ? Province::find($request->province_id) : null;
        $territoire = $request->filled('territoire_id') ? Territoire::find($request->territoire_id) : null;
        $commune = $request->filled('commune_id') ? Commune::find($request->commune_id) : null;
        $site = $request->filled('site_id') ? Site::find($request->site_id) : null;
        $coordinateur = $request->filled('coordinateur_id') ? Coordinateur::find($request->coordinateur_id) : null;
        $gestionnaire = $request->filled('gestionnaire_id') ? Gestionnaire::find($request->gestionnaire_id) : null;
        $categorieSite = $request->filled('categorie_site_id') ? CategorieSite::find($request->categorie_site_id) : null;

        return [
            'province' => $province?->name ?: 'Toutes',
            'territoire' => $territoire?->name ?: 'Tous',
            'commune' => $commune?->name ?: 'Toutes',
            'site' => $site?->nom ?: 'Tous',
            'coordinateur' => $coordinateur?->name ?: 'Tous',
            'gestionnaire' => $gestionnaire?->name ?: 'Tous',
            'categorieSite' => $categorieSite?->name ?: 'Toutes',
        ];
    }

    private function buildDashboardSpreadsheet(array $payload, array $filters, ?string $mapBinaryOverride = null): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Segoe UI')->setSize(10);
        $spreadsheet->getDefaultStyle()->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $totalPdi = max(1, (int) ($payload['stats']['total_pdi'] ?? 0));
        $quickChartConfigs = $this->buildQuickChartConfigs($payload);
        $mapBinary = $mapBinaryOverride ?: $this->fetchQuickChartPng($quickChartConfigs['map'], 1600, 900);

        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Synthese');

        $summarySheet->fromArray([
            ['Dashboard', 'Valeur'],
            ['Période demandée', $payload['selectedPeriod']],
            ['Période considérée', $payload['consideredPeriod']],
            ['Période de comparaison', $payload['comparisonPeriod'] ?: 'Aucune'],
            ['Filtre Province', $filters['province']],
            ['Filtre Territoire', $filters['territoire']],
            ['Filtre Zone de santé', $filters['commune']],
            ['Filtre Site', $filters['site']],
            ['Filtre Coordinateur', $filters['coordinateur']],
            ['Filtre Gestionnaire', $filters['gestionnaire']],
            ['Filtre Mécanisme CCCM', $filters['categorieSite']],
            ['Note', $payload['fallbackNote'] ?: ''],
        ], null, 'A1');

        $comparisonReferenceLabel = (string) ($payload['comparisonPeriod'] ?: 'aucune période antérieure disponible');
        $demographicProfiles = $this->buildDemographicProfiles($payload, $comparisonReferenceLabel);
        $demographicRows = [['Icône', 'Indicateur', 'Valeur', 'Différence']];
        foreach ($demographicProfiles as $profile) {
            $demographicRows[] = [
                (string) $profile['icon'],
                (string) $profile['label'],
                number_format((int) $profile['value']),
                (string) $profile['deltaText'],
            ];
        }
        $summarySheet->fromArray($demographicRows, null, 'D1');

        $ageSheet = $spreadsheet->createSheet();
        $ageSheet->setTitle('Age');
        $ageSheet->fromArray([
            ['Tranche d\'âge', 'Population'],
            ['0-5 ans', $payload['ageDistribution']['0-5 ans'] ?? 0],
            ['6-17 ans', $payload['ageDistribution']['6-17 ans'] ?? 0],
            ['18-59 ans', $payload['ageDistribution']['18-59 ans'] ?? 0],
            ['60+ ans', $payload['ageDistribution']['60+ ans'] ?? 0],
            ['Non spécifié', $payload['ageDistribution']['Non spécifié'] ?? 0],
        ], null, 'A1');

        $provinceSheet = $spreadsheet->createSheet();
        $provinceSheet->setTitle('Provinces');
        $provinceSheet->fromArray([['Province', 'Population']], null, 'A1');

        $row = 2;
        foreach ($payload['provinceDistribution'] as $province => $value) {
            $provinceSheet->setCellValue('A' . $row, $province ?: 'Non spécifié');
            $provinceSheet->setCellValue('B' . $row, $value);
            $row++;
        }

        $visualSheet = $spreadsheet->createSheet();
        $visualSheet->setTitle('Visuels');

        $visualSheet->setCellValue('A1', 'Répartition par âge');
        $visualSheet->fromArray([
            ['Tranche', 'Population', 'Part (%)'],
            ['0-5 ans', $payload['ageDistribution']['0-5 ans'] ?? 0, round((($payload['ageDistribution']['0-5 ans'] ?? 0) * 100) / $totalPdi, 1)],
            ['6-17 ans', $payload['ageDistribution']['6-17 ans'] ?? 0, round((($payload['ageDistribution']['6-17 ans'] ?? 0) * 100) / $totalPdi, 1)],
            ['18-59 ans', $payload['ageDistribution']['18-59 ans'] ?? 0, round((($payload['ageDistribution']['18-59 ans'] ?? 0) * 100) / $totalPdi, 1)],
            ['60+ ans', $payload['ageDistribution']['60+ ans'] ?? 0, round((($payload['ageDistribution']['60+ ans'] ?? 0) * 100) / $totalPdi, 1)],
            ['Non spécifié', $payload['ageDistribution']['Non spécifié'] ?? 0, round((($payload['ageDistribution']['Non spécifié'] ?? 0) * 100) / $totalPdi, 1)],
        ], null, 'A2');

        $visualSheet->setCellValue('D1', 'Distribution par sexe');
        $visualSheet->fromArray([
            ['Sexe', 'Population', 'Part (%)'],
            ['Femmes', $payload['stats']['femmes'] ?? 0, round((($payload['stats']['femmes'] ?? 0) * 100) / $totalPdi, 1)],
            ['Hommes', $payload['stats']['hommes'] ?? 0, round((($payload['stats']['hommes'] ?? 0) * 100) / $totalPdi, 1)],
        ], null, 'D2');

        $visualSheet->setCellValue('G1', 'Distribution par province');
        $visualSheet->fromArray([['Province', 'Population', 'Part (%)']], null, 'G2');

        $provinceChartRow = 3;
        foreach ($payload['provinceDistribution'] as $province => $value) {
            $visualSheet->setCellValue('G' . $provinceChartRow, $province ?: 'Non spécifié');
            $visualSheet->setCellValue('H' . $provinceChartRow, (int) $value);
            $visualSheet->setCellValue('I' . $provinceChartRow, round(((int) $value * 100) / $totalPdi, 1));
            $provinceChartRow++;
        }

        $visualSheet->setCellValue('J1', 'Carte des sites recensés');
        $visualSheet->setCellValue('J2', 'Image de la carte (sans tableau de repères)');
        $visualSheet->mergeCells('J2:N2');

        $ageLabels = [new DataSeriesValues('String', "'Visuels'!\$A\$3:\$A\$7", null, 5)];
        $ageValues = [new DataSeriesValues('Number', "'Visuels'!\$B\$3:\$B\$7", null, 5)];
        $ageSeries = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            range(0, count($ageValues) - 1),
            [new DataSeriesValues('String', "'Visuels'!\$B\$2", null, 1)],
            $ageLabels,
            $ageValues
        );
        $agePlot = new PlotArea(null, [$ageSeries]);
        $ageChart = new Chart('age_chart', new Title('Répartition de la population par tranche d\'âge'), null, $agePlot);
        $ageChart->setTopLeftPosition('G8');
        $ageChart->setBottomRightPosition('K23');
        $visualSheet->addChart($ageChart);

        $genderLabels = [new DataSeriesValues('String', "'Visuels'!\$D\$3:\$D\$4", null, 2)];
        $genderValues = [new DataSeriesValues('Number', "'Visuels'!\$E\$3:\$E\$4", null, 2)];
        $genderSeries = new DataSeries(
            DataSeries::TYPE_PIECHART,
            null,
            range(0, count($genderValues) - 1),
            [],
            $genderLabels,
            $genderValues
        );
        $genderPlot = new PlotArea(null, [$genderSeries]);
        $genderChart = new Chart('gender_chart', new Title('Distribution de la population par sexe'), new Legend(Legend::POSITION_BOTTOM, null, false), $genderPlot);
        $genderChart->setTopLeftPosition('L8');
        $genderChart->setBottomRightPosition('N23');
        $visualSheet->addChart($genderChart);

        $provinceCount = max(1, $provinceChartRow - 3);
        $provinceEndRow = 2 + $provinceCount;
        $provinceLabels = [new DataSeriesValues('String', "'Visuels'!\$G\$3:\$G\$" . $provinceEndRow, null, $provinceCount)];
        $provinceValues = [new DataSeriesValues('Number', "'Visuels'!\$H\$3:\$H\$" . $provinceEndRow, null, $provinceCount)];
        $provinceSeries = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            range(0, count($provinceValues) - 1),
            [new DataSeriesValues('String', "'Visuels'!\$H\$2", null, 1)],
            $provinceLabels,
            $provinceValues
        );
        $provincePlot = new PlotArea(null, [$provinceSeries]);
        $provinceChart = new Chart('province_chart', new Title('Distribution de la population dans les provinces touchées par la crise'), null, $provincePlot);
        $provinceChart->setTopLeftPosition('G26');
        $provinceChart->setBottomRightPosition('N45');
        $visualSheet->addChart($provinceChart);

        // Habillage visuel proche du dashboard.
        $summarySheet->mergeCells('A1:B1');
        $summarySheet->mergeCells('D1:G1');
        $summarySheet->getStyle('A1:G1')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FFFFFFFF');
        $summarySheet->getStyle('A1:G1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $summarySheet->getStyle('A1:B1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1D4ED8');
        $summarySheet->getStyle('D1:G1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF111827');
        $summarySheet->getStyle('A2:A12')->getFont()->setBold(true)->getColor()->setARGB('FF1F2937');
        $summarySheet->getStyle('D2:G2')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $summarySheet->getStyle('D2:G2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF374151');
        $summarySheet->getStyle('B2:B12')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $summarySheet->getStyle('D3:D10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $summarySheet->getStyle('F3:F10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $summarySheet->getStyle('A2:G12')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE5E7EB');
        $summarySheet->getColumnDimension('A')->setWidth(28);
        $summarySheet->getColumnDimension('B')->setWidth(30);
        $summarySheet->getColumnDimension('C')->setWidth(3);
        $summarySheet->getColumnDimension('D')->setWidth(10);
        $summarySheet->getColumnDimension('E')->setWidth(42);
        $summarySheet->getColumnDimension('F')->setWidth(20);
        $summarySheet->getColumnDimension('G')->setWidth(46);
        $summarySheet->getRowDimension(1)->setRowHeight(24);
        $summarySheet->getRowDimension(2)->setRowHeight(28);
        $summarySheet->getStyle('G3:G10')->getAlignment()->setWrapText(true);
        $summarySheet->freezePane('A2');

        $visualSheet->mergeCells('A1:B1');
        $visualSheet->mergeCells('D1:E1');
        $visualSheet->mergeCells('G1:I1');
        $visualSheet->mergeCells('J1:N1');
        $visualSheet->getStyle('A1:N2')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $visualSheet->getStyle('A1:N2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $visualSheet->getStyle('A1:N2')->getAlignment()->setWrapText(true);
        $visualSheet->getStyle('A1:B1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF3B82F6');
        $visualSheet->getStyle('D1:E1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEC4899');
        $visualSheet->getStyle('G1:I1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF8B5CF6');
        $visualSheet->getStyle('J1:N1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F766E');
        $visualSheet->getStyle('A2:N2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF111827');
        $visualSheet->getStyle('A2:N2')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FF374151');
        $visualSheet->getStyle('A3:I' . max(7, $provinceEndRow))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE5E7EB');
        $visualSheet->getStyle('B3:B7')->getNumberFormat()->setFormatCode('#,##0');
        $visualSheet->getStyle('E3:E4')->getNumberFormat()->setFormatCode('#,##0');
        $visualSheet->getStyle('H3:H' . $provinceEndRow)->getNumberFormat()->setFormatCode('#,##0');
        $visualSheet->getStyle('C3:C7')->getNumberFormat()->setFormatCode('0.0');
        $visualSheet->getStyle('F3:F4')->getNumberFormat()->setFormatCode('0.0');
        $visualSheet->getStyle('I3:I' . $provinceEndRow)->getNumberFormat()->setFormatCode('0.0');
        $visualSheet->getStyle('B3:B7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $visualSheet->getStyle('E3:E4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $visualSheet->getStyle('H3:H' . $provinceEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $visualSheet->getStyle('C3:C7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $visualSheet->getStyle('F3:F4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $visualSheet->getStyle('I3:I' . $provinceEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $visualSheet->getColumnDimension('A')->setWidth(24);
        $visualSheet->getColumnDimension('B')->setWidth(16);
        $visualSheet->getColumnDimension('C')->setWidth(12);
        $visualSheet->getColumnDimension('D')->setWidth(20);
        $visualSheet->getColumnDimension('E')->setWidth(16);
        $visualSheet->getColumnDimension('F')->setWidth(12);
        $visualSheet->getColumnDimension('G')->setWidth(44);
        $visualSheet->getColumnDimension('H')->setWidth(16);
        $visualSheet->getColumnDimension('I')->setWidth(12);
        $visualSheet->getColumnDimension('J')->setWidth(34);
        $visualSheet->getColumnDimension('K')->setWidth(20);
        $visualSheet->getColumnDimension('L')->setWidth(14);
        $visualSheet->getColumnDimension('M')->setWidth(14);
        $visualSheet->getColumnDimension('N')->setWidth(14);
        $visualSheet->getRowDimension(1)->setRowHeight(24);
        $visualSheet->getRowDimension(2)->setRowHeight(28);
        $visualSheet->freezePane('A3');
        $visualSheet->setShowGridlines(false);
        $visualSheet->getSheetView()->setZoomScale(110);

        if ($mapBinary) {
            $mapTempPath = $this->createTempPngFromBinary($mapBinary);
            if ($mapTempPath) {
                $mapDrawing = new Drawing();
                $mapDrawing->setName('Carte des sites');
                $mapDrawing->setDescription('Carte de répartition géographique des sites filtrés');
                $mapDrawing->setPath($mapTempPath);
                $mapDrawing->setCoordinates('A8');
                $mapDrawing->setWidth(640);
                $mapDrawing->setHeight(360);
                $mapDrawing->setOffsetX(6);
                $mapDrawing->setOffsetY(4);
                $mapDrawing->setWorksheet($visualSheet);
            }
        }

        $ageSheet->getStyle('A1:B1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $ageSheet->getStyle('A1:B1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF3B82F6');
        $ageSheet->getStyle('A2:B6')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE5E7EB');
        $ageSheet->getStyle('B2:B6')->getNumberFormat()->setFormatCode('#,##0');
        $ageSheet->getStyle('B2:B6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $ageSheet->getColumnDimension('A')->setWidth(20);
        $ageSheet->getColumnDimension('B')->setWidth(16);
        $ageSheet->freezePane('A2');

        $provinceSheet->getStyle('A1:B1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $provinceSheet->getStyle('A1:B1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF8B5CF6');
        $provinceSheet->getStyle('A2:B' . max(2, $row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE5E7EB');
        $provinceSheet->getStyle('B2:B' . max(2, $row - 1))->getNumberFormat()->setFormatCode('#,##0');
        $provinceSheet->getStyle('B2:B' . max(2, $row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $provinceSheet->getColumnDimension('A')->setWidth(52);
        $provinceSheet->getColumnDimension('B')->setWidth(16);
        $provinceSheet->freezePane('A2');

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheet->getStyle('A1:Z80')->getAlignment()->setWrapText(true);
        }

        return $spreadsheet;
    }

    private function exportDashboardExcel(array $payload, array $filters, ?string $mapBinaryOverride = null)
    {
        $spreadsheet = $this->buildDashboardSpreadsheet($payload, $filters, $mapBinaryOverride);
        $writer = new Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);
        $filename = 'dashboard-' . now()->format('Y-m-d-His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function xmlEscape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function buildWordHeadingXml(string $text, string $color = '545456', int $size = 22, string $align = 'left'): string
    {
        return '<w:p><w:pPr><w:spacing w:before="120" w:after="80"/><w:jc w:val="' . $this->xmlEscape($align) . '"/></w:pPr>'
            . '<w:r><w:rPr><w:b/><w:sz w:val="' . $size . '"/><w:color w:val="' . $this->xmlEscape($color) . '"/></w:rPr><w:t>' . $this->xmlEscape($text) . '</w:t></w:r>'
            . '</w:p>';
    }

    private function buildWordParagraphXml(string $text, string $color = '545456', int $size = 18, bool $bold = false, string $align = 'left'): string
    {
        $boldXml = $bold ? '<w:b/>' : '';

        return '<w:p><w:pPr><w:spacing w:before="20" w:after="40"/><w:jc w:val="' . $this->xmlEscape($align) . '"/></w:pPr>'
            . '<w:r><w:rPr>' . $boldXml . '<w:sz w:val="' . $size . '"/><w:color w:val="' . $this->xmlEscape($color) . '"/></w:rPr><w:t>' . $this->xmlEscape($text) . '</w:t></w:r>'
            . '</w:p>';
    }

    private function buildWordDividerXml(string $color = 'E8E8E9'): string
    {
        return '<w:p><w:pPr><w:spacing w:before="40" w:after="100"/><w:pBdr><w:bottom w:val="single" w:sz="8" w:space="1" w:color="' . $this->xmlEscape($color) . '"/></w:pBdr></w:pPr></w:p>';
    }

    private function buildWordPageBreakXml(): string
    {
        return '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
    }

    private function buildWordShapeCardXml(
        string $label,
        string $value,
        string $fillColor = 'E8F4FB',
        string $strokeColor = 'C2E3F5',
        string $labelColor = '323234',
        string $valueColor = '545456',
        string $widthPt = '230pt'
    ): string {
        return '<w:p><w:pPr><w:spacing w:before="20" w:after="40"/></w:pPr>'
            . '<w:r><w:pict>'
            . '<v:roundrect arcsize="10%" strokecolor="#' . $this->xmlEscape($strokeColor) . '" fillcolor="#' . $this->xmlEscape($fillColor) . '" style="width:' . $this->xmlEscape($widthPt) . ';height:34pt">'
            . '<v:textbox inset="8pt,5pt,8pt,5pt">'
            . '<w:txbxContent>'
            . '<w:p><w:pPr><w:jc w:val="left"/></w:pPr>'
            . '<w:r><w:rPr><w:b/><w:color w:val="' . $this->xmlEscape($labelColor) . '"/><w:sz w:val="18"/></w:rPr><w:t>' . $this->xmlEscape($label) . '</w:t></w:r>'
            . '<w:r><w:rPr><w:color w:val="' . $this->xmlEscape($valueColor) . '"/><w:sz w:val="18"/></w:rPr><w:t>: ' . $this->xmlEscape($value) . '</w:t></w:r>'
            . '</w:p>'
            . '</w:txbxContent>'
            . '</v:textbox>'
            . '</v:roundrect>'
            . '</w:pict></w:r>'
            . '</w:p>';
    }

    private function buildWordShapeCardsXml(
        array $rows,
        string $fillColor = 'E8F4FB',
        string $strokeColor = 'C2E3F5',
        string $labelColor = '323234',
        string $valueColor = '545456'
    ): string {
        $cards = [];
        foreach ($rows as $row) {
            $label = (string) ($row['label'] ?? '');
            if ($label === '') {
                continue;
            }

            $cards[] = [
                'label' => $label,
                'value' => (string) ($row['value'] ?? ''),
            ];
        }

        if (empty($cards)) {
            return '';
        }

        $xml = '<w:tbl>'
            . '<w:tblPr><w:tblW w:w="0" w:type="auto"/><w:tblLayout w:type="fixed"/></w:tblPr>'
            . '<w:tblGrid>'
            . '<w:gridCol w:w="5000"/><w:gridCol w:w="5000"/><w:gridCol w:w="5000"/>'
            . '</w:tblGrid>';

        $cardCount = count($cards);
        for ($i = 0; $i < $cardCount; $i += 3) {
            $xml .= '<w:tr>';
            for ($col = 0; $col < 3; $col++) {
                $idx = $i + $col;
                $xml .= '<w:tc><w:tcPr><w:tcW w:w="5000" w:type="dxa"/>'
                    . '<w:tcMar><w:top w:w="80" w:type="dxa"/><w:left w:w="80" w:type="dxa"/><w:bottom w:w="80" w:type="dxa"/><w:right w:w="80" w:type="dxa"/></w:tcMar>'
                    . '</w:tcPr>';

                if ($idx < $cardCount) {
                    $xml .= $this->buildWordShapeCardXml(
                        $cards[$idx]['label'],
                        $cards[$idx]['value'],
                        $fillColor,
                        $strokeColor,
                        $labelColor,
                        $valueColor,
                        '230pt'
                    );
                } else {
                    $xml .= '<w:p/>';
                }

                $xml .= '</w:tc>';
            }
            $xml .= '</w:tr>';
        }

        $xml .= '</w:tbl>';

        return $xml;
    }

    private function buildWordSingleContextShapeXml(
        array $rows,
        string $fillColor = 'E8F4FB',
        string $strokeColor = 'C2E3F5',
        string $labelColor = '323234',
        string $valueColor = '545456'
    ): string {
        $contentXml = '';
        $lineCount = 0;

        foreach ($rows as $row) {
            $label = trim((string) ($row['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $value = (string) ($row['value'] ?? '');
            $contentXml .= '<w:p><w:pPr><w:spacing w:before="10" w:after="10"/><w:jc w:val="left"/></w:pPr>'
                . '<w:r><w:rPr><w:b/><w:color w:val="' . $this->xmlEscape($labelColor) . '"/><w:sz w:val="18"/></w:rPr><w:t>' . $this->xmlEscape($label) . '</w:t></w:r>'
                . '<w:r><w:rPr><w:color w:val="' . $this->xmlEscape($valueColor) . '"/><w:sz w:val="18"/></w:rPr><w:t>: ' . $this->xmlEscape($value) . '</w:t></w:r>'
                . '</w:p>';
            $lineCount++;
        }

        if ($contentXml === '') {
            return '';
        }

        $heightPt = max(90, 24 + ($lineCount * 16));

        return '<w:p><w:pPr><w:spacing w:before="20" w:after="40"/></w:pPr>'
            . '<w:r><w:pict>'
            . '<v:roundrect arcsize="8%" strokecolor="#' . $this->xmlEscape($strokeColor) . '" fillcolor="#' . $this->xmlEscape($fillColor) . '" style="width:730pt;height:' . $heightPt . 'pt">'
            . '<v:textbox inset="10pt,8pt,10pt,8pt">'
            . '<w:txbxContent>' . $contentXml . '</w:txbxContent>'
            . '</v:textbox>'
            . '</v:roundrect>'
            . '</w:pict></w:r>'
            . '</w:p>';
    }

    private function buildWordDemographicShapeCardXml(array $profile, string $widthPt = '230pt'): string
    {
        $icon = (string) ($profile['icon'] ?? '');
        $label = (string) ($profile['label'] ?? '');
        $value = number_format((int) ($profile['value'] ?? 0), 0, ',', ' ');
        $deltaText = (string) ($profile['deltaText'] ?? '');
        $deltaValue = (int) ($profile['deltaValue'] ?? 0);

        $deltaColor = 'D48C74';
        if ($deltaValue > 0) {
            $deltaColor = '9D4838';
        } elseif ($deltaValue < 0) {
            $deltaColor = '2A87C8';
        }

        return '<w:p><w:pPr><w:spacing w:before="20" w:after="40"/></w:pPr>'
            . '<w:r><w:pict>'
            . '<v:roundrect arcsize="10%" strokecolor="#C2E3F5" fillcolor="#E8F4FB" style="width:' . $this->xmlEscape($widthPt) . ';height:92pt">'
            . '<v:textbox inset="8pt,6pt,8pt,6pt">'
            . '<w:txbxContent>'
            . '<w:p><w:pPr><w:jc w:val="left"/></w:pPr>'
            . '<w:r><w:rPr><w:b/><w:color w:val="2A87C8"/><w:sz w:val="20"/></w:rPr><w:t>' . $this->xmlEscape($icon) . '</w:t></w:r>'
            . '<w:r><w:rPr><w:b/><w:color w:val="323234"/><w:sz w:val="22"/></w:rPr><w:t> ' . $this->xmlEscape($value) . '</w:t></w:r>'
            . '</w:p>'
            . '<w:p><w:pPr><w:jc w:val="left"/></w:pPr>'
            . '<w:r><w:rPr><w:color w:val="545456"/><w:sz w:val="16"/></w:rPr><w:t>' . $this->xmlEscape($label) . '</w:t></w:r>'
            . '</w:p>'
            . '<w:p><w:pPr><w:jc w:val="left"/></w:pPr>'
            . '<w:r><w:rPr><w:color w:val="' . $this->xmlEscape($deltaColor) . '"/><w:sz w:val="16"/></w:rPr><w:t>' . $this->xmlEscape($deltaText) . '</w:t></w:r>'
            . '</w:p>'
            . '</w:txbxContent>'
            . '</v:textbox>'
            . '</v:roundrect>'
            . '</w:pict></w:r>'
            . '</w:p>';
    }

    private function buildWordDemographicShapeCardsXml(array $profiles): string
    {
        if (empty($profiles)) {
            return '';
        }

        $xml = '<w:tbl>'
            . '<w:tblPr><w:tblW w:w="0" w:type="auto"/><w:tblLayout w:type="fixed"/></w:tblPr>'
            . '<w:tblGrid>'
            . '<w:gridCol w:w="5000"/><w:gridCol w:w="5000"/><w:gridCol w:w="5000"/>'
            . '</w:tblGrid>';

        $count = count($profiles);
        for ($i = 0; $i < $count; $i += 3) {
            $xml .= '<w:tr>';
            for ($col = 0; $col < 3; $col++) {
                $idx = $i + $col;
                $xml .= '<w:tc><w:tcPr><w:tcW w:w="5000" w:type="dxa"/>'
                    . '<w:tcMar><w:top w:w="80" w:type="dxa"/><w:left w:w="80" w:type="dxa"/><w:bottom w:w="80" w:type="dxa"/><w:right w:w="80" w:type="dxa"/></w:tcMar>'
                    . '</w:tcPr>';

                if ($idx < $count) {
                    $xml .= $this->buildWordDemographicShapeCardXml($profiles[$idx], '230pt');
                } else {
                    $xml .= '<w:p/>';
                }

                $xml .= '</w:tc>';
            }
            $xml .= '</w:tr>';
        }

        $xml .= '</w:tbl>';

        return $xml;
    }

    private function formatDeltaPercent(float $current, float $delta): string
    {
        $previous = $current - $delta;
        if (abs($previous) < 0.0001) {
            return $current > 0 ? 'N/A' : '0.0%';
        }

        $percent = ($delta / $previous) * 100;

        return number_format($percent, 1, ',', ' ') . '%';
    }

    private function formatDeltaText(array $delta, string $comparisonReferenceLabel): string
    {
        $value = $delta['value'] ?? 0;
        $percent = $delta['percent'] ?? null;

        if ($value > 0) {
            return '↑ Différence: +' . number_format($value, 0, ',', ' ')
                . ($percent !== null ? ' (+' . $percent . '%)' : '')
                . ' vs ' . $comparisonReferenceLabel;
        }

        if ($value < 0) {
            return '↓ Différence: ' . number_format($value, 0, ',', ' ')
                . ($percent !== null ? ' (' . $percent . '%)' : '')
                . ' vs ' . $comparisonReferenceLabel;
        }

        return '→ Différence: 0 vs ' . $comparisonReferenceLabel;
    }

    private function buildDemographicProfiles(array $payload, string $comparisonReferenceLabel): array
    {
        return [
            [
                'icon' => '👥',
                'label' => 'Total PDI',
                'value' => (int) ($payload['stats']['total_pdi'] ?? 0),
                'deltaValue' => (int) ($payload['deltas']['total_pdi']['value'] ?? 0),
                'deltaText' => $this->formatDeltaText($payload['deltas']['total_pdi'] ?? [], $comparisonReferenceLabel),
            ],
            [
                'icon' => '♂',
                'label' => 'Hommes',
                'value' => (int) ($payload['stats']['hommes'] ?? 0),
                'deltaValue' => (int) ($payload['deltas']['hommes']['value'] ?? 0),
                'deltaText' => $this->formatDeltaText($payload['deltas']['hommes'] ?? [], $comparisonReferenceLabel),
            ],
            [
                'icon' => '♀',
                'label' => 'Femmes',
                'value' => (int) ($payload['stats']['femmes'] ?? 0),
                'deltaValue' => (int) ($payload['deltas']['femmes']['value'] ?? 0),
                'deltaText' => $this->formatDeltaText($payload['deltas']['femmes'] ?? [], $comparisonReferenceLabel),
            ],
            [
                'icon' => '♿',
                'label' => 'Personnes vivantes avec handicap',
                'value' => (int) ($payload['stats']['personnes_handicap'] ?? 0),
                'deltaValue' => (int) ($payload['deltas']['personnes_handicap']['value'] ?? 0),
                'deltaText' => $this->formatDeltaText($payload['deltas']['personnes_handicap'] ?? [], $comparisonReferenceLabel),
            ],
            [
                'icon' => '🏠',
                'label' => 'Ménages',
                'value' => (int) ($payload['stats']['menages'] ?? 0),
                'deltaValue' => (int) ($payload['deltas']['menages']['value'] ?? 0),
                'deltaText' => $this->formatDeltaText($payload['deltas']['menages'] ?? [], $comparisonReferenceLabel),
            ],
            [
                'icon' => '🙂',
                'label' => 'Enfants',
                'value' => (int) ($payload['stats']['enfants'] ?? 0),
                'deltaValue' => (int) ($payload['deltas']['enfants']['value'] ?? 0),
                'deltaText' => $this->formatDeltaText($payload['deltas']['enfants'] ?? [], $comparisonReferenceLabel),
            ],
            [
                'icon' => '🧑',
                'label' => 'Adultes',
                'value' => (int) ($payload['stats']['adultes'] ?? 0),
                'deltaValue' => (int) ($payload['deltas']['adultes']['value'] ?? 0),
                'deltaText' => $this->formatDeltaText($payload['deltas']['adultes'] ?? [], $comparisonReferenceLabel),
            ],
            [
                'icon' => '👴',
                'label' => 'Personnes âgées',
                'value' => (int) ($payload['stats']['personnes_agees'] ?? 0),
                'deltaValue' => (int) ($payload['deltas']['personnes_agees']['value'] ?? 0),
                'deltaText' => $this->formatDeltaText($payload['deltas']['personnes_agees'] ?? [], $comparisonReferenceLabel),
            ],
        ];
    }

    private function createTempPngFromBinary(string $binary): ?string
    {
        $tmpBase = tempnam(sys_get_temp_dir(), 'dashboard_map_');
        if ($tmpBase === false) {
            return null;
        }

        $tmpPath = $tmpBase . '.png';
        @rename($tmpBase, $tmpPath);

        if (@file_put_contents($tmpPath, $binary) === false) {
            @unlink($tmpPath);
            return null;
        }

        return $tmpPath;
    }

    private function buildWordTableXml(array $rows, string $headerFill = 'C2E3F5', string $headerTextColor = '323234'): string
    {
        if (empty($rows)) {
            return '';
        }

        $xml = '<w:tbl>'
            . '<w:tblPr>'
            . '<w:tblW w:w="0" w:type="auto"/>'
            . '<w:tblLayout w:type="autofit"/>'
            . '<w:tblBorders>'
            . '<w:top w:val="single" w:sz="8" w:color="D1D1D2"/>'
            . '<w:left w:val="single" w:sz="8" w:color="D1D1D2"/>'
            . '<w:bottom w:val="single" w:sz="8" w:color="D1D1D2"/>'
            . '<w:right w:val="single" w:sz="8" w:color="D1D1D2"/>'
            . '<w:insideH w:val="single" w:sz="6" w:color="E8E8E9"/>'
            . '<w:insideV w:val="single" w:sz="6" w:color="E8E8E9"/>'
            . '</w:tblBorders>'
            . '</w:tblPr>';

        foreach ($rows as $rowIndex => $row) {
            $xml .= '<w:tr>';
            foreach ($row as $columnIndex => $cell) {
                $text = (string) $cell;
                $isHeader = $rowIndex === 0;
                $normalized = str_replace([' ', ','], ['', '.'], trim($text));
                $isNumeric = !$isHeader && $columnIndex > 0 && is_numeric($normalized);

                $xml .= '<w:tc><w:tcPr><w:tcMar><w:top w:w="80" w:type="dxa"/><w:left w:w="100" w:type="dxa"/><w:bottom w:w="80" w:type="dxa"/><w:right w:w="100" w:type="dxa"/></w:tcMar>';
                if ($isHeader) {
                    $xml .= '<w:shd w:val="clear" w:color="auto" w:fill="' . $this->xmlEscape($headerFill) . '"/>';
                } elseif ($rowIndex % 2 === 0) {
                    $xml .= '<w:shd w:val="clear" w:color="auto" w:fill="F5F5F5"/>';
                }
                $xml .= '</w:tcPr>';
                $xml .= '<w:p><w:pPr><w:jc w:val="' . ($isNumeric ? 'right' : 'left') . '"/></w:pPr><w:r><w:rPr>';

                if ($isHeader) {
                    $xml .= '<w:b/><w:color w:val="' . $this->xmlEscape($headerTextColor) . '"/>';
                } else {
                    $xml .= '<w:color w:val="545456"/>';
                }

                $xml .= '<w:sz w:val="18"/></w:rPr><w:t>' . $this->xmlEscape($text) . '</w:t></w:r></w:p></w:tc>';
            }
            $xml .= '</w:tr>';
        }

        $xml .= '</w:tbl>';

        return $xml;
    }

    private function buildWordImageXml(string $relationshipId, string $name, int $docPrId, int $widthPx = 900, int $heightPx = 420): string
    {
        $cx = $widthPx * 9525;
        $cy = $heightPx * 9525;

        return '<w:p><w:pPr><w:spacing w:before="80" w:after="160"/><w:jc w:val="center"/></w:pPr><w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0">'
            . '<wp:extent cx="' . $cx . '" cy="' . $cy . '"/>'
            . '<wp:docPr id="' . $docPrId . '" name="' . $this->xmlEscape($name) . '"/>'
            . '<a:graphic>'
            . '<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            . '<pic:pic>'
            . '<pic:nvPicPr><pic:cNvPr id="0" name="' . $this->xmlEscape($name) . '"/><pic:cNvPicPr/></pic:nvPicPr>'
            . '<pic:blipFill><a:blip r:embed="' . $this->xmlEscape($relationshipId) . '"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
            . '<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
            . '</pic:pic>'
            . '</a:graphicData>'
            . '</a:graphic>'
            . '</wp:inline></w:drawing></w:r></w:p>';
    }

    private function fetchQuickChartPng(array $chartConfig, int $width = 1000, int $height = 450): ?string
    {
        $query = http_build_query([
            'width' => $width,
            'height' => $height,
            'backgroundColor' => 'white',
            'format' => 'png',
            'devicePixelRatio' => 2,
            'c' => json_encode($chartConfig, JSON_UNESCAPED_UNICODE),
        ]);

        $url = 'https://quickchart.io/chart?' . $query;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $binary = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($binary === false || $httpCode >= 400 || !empty($error)) {
            return null;
        }

        return $binary;
    }

    private function buildQuickChartConfigs(array $payload): array
    {
        $ageLabels = ['0-5 ans', '6-17 ans', '18-59 ans', '60+ ans', 'Non spécifié'];
        $ageValues = [
            (int) ($payload['ageDistribution']['0-5 ans'] ?? 0),
            (int) ($payload['ageDistribution']['6-17 ans'] ?? 0),
            (int) ($payload['ageDistribution']['18-59 ans'] ?? 0),
            (int) ($payload['ageDistribution']['60+ ans'] ?? 0),
            (int) ($payload['ageDistribution']['Non spécifié'] ?? 0),
        ];

        $provinceLabels = [];
        $provinceValues = [];
        foreach ($payload['provinceDistribution'] as $province => $value) {
            $provinceLabels[] = (string) ($province ?: 'Non spécifié');
            $provinceValues[] = (int) $value;
        }

        return [
            'age' => [
                'type' => 'bar',
                'data' => [
                    'labels' => $ageLabels,
                    'datasets' => [[
                        'label' => 'Population',
                        'backgroundColor' => '#3B82F6',
                        'borderColor' => '#1D4ED8',
                        'borderWidth' => 1,
                        'data' => $ageValues,
                    ]],
                ],
                'options' => [
                    'responsive' => true,
                    'layout' => ['padding' => ['top' => 8, 'left' => 8, 'right' => 8, 'bottom' => 4]],
                    'plugins' => [
                        'title' => ['display' => true, 'text' => 'Répartition de la population par tranche d\'âge', 'fontSize' => 18, 'fontColor' => '#111827'],
                        'legend' => ['display' => false],
                    ],
                    'scales' => [
                        'xAxes' => [[
                            'ticks' => ['fontSize' => 11, 'fontColor' => '#6B7280'],
                            'gridLines' => ['display' => false],
                        ]],
                        'yAxes' => [[
                            'ticks' => ['beginAtZero' => true, 'fontColor' => '#6B7280'],
                            'gridLines' => ['color' => '#E5E7EB'],
                        ]],
                    ],
                ],
            ],
            'gender' => [
                'type' => 'doughnut',
                'data' => [
                    'labels' => ['Femmes', 'Hommes'],
                    'datasets' => [[
                        'backgroundColor' => ['#EC4899', '#3B82F6'],
                        'data' => [
                            (int) ($payload['stats']['femmes'] ?? 0),
                            (int) ($payload['stats']['hommes'] ?? 0),
                        ],
                    ]],
                ],
                'options' => [
                    'cutoutPercentage' => 68,
                    'layout' => ['padding' => ['top' => 8, 'left' => 8, 'right' => 8, 'bottom' => 8]],
                    'plugins' => [
                        'title' => ['display' => true, 'text' => 'Distribution de la population par sexe', 'fontSize' => 18, 'fontColor' => '#111827'],
                        'legend' => [
                            'display' => true,
                            'position' => 'bottom',
                            'labels' => ['fontColor' => '#6B7280', 'boxWidth' => 18],
                        ],
                    ],
                ],
            ],
            'province' => [
                'type' => 'horizontalBar',
                'data' => [
                    'labels' => $provinceLabels,
                    'datasets' => [[
                        'label' => 'Population',
                        'backgroundColor' => '#8B5CF6',
                        'data' => $provinceValues,
                    ]],
                ],
                'options' => [
                    'layout' => ['padding' => ['top' => 8, 'left' => 8, 'right' => 8, 'bottom' => 4]],
                    'plugins' => [
                        'title' => ['display' => true, 'text' => 'Distribution de la population dans les provinces touchées par la crise', 'fontSize' => 17, 'fontColor' => '#111827'],
                        'legend' => ['display' => false],
                    ],
                    'scales' => [
                        'xAxes' => [[
                            'ticks' => ['beginAtZero' => true, 'fontColor' => '#6B7280'],
                            'gridLines' => ['color' => '#E5E7EB'],
                        ]],
                        'yAxes' => [[
                            'ticks' => ['fontSize' => 10, 'fontColor' => '#6B7280'],
                            'gridLines' => ['display' => false],
                        ]],
                    ],
                ],
            ],
            'map' => [
                'type' => 'bubble',
                'data' => [
                    'datasets' => [[
                        'label' => 'Sites',
                        'backgroundColor' => 'rgba(37, 99, 235, 0.7)',
                        'borderColor' => '#1D4ED8',
                        'borderWidth' => 1,
                        'data' => collect(array_slice($payload['mapPoints'] ?? [], 0, 25))
                            ->map(function ($point) {
                                $population = max(1, (int) ($point['population'] ?? 0));

                                return [
                                    'x' => (float) ($point['longitude'] ?? 0),
                                    'y' => (float) ($point['latitude'] ?? 0),
                                    'r' => max(4, min(13, (int) round(sqrt($population) / 18))),
                                ];
                            })
                            ->values()
                            ->all(),
                    ]],
                ],
                'options' => [
                    'layout' => ['padding' => ['top' => 8, 'left' => 8, 'right' => 8, 'bottom' => 4]],
                    'plugins' => [
                        'title' => ['display' => true, 'text' => 'Carte des sites (répartition géographique)', 'fontSize' => 17, 'fontColor' => '#111827'],
                        'legend' => ['display' => false],
                    ],
                    'scales' => [
                        'xAxes' => [[
                            'scaleLabel' => ['display' => true, 'labelString' => 'Longitude'],
                            'ticks' => ['fontColor' => '#6B7280'],
                            'gridLines' => ['color' => '#E5E7EB'],
                        ]],
                        'yAxes' => [[
                            'scaleLabel' => ['display' => true, 'labelString' => 'Latitude'],
                            'ticks' => ['fontColor' => '#6B7280'],
                            'gridLines' => ['color' => '#E5E7EB'],
                        ]],
                    ],
                ],
            ],
        ];
    }

    private function buildTextBar(int $value, int $max, int $width = 24): string
    {
        if ($max <= 0) {
            return '';
        }

        $filled = (int) round(($value / $max) * $width);
        $filled = max(0, min($width, $filled));

        return str_repeat('#', $filled) . str_repeat('.', $width - $filled);
    }

    private function buildDashboardWordDocument(array $payload, array $filters, array $imageRelations = []): string
    {
        $totalPdi = max(1, (int) ($payload['stats']['total_pdi'] ?? 0));
        $comparisonReferenceLabel = (string) ($payload['comparisonPeriod'] ?: 'aucune période antérieure disponible');
        $demographicProfiles = $this->buildDemographicProfiles($payload, $comparisonReferenceLabel);
        $generatedAt = now()->format('d/m/Y H:i');

        $contextCards = [
            ['label' => 'Période demandée', 'value' => $payload['selectedPeriod']],
            ['label' => 'Période considérée', 'value' => $payload['consideredPeriod']],
            ['label' => 'Période de comparaison', 'value' => $payload['comparisonPeriod'] ?: 'Aucune'],
            ['label' => 'Province', 'value' => $filters['province']],
            ['label' => 'Territoire', 'value' => $filters['territoire']],
            ['label' => 'Zone de santé', 'value' => $filters['commune']],
            ['label' => 'Site', 'value' => $filters['site']],
            ['label' => 'Coordinateur', 'value' => $filters['coordinateur']],
            ['label' => 'Gestionnaire', 'value' => $filters['gestionnaire']],
            ['label' => 'Mécanisme CCCM', 'value' => $filters['categorieSite']],
        ];

        $keyMetricsCards = [
            ['label' => 'Total PDI', 'value' => number_format((int) ($payload['stats']['total_pdi'] ?? 0), 0, ',', ' ')],
            ['label' => 'Ménages', 'value' => number_format((int) ($payload['stats']['menages'] ?? 0), 0, ',', ' ')],
            ['label' => 'Enfants', 'value' => number_format((int) ($payload['stats']['enfants'] ?? 0), 0, ',', ' ')],
            ['label' => 'Personnes âgées', 'value' => number_format((int) ($payload['stats']['personnes_agees'] ?? 0), 0, ',', ' ')],
        ];

        $provinceRows = [['Province', 'Population', 'Part (%)']];
        foreach ($payload['provinceDistribution'] as $province => $value) {
            $provinceRows[] = [
                $province ?: 'Non spécifié',
                number_format((int) $value, 0, ',', ' '),
                number_format(((int) $value * 100) / $totalPdi, 1, ',', ' ') . '%',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<w:document '
            . 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" '
            . 'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" '
            . 'xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" '
            . 'xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture" '
            . 'xmlns:v="urn:schemas-microsoft-com:vml">';
        $xml .= '<w:body>';
        $xml .= $this->buildWordHeadingXml('Tableau de bord DMS CCCM', '2A87C8', 34, 'center');
        $xml .= $this->buildWordParagraphXml('Version Comité de pilotage — Synthèse 1 page', '545456', 20, true, 'center');
        $xml .= $this->buildWordParagraphXml('Synthèse opérationnelle des personnes déplacées internes', '545456', 18, false, 'center');
        $xml .= $this->buildWordParagraphXml('Généré le ' . $generatedAt, '545456', 16, false, 'center');
        $xml .= $this->buildWordDividerXml('D1D1D2');

        $xml .= $this->buildWordHeadingXml('Contexte décisionnel', '2A87C8', 24);
        $xml .= $this->buildWordSingleContextShapeXml($contextCards, 'E8F4FB', 'C2E3F5', '323234', '545456');

        $xml .= $this->buildWordHeadingXml('Indicateurs clés détaillés', 'D48C74', 22);
        $xml .= $this->buildWordShapeCardsXml($keyMetricsCards, 'FDF6F4', 'FAE7E1', '323234', '545456');

        if (!empty($payload['fallbackNote'])) {
            $xml .= $this->buildWordParagraphXml('Alerte : ' . $payload['fallbackNote'], '9D4838', 18, true);
        }

        $xml .= $this->buildWordPageBreakXml();
        $xml .= $this->buildWordHeadingXml('Détails analytiques', '2A87C8', 28);
        $xml .= $this->buildWordParagraphXml('Le contenu ci-dessous présente le détail démographique, la carte et les distributions par catégorie.', '545456', 16);

        $xml .= $this->buildWordHeadingXml('Profil démographique de la population dans les sites', '2A87C8', 24);
        $xml .= $this->buildWordParagraphXml('Données sexe, âge et personnes vivantes avec handicap enregistrés', '545456', 16);
        $xml .= $this->buildWordDemographicShapeCardsXml($demographicProfiles);

        $docPrId = 1;
        $xml .= $this->buildWordHeadingXml('Graphiques (même présentation que le dashboard)', '545456', 24);
        $xml .= $this->buildWordParagraphXml('La carte est affichée en premier visuel, suivie des graphiques démographiques et provinciaux.', '545456', 16);

        if (!empty($imageRelations['map'])) {
            $xml .= $this->buildWordParagraphXml('Carte des sites', '2A87C8', 18, true, 'center');
            $xml .= $this->buildWordImageXml($imageRelations['map'], 'map-chart.png', $docPrId++, 960, 540);
        }

        if (!empty($imageRelations['age'])) {
            $xml .= $this->buildWordParagraphXml('Répartition par âge', 'D48C74', 18, true, 'center');
            $xml .= $this->buildWordImageXml($imageRelations['age'], 'age-chart.png', $docPrId++, 920, 430);
        }

        if (!empty($imageRelations['gender'])) {
            $xml .= $this->buildWordParagraphXml('Distribution par sexe', '545456', 18, true, 'center');
            $xml .= $this->buildWordImageXml($imageRelations['gender'], 'gender-chart.png', $docPrId++, 760, 430);
        }

        if (!empty($imageRelations['province'])) {
            $xml .= $this->buildWordParagraphXml('Distribution par province', '9D4838', 18, true, 'center');
            $xml .= $this->buildWordImageXml($imageRelations['province'], 'province-chart.png', $docPrId++, 980, 520);
        }

        if (empty($imageRelations['age']) && empty($imageRelations['gender']) && empty($imageRelations['province'])) {
            $xml .= $this->buildWordParagraphXml('Note: Impossible de récupérer les graphiques image automatiquement sur ce serveur.', '9D4838', 18, true);
        }

        $xml .= $this->buildWordHeadingXml('Répartition par province', '9D4838', 22);
        $xml .= $this->buildWordTableXml($provinceRows, 'F5CCC5', '323234');
        $xml .= '<w:sectPr><w:pgSz w:w="16838" w:h="11906" w:orient="landscape"/><w:pgMar w:top="900" w:right="900" w:bottom="900" w:left="900"/></w:sectPr>';
        $xml .= '</w:body></w:document>';

        return $xml;
    }

    private function exportDashboardWord(array $payload, array $filters, ?string $mapBinaryOverride = null)
    {
        $tempDir = sys_get_temp_dir();
        $tmpBase = tempnam($tempDir, 'dashboard_word_');
        $docxFile = $tmpBase . '.docx';
        rename($tmpBase, $docxFile);

        $zip = new ZipArchive();
        $zip->open($docxFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $chartConfigs = $this->buildQuickChartConfigs($payload);
        $images = [
            'age' => $this->fetchQuickChartPng($chartConfigs['age'], 1400, 650),
            'gender' => $this->fetchQuickChartPng($chartConfigs['gender'], 1000, 560),
            'province' => $this->fetchQuickChartPng($chartConfigs['province'], 1450, 780),
            'map' => $mapBinaryOverride ?: $this->fetchQuickChartPng($chartConfigs['map'], 1600, 900),
        ];

        $imageRelations = [];
        $documentRels = [
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>',
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">',
        ];

        $relationIndex = 2;
        foreach ($images as $key => $binary) {
            if (!$binary) {
                continue;
            }

            $target = 'media/' . $key . '-chart.png';
            $rId = 'rId' . $relationIndex;
            $relationIndex++;

            $zip->addFromString('word/' . $target, $binary);
            $documentRels[] = '<Relationship Id="' . $rId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="' . $target . '"/>';
            $imageRelations[$key] = $rId;
        }

        $documentRels[] = '</Relationships>';

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
            '<Default Extension="xml" ContentType="application/xml"/>' .
            '<Default Extension="png" ContentType="image/png"/>' .
            '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>' .
            '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>' .
            '</Relationships>');
        $zip->addFromString('word/_rels/document.xml.rels', implode('', $documentRels));
        $zip->addFromString('word/document.xml', $this->buildDashboardWordDocument($payload, $filters, $imageRelations));
        $zip->close();

        return response()->download($docxFile, 'dashboard-' . now()->format('Y-m-d-His') . '.docx')->deleteFileAfterSend(true);
    }

    private function extractMapSnapshotBinary(Request $request): ?string
    {
        $snapshot = $request->input('map_snapshot');
        if (!is_string($snapshot) || trim($snapshot) === '') {
            return null;
        }

        if (!preg_match('/^data:image\/(png|jpeg);base64,/', $snapshot)) {
            return null;
        }

        $parts = explode(',', $snapshot, 2);
        if (count($parts) !== 2) {
            return null;
        }

        $binary = base64_decode($parts[1], true);
        if ($binary === false || $binary === '') {
            return null;
        }

        if (strlen($binary) > 12 * 1024 * 1024) {
            return null;
        }

        return $binary;
    }

    public function exportExcel(Request $request)
    {
        $selectedPeriodDate = $this->resolveSelectedPeriod($request->input('periode')) ?? now()->startOfMonth();
        $payload = $this->buildDashboardPayload($selectedPeriodDate, $request);
        $filters = $this->buildDashboardFilters($request);
        $mapSnapshotBinary = $this->extractMapSnapshotBinary($request);

        return $this->exportDashboardExcel($payload, $filters, $mapSnapshotBinary);
    }

    public function exportWord(Request $request)
    {
        $selectedPeriodDate = $this->resolveSelectedPeriod($request->input('periode')) ?? now()->startOfMonth();
        $payload = $this->buildDashboardPayload($selectedPeriodDate, $request);
        $filters = $this->buildDashboardFilters($request);
        $mapSnapshotBinary = $this->extractMapSnapshotBinary($request);

        return $this->exportDashboardWord($payload, $filters, $mapSnapshotBinary);
    }

    /**
     * Display the dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $selectedPeriodDate = $this->resolveSelectedPeriod($request->input('periode')) ?? now()->startOfMonth();
        $payload = $this->buildDashboardPayload($selectedPeriodDate, $request);

        return view('dashboard', [
            'stats' => $payload['stats'],
            'ageDistribution' => $payload['ageDistribution'],
            'provinceDistribution' => $payload['provinceDistribution'],
            'selectedPeriod' => $payload['selectedPeriod'],
            'consideredPeriod' => $payload['consideredPeriod'],
            'comparisonPeriod' => $payload['comparisonPeriod'],
            'deltas' => $payload['deltas'],
            'dashboardFallbackNote' => $payload['fallbackNote'],
            'dashboardUsedFallback' => $payload['usedFallback'],
        ]);
    }
}
