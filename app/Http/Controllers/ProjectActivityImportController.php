<?php

namespace App\Http\Controllers;

use App\Models\Cluster;
use App\Models\Commune;
use App\Models\Organisation;
use App\Models\ProgramIndicator;
use App\Models\ProgramSubActivity;
use App\Models\Project;
use App\Models\ProjectActivity;
use App\Models\Province;
use App\Models\Site;
use App\Models\Territoire;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectActivityImportController extends Controller
{
    private const FIRST_DATA_ROW = 6;
    private const LAST_DATA_ROW = 500;

    public function index()
    {
        $user = auth()->user();

        $organisations = $user->isSuperAdmin()
            ? Organisation::query()->orderBy('name')->get(['id', 'name', 'code'])
            : collect();

        return view('project-activities.import', [
            'isSuperAdmin'     => $user->isSuperAdmin(),
            'organisationName' => $user->organisation?->name,
            'organisations'    => $organisations,
        ]);
    }

    public function downloadTemplate(Request $request): StreamedResponse
    {
        $user = auth()->user();

        // Superadmin may scope the template to a single organisation via ?organisation_id=X.
        $overrideOrgId = null;
        if ($user->isSuperAdmin()) {
            $rawOrgId = (int) $request->query('organisation_id', 0);
            if ($rawOrgId > 0) {
                $overrideOrgId = $rawOrgId;
            }
        }

        [$organisations, $provinces, $territoires, $communes, $sites, $clusters, $projects, $clusterIndicators] = $this->loadTemplateContext($user, $overrideOrgId);

        // When superadmin picks a specific org, the sheet behaves like a single-org template.
        $isSuperAdminMode = $user->isSuperAdmin() && $overrideOrgId === null;

        $spreadsheet = new Spreadsheet();
        $this->buildInstructionsSheet($spreadsheet, $isSuperAdminMode);
        $sheet = $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex($spreadsheet->getIndex($sheet));
        $sheet->setTitle('IMPORTATION');

        $orgLabels = $organisations->map(fn ($organisation) => $this->formatOrganisationLabel($organisation))->values()->all();
        $orgIds = $organisations->map(fn ($organisation) => (string) $organisation->id)->values()->all();

        $headers = [
            'A' => ['label' => 'organisation *', 'comment' => 'Pour le super admin: choisir l\'organisation concernée. Pour une organisation: valeur pré-remplie et protégée.'],
            'B' => ['label' => 'cluster *', 'comment' => 'La liste dépend de l\'organisation choisie.'],
            'C' => ['label' => 'projet *', 'comment' => 'La liste dépend du cluster choisi.'],
            'D' => ['label' => 'date_rapportage *', 'comment' => 'Format AAAA-MM-JJ.'],
            'E' => ['label' => 'province *', 'comment' => 'Cascade géographique niveau 1.'],
            'F' => ['label' => 'territoire *', 'comment' => 'Cascade selon la province.'],
            'G' => ['label' => 'zone_sante *', 'comment' => 'Cascade selon le territoire.'],
            'H' => ['label' => 'site', 'comment' => 'Optionnel. Cascade selon la zone de santé uniquement.'],
            'I' => ['label' => 'indicateur', 'comment' => 'Optionnel. Filtré par cluster.'],
            'J' => ['label' => 'activite *', 'comment' => 'Cascade principale selon le cluster.'],
            'K' => ['label' => 'sous_activite', 'comment' => 'Optionnel. Cascade selon l\'activité.'],
            'L' => ['label' => 'statut_beneficiaire', 'comment' => 'Une valeur par ligne: pdi, retourne, refugie, communaute_hote, autre.'],
            'M' => ['label' => 'filles_0_17', 'comment' => 'Nombre entier supérieur ou égal à 0.'],
            'N' => ['label' => 'filles_18_59', 'comment' => 'Nombre entier supérieur ou égal à 0.'],
            'O' => ['label' => 'filles_60_plus', 'comment' => 'Nombre entier supérieur ou égal à 0.'],
            'P' => ['label' => 'garcons_0_17', 'comment' => 'Nombre entier supérieur ou égal à 0.'],
            'Q' => ['label' => 'hommes_18_59', 'comment' => 'Nombre entier supérieur ou égal à 0.'],
            'R' => ['label' => 'hommes_60_plus', 'comment' => 'Nombre entier supérieur ou égal à 0.'],
            'S' => ['label' => 'personnes_handicap', 'comment' => 'Optionnel. Nombre entier supérieur ou égal à 0.'],
            'T' => ['label' => 'cout_activite_usd', 'comment' => 'Optionnel. Coût en USD.'],
            'U' => ['label' => 'commentaire', 'comment' => 'Optionnel. Notes libres.'],
            'V' => ['label' => 'total_beneficiaires (auto)', 'comment' => 'Calcul automatique. Ne pas modifier.'],
        ];

        $this->decorateImportSheet($sheet, $headers);

        $defaultOrganisation = $organisations->first();
        $defaultCluster = $defaultOrganisation
            ? $clusters->filter(fn ($cluster) => in_array($defaultOrganisation->id, $cluster->organisations->pluck('id')->all(), true))->sortBy('name')->first()
            : null;
        $defaultProject = $defaultCluster
            ? $projects->first(fn ($project) => (int) $project->cluster_id === (int) $defaultCluster->id)
            : null;
        $defaultProvince = $provinces->first();
        $defaultTerritoire = $defaultProvince ? $territoires->firstWhere('province_id', $defaultProvince->id) : null;
        $defaultCommune = $defaultTerritoire ? $communes->firstWhere('territoire_id', $defaultTerritoire->id) : null;
        $defaultSite = $defaultCommune && $defaultOrganisation
            ? $sites->first(fn ($site) => (int) $site->commune_id === (int) $defaultCommune->id && (int) $site->organisation_id === (int) $defaultOrganisation->id)
            : null;
        $defaultIndicator = $defaultCluster ? ($clusterIndicators[$defaultCluster->id]['indicators']->first() ?? null) : null;
        $defaultActivity = $defaultIndicator ? ($defaultIndicator->activities->first() ?? null) : null;
        $defaultSubActivity = $defaultActivity ? ($defaultActivity->subActivities->first() ?? null) : null;

        $exampleRow = self::FIRST_DATA_ROW - 1;
        $sheet->setCellValue("A{$exampleRow}", $defaultOrganisation ? $this->formatOrganisationLabel($defaultOrganisation) : '');
        $sheet->setCellValue("B{$exampleRow}", $defaultCluster ? $this->formatClusterLabel($defaultCluster) : '');
        $sheet->setCellValue("C{$exampleRow}", $defaultProject ? $this->formatProjectLabel($defaultProject) : '');
        $sheet->setCellValue("D{$exampleRow}", now()->format('Y-m-d'));
        $sheet->setCellValue("E{$exampleRow}", $defaultProvince ? $this->formatProvinceLabel($defaultProvince) : '');
        $sheet->setCellValue("F{$exampleRow}", $defaultTerritoire ? $this->formatTerritoireLabel($defaultTerritoire) : '');
        $sheet->setCellValue("G{$exampleRow}", $defaultCommune ? $this->formatCommuneLabel($defaultCommune) : '');
        $sheet->setCellValue("H{$exampleRow}", $defaultSite ? $this->formatSiteLabel($defaultSite) : '');
        $sheet->setCellValue("I{$exampleRow}", $defaultIndicator ? $this->formatIndicatorLabel($defaultIndicator) : '');
        $sheet->setCellValue("J{$exampleRow}", $defaultActivity ? $this->formatActivityLabel($defaultActivity) : '');
        $sheet->setCellValue("K{$exampleRow}", $defaultSubActivity ? $this->formatSubActivityLabel($defaultSubActivity) : '');
        $sheet->setCellValue("L{$exampleRow}", 'pdi');
        $sheet->setCellValue("M{$exampleRow}", 12);
        $sheet->setCellValue("N{$exampleRow}", 7);
        $sheet->setCellValue("O{$exampleRow}", 3);
        $sheet->setCellValue("P{$exampleRow}", 10);
        $sheet->setCellValue("Q{$exampleRow}", 5);
        $sheet->setCellValue("R{$exampleRow}", 1);
        $sheet->setCellValue("S{$exampleRow}", 2);
        $sheet->setCellValue("T{$exampleRow}", 2500);
        $sheet->setCellValue("U{$exampleRow}", 'Exemple de ligne importable');
        $sheet->setCellValue("V{$exampleRow}", "=SUM(M{$exampleRow}:R{$exampleRow})");
        $sheet->getStyle("A{$exampleRow}:V{$exampleRow}")->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '6B7280']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']],
        ]);

        $listsSheet = $spreadsheet->createSheet();
        $listsSheet->setTitle('LISTES');
        $listsSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
        $columnIndex = 1;

        $this->addNamedList($spreadsheet, $listsSheet, $columnIndex, 'ORGS_ALL_LABELS', $orgLabels);
        $this->addNamedList($spreadsheet, $listsSheet, $columnIndex, 'ORGS_ALL_IDS', $orgIds);
        $this->addNamedList($spreadsheet, $listsSheet, $columnIndex, 'STATUS_ALL', ['pdi', 'retourne', 'refugie', 'communaute_hote', 'autre']);
        $this->addNamedList(
            $spreadsheet,
            $listsSheet,
            $columnIndex,
            'PROVINCES_ALL_LABELS',
            $provinces->map(fn ($province) => $this->formatProvinceLabel($province))->values()->all()
        );
        $this->addNamedList($spreadsheet, $listsSheet, $columnIndex, 'PROVINCES_ALL_IDS', $provinces->map(fn ($province) => (string) $province->id)->values()->all());

        foreach ($organisations as $organisation) {
            $orgClusters = $clusters
                ->filter(fn ($cluster) => in_array($organisation->id, $cluster->organisations->pluck('id')->all(), true))
                ->sortBy('name')
                ->map(fn ($cluster) => $this->formatClusterLabel($cluster))
                ->values()
                ->all();

            $this->addNamedList($spreadsheet, $listsSheet, $columnIndex, 'ORG_' . $organisation->id . '_CLUSTERS', $orgClusters);
        }

        $this->addNamedList($spreadsheet, $listsSheet, $columnIndex, 'CLUSTERS_ALL_LABELS', $clusters->map(fn ($cluster) => $this->formatClusterLabel($cluster))->values()->all());
        $this->addNamedList($spreadsheet, $listsSheet, $columnIndex, 'CLUSTERS_ALL_IDS', $clusters->map(fn ($cluster) => (string) $cluster->id)->values()->all());

        foreach ($projects->groupBy('cluster_id') as $clusterId => $clusterProjects) {
            $this->addNamedList(
                $spreadsheet,
                $listsSheet,
                $columnIndex,
                'CLUSTER_' . (int) $clusterId . '_PROJECTS',
                $clusterProjects->sortBy('name')->map(fn ($project) => $this->formatProjectLabel($project))->values()->all()
            );
        }

        $this->addNamedList($spreadsheet, $listsSheet, $columnIndex, 'PROJECTS_ALL_LABELS', $projects->map(fn ($project) => $this->formatProjectLabel($project))->values()->all());
        $this->addNamedList($spreadsheet, $listsSheet, $columnIndex, 'PROJECTS_ALL_IDS', $projects->map(fn ($project) => (string) $project->id)->values()->all());

        foreach ($provinces as $province) {
            $provinceTerritoires = $territoires
                ->where('province_id', $province->id)
                ->sortBy('name')
                ->map(fn ($territoire) => $this->formatTerritoireLabel($territoire))
                ->values()
                ->all();

            $this->addNamedList($spreadsheet, $listsSheet, $columnIndex, 'PROV_' . $province->id . '_TERRITOIRES', $provinceTerritoires);
        }

        $this->addNamedList($spreadsheet, $listsSheet, $columnIndex, 'TERRITOIRES_ALL_LABELS', $territoires->map(fn ($territoire) => $this->formatTerritoireLabel($territoire))->values()->all());
        $this->addNamedList($spreadsheet, $listsSheet, $columnIndex, 'TERRITOIRES_ALL_IDS', $territoires->map(fn ($territoire) => (string) $territoire->id)->values()->all());

        foreach ($territoires as $territoire) {
            $territoireCommunes = $communes
                ->where('territoire_id', $territoire->id)
                ->sortBy('name')
            ->map(fn ($commune) => $this->formatCommuneLabel($commune))
                ->values()
                ->all();

            $this->addNamedList($spreadsheet, $listsSheet, $columnIndex, 'TERR_' . $territoire->id . '_COMMUNES', $territoireCommunes);
        }

        $this->addNamedList($spreadsheet, $listsSheet, $columnIndex, 'COMMUNES_ALL_LABELS', $communes->map(fn ($commune) => $this->formatCommuneLabel($commune))->values()->all());
        $this->addNamedList($spreadsheet, $listsSheet, $columnIndex, 'COMMUNES_ALL_IDS', $communes->map(fn ($commune) => (string) $commune->id)->values()->all());

        foreach ($sites->groupBy('commune_id') as $communeId => $communeSites) {
            $this->addNamedList(
                $spreadsheet,
                $listsSheet,
                $columnIndex,
                'COMM_' . (int) $communeId . '_SITES',
                $communeSites->sortBy('nom')->map(fn ($site) => $this->formatSiteLabel($site))->values()->all()
            );
        }

        $this->addNamedList($spreadsheet, $listsSheet, $columnIndex, 'SITES_ALL_LABELS', $sites->map(fn ($site) => $this->formatSiteLabel($site))->values()->all());
        $this->addNamedList($spreadsheet, $listsSheet, $columnIndex, 'SITES_ALL_IDS', $sites->map(fn ($site) => (string) $site->id)->values()->all());

        foreach ($clusterIndicators as $clusterId => $payload) {
            $indicatorValues = $payload['indicators']
                ->map(fn ($indicator) => $this->formatIndicatorLabel($indicator))
                ->values()
                ->all();

            $activityValues = $payload['indicators']
                ->flatMap(fn ($indicator) => $indicator->activities)
                ->unique('id')
                ->sortBy('code')
                ->map(fn ($activity) => $this->formatActivityLabel($activity))
                ->values()
                ->all();

            $this->addNamedList($spreadsheet, $listsSheet, $columnIndex, 'CLUSTER_' . $clusterId . '_INDICATORS', $indicatorValues);
            $this->addNamedList($spreadsheet, $listsSheet, $columnIndex, 'CLUSTER_' . $clusterId . '_ACTIVITIES', $activityValues);

            foreach ($payload['indicators'] as $indicator) {
                $this->addNamedList(
                    $spreadsheet,
                    $listsSheet,
                    $columnIndex,
                    'IND_' . $indicator->id . '_ACTIVITIES',
                    $indicator->activities->map(fn ($activity) => $this->formatActivityLabel($activity))->values()->all()
                );

                foreach ($indicator->activities as $activity) {
                    $this->addNamedList(
                        $spreadsheet,
                        $listsSheet,
                        $columnIndex,
                        'ACT_' . $activity->id . '_SUBACTIVITIES',
                        $activity->subActivities->map(fn ($subActivity) => $this->formatSubActivityLabel($subActivity))->values()->all()
                    );
                }
            }
        }

        $allIndicators = collect($clusterIndicators)->flatMap(fn ($payload) => $payload['indicators'])->unique('id')->values();
        $allActivities = $allIndicators->flatMap(fn ($indicator) => $indicator->activities)->unique('id')->values();
        $allSubActivities = $allActivities->flatMap(fn ($activity) => $activity->subActivities)->unique('id')->values();

        $this->addNamedList($spreadsheet, $listsSheet, $columnIndex, 'INDICATORS_ALL_LABELS', $allIndicators->map(fn ($indicator) => $this->formatIndicatorLabel($indicator))->all());
        $this->addNamedList($spreadsheet, $listsSheet, $columnIndex, 'INDICATORS_ALL_IDS', $allIndicators->map(fn ($indicator) => (string) $indicator->id)->all());
        $this->addNamedList($spreadsheet, $listsSheet, $columnIndex, 'ACTIVITIES_ALL_LABELS', $allActivities->map(fn ($activity) => $this->formatActivityLabel($activity))->all());
        $this->addNamedList($spreadsheet, $listsSheet, $columnIndex, 'ACTIVITIES_ALL_IDS', $allActivities->map(fn ($activity) => (string) $activity->id)->all());
        $this->addNamedList($spreadsheet, $listsSheet, $columnIndex, 'SUBACTIVITIES_ALL_LABELS', $allSubActivities->map(fn ($subActivity) => $this->formatSubActivityLabel($subActivity))->all());
        $this->addNamedList($spreadsheet, $listsSheet, $columnIndex, 'SUBACTIVITIES_ALL_IDS', $allSubActivities->map(fn ($subActivity) => (string) $subActivity->id)->all());

        $referenceSheet = $spreadsheet->createSheet();
        $referenceSheet->setTitle('REFERENCES');
        $referenceSheet->setCellValue('A1', 'TYPE');
        $referenceSheet->setCellValue('B1', 'ID');
        $referenceSheet->setCellValue('C1', 'LIBELLE');
        $referenceSheet->getStyle('A1:C1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $referenceOrganisationIds = $organisations->pluck('id');
        $referenceProjects = Project::query()
            ->whereIn('organisation_id', $referenceOrganisationIds)
            ->orderBy('name')
            ->get(['id', 'organisation_id', 'cluster_id', 'name', 'code']);

        $referenceIndicators = ProgramIndicator::query()
            ->with(['activities.subActivities'])
            ->orderBy('code')
            ->get();

        $refRow = 2;
        $pushRef = function (string $type, int $id, string $label) use ($referenceSheet, &$refRow): void {
            $referenceSheet->setCellValue('A' . $refRow, $type);
            $referenceSheet->setCellValue('B' . $refRow, $id);
            $referenceSheet->setCellValue('C' . $refRow, $label);
            $refRow++;
        };

        foreach ($organisations as $organisation) {
            $pushRef('organisation', (int) $organisation->id, trim(($organisation->code ? $organisation->code . ' - ' : '') . $organisation->name));
        }
        foreach ($clusters as $cluster) {
            $pushRef('cluster', (int) $cluster->id, trim(($cluster->code ? $cluster->code . ' - ' : '') . $cluster->name));
        }
        foreach ($referenceProjects as $project) {
            $pushRef('projet', (int) $project->id, trim(($project->code ? $project->code . ' - ' : '') . $project->name));
        }
        foreach ($provinces as $province) {
            $pushRef('province', (int) $province->id, $province->name);
        }
        foreach ($territoires as $territoire) {
            $pushRef('territoire', (int) $territoire->id, $territoire->name);
        }
        foreach ($communes as $commune) {
            $pushRef('zone_sante', (int) $commune->id, $commune->name);
        }
        foreach ($sites as $site) {
            $pushRef('site', (int) $site->id, trim(($site->code_site ? $site->code_site . ' - ' : '') . $site->nom));
        }
        foreach ($referenceIndicators as $indicator) {
            $pushRef('indicateur', (int) $indicator->id, trim(($indicator->code ? $indicator->code . ' - ' : '') . $indicator->label));
            foreach ($indicator->activities as $activity) {
                $pushRef('activite', (int) $activity->id, trim(($activity->code ? $activity->code . ' - ' : '') . $activity->label));
                foreach ($activity->subActivities as $subActivity) {
                    $pushRef('sous_activite', (int) $subActivity->id, trim(($subActivity->code ? $subActivity->code . ' - ' : '') . $subActivity->label));
                }
            }
        }

        $referenceSheet->getColumnDimension('A')->setWidth(20);
        $referenceSheet->getColumnDimension('B')->setWidth(12);
        $referenceSheet->getColumnDimension('C')->setWidth(60);
        $referenceSheet->freezePane('A2');

        for ($row = self::FIRST_DATA_ROW; $row <= self::LAST_DATA_ROW; $row++) {
            $sheet->setCellValue("V{$row}", "=SUM(M{$row}:R{$row})");
            $sheet->setCellValue("AA{$row}", '=IFERROR(INDEX(ORGS_ALL_IDS,MATCH(A' . $row . ',ORGS_ALL_LABELS,0)),"")');
            $sheet->setCellValue("AB{$row}", '=IFERROR(INDEX(CLUSTERS_ALL_IDS,MATCH(B' . $row . ',CLUSTERS_ALL_LABELS,0)),"")');
            $sheet->setCellValue("AC{$row}", '=IFERROR(INDEX(PROJECTS_ALL_IDS,MATCH(C' . $row . ',PROJECTS_ALL_LABELS,0)),"")');
            $sheet->setCellValue("AD{$row}", '=IFERROR(INDEX(PROVINCES_ALL_IDS,MATCH(E' . $row . ',PROVINCES_ALL_LABELS,0)),"")');
            $sheet->setCellValue("AE{$row}", '=IFERROR(INDEX(TERRITOIRES_ALL_IDS,MATCH(F' . $row . ',TERRITOIRES_ALL_LABELS,0)),"")');
            $sheet->setCellValue("AF{$row}", '=IFERROR(INDEX(COMMUNES_ALL_IDS,MATCH(G' . $row . ',COMMUNES_ALL_LABELS,0)),"")');
            $sheet->setCellValue("AG{$row}", '=IFERROR(INDEX(INDICATORS_ALL_IDS,MATCH(I' . $row . ',INDICATORS_ALL_LABELS,0)),"")');
            $sheet->setCellValue("AH{$row}", '=IFERROR(INDEX(ACTIVITIES_ALL_IDS,MATCH(J' . $row . ',ACTIVITIES_ALL_LABELS,0)),"")');
            $sheet->setCellValue("AI{$row}", '=IFERROR(INDEX(SUBACTIVITIES_ALL_IDS,MATCH(K' . $row . ',SUBACTIVITIES_ALL_LABELS,0)),"")');
            $sheet->setCellValue("AJ{$row}", '=IFERROR(INDEX(SITES_ALL_IDS,MATCH(H' . $row . ',SITES_ALL_LABELS,0)),"")');

            if ($isSuperAdminMode) {
                $this->applyListValidation($sheet, "A{$row}", '=ORGS_ALL_LABELS', false, 'Organisation invalide', 'Choisissez une organisation dans la liste.');
            } elseif ($defaultOrganisation) {
                $sheet->setCellValue("A{$row}", $this->formatOrganisationLabel($defaultOrganisation));
            }

            $this->applyListValidation($sheet, "B{$row}", '=INDIRECT("ORG_"&AA' . $row . '&"_CLUSTERS")', false, 'Cluster invalide', 'Choisissez un cluster correspondant à l\'organisation.');
            $this->applyListValidation($sheet, "C{$row}", '=INDIRECT("CLUSTER_"&AB' . $row . '&"_PROJECTS")', false, 'Projet invalide', 'Choisissez un projet correspondant au cluster.');
            $this->applyListValidation($sheet, "E{$row}", '=PROVINCES_ALL_LABELS', false, 'Province invalide', 'Choisissez une province dans la liste.');
            $this->applyListValidation($sheet, "F{$row}", '=INDIRECT("PROV_"&AD' . $row . '&"_TERRITOIRES")', false, 'Territoire invalide', 'Choisissez un territoire correspondant à la province.');
            $this->applyListValidation($sheet, "G{$row}", '=INDIRECT("TERR_"&AE' . $row . '&"_COMMUNES")', false, 'Zone de santé invalide', 'Choisissez une zone de santé correspondant au territoire.');
            $this->applyListValidation($sheet, "H{$row}", '=INDIRECT("COMM_"&AF' . $row . '&"_SITES")', true, 'Site invalide', 'Choisissez un site correspondant à la zone de santé.');
            $this->applyListValidation($sheet, "I{$row}", '=INDIRECT("CLUSTER_"&AB' . $row . '&"_INDICATORS")', true, 'Indicateur invalide', 'Choisissez un indicateur correspondant au cluster.');
            $this->applyListValidation($sheet, "J{$row}", '=INDIRECT("CLUSTER_"&AB' . $row . '&"_ACTIVITIES")', false, 'Activité invalide', 'Choisissez une activité correspondant au cluster.');
            $this->applyListValidation($sheet, "K{$row}", '=INDIRECT("ACT_"&AH' . $row . '&"_SUBACTIVITIES")', true, 'Sous-activité invalide', 'Choisissez une sous-activité correspondant à l\'activité.');
            $this->applyListValidation($sheet, "L{$row}", '=STATUS_ALL', true, 'Statut invalide', 'Choisissez un statut bénéficiaire dans la liste.');
        }

        foreach (range('AA', 'AJ') as $helperColumn) {
            $sheet->getColumnDimension($helperColumn)->setVisible(false);
        }

        $this->addCascadeInvalidationHighlight($sheet, 'C6:C' . self::LAST_DATA_ROW, 'AND($C6<>"",COUNTIF(INDIRECT("CLUSTER_"&$AB6&"_PROJECTS"),$C6)=0)');
        $this->addCascadeInvalidationHighlight($sheet, 'F6:F' . self::LAST_DATA_ROW, 'AND($F6<>"",COUNTIF(INDIRECT("PROV_"&$AD6&"_TERRITOIRES"),$F6)=0)');
        $this->addCascadeInvalidationHighlight($sheet, 'G6:G' . self::LAST_DATA_ROW, 'AND($G6<>"",COUNTIF(INDIRECT("TERR_"&$AE6&"_COMMUNES"),$G6)=0)');
        $this->addCascadeInvalidationHighlight($sheet, 'H6:H' . self::LAST_DATA_ROW, 'AND($H6<>"",COUNTIF(INDIRECT("COMM_"&$AF6&"_SITES"),$H6)=0)');
        $this->addCascadeInvalidationHighlight($sheet, 'H6:H' . self::LAST_DATA_ROW, 'AND($H6="",LEFT($B6,4)="CCCM")');
        $this->addCascadeInvalidationHighlight($sheet, 'I6:I' . self::LAST_DATA_ROW, 'AND($I6<>"",COUNTIF(INDIRECT("CLUSTER_"&$AB6&"_INDICATORS"),$I6)=0)');
        $this->addCascadeInvalidationHighlight($sheet, 'J6:J' . self::LAST_DATA_ROW, 'AND($J6<>"",COUNTIF(INDIRECT("CLUSTER_"&$AB6&"_ACTIVITIES"),$J6)=0)');
        $this->addCascadeInvalidationHighlight($sheet, 'K6:K' . self::LAST_DATA_ROW, 'AND($K6<>"",COUNTIF(INDIRECT("ACT_"&$AH6&"_SUBACTIVITIES"),$K6)=0)');

        // Protect only support sheets (lists, references, instructions) — IMPORTATION is left unprotected so users can write freely.
        foreach ([$listsSheet, $referenceSheet, $spreadsheet->getSheetByName('MODE_EMPLOI')] as $protectedSheet) {
            if ($protectedSheet instanceof Worksheet) {
                $protection = $protectedSheet->getProtection();
                $protection->setPassword('DMS_IMPORT_2026');
                $protection->setSheet(true);
                $protection->setSort(false);
                $protection->setInsertRows(false);
                $protection->setFormatCells(false);
                $protection->setSelectLockedCells(false);
                $protection->setSelectUnlockedCells(false);
            }
        }

        $sheet->freezePane('A6');
        $spreadsheet->setActiveSheetIndex($spreadsheet->getIndex($sheet));

        $filename = 'template_import_activites_projets_' . now()->format('Y-m-d') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'fichier_excel' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $user = auth()->user();
        [$organisations, $provinces, $territoires, $communes, $sites, $clusters, $projects, $clusterIndicators] = $this->loadTemplateContext($user);

        $projectsById = $projects->keyBy('id');
        $sitesById = $sites->keyBy('id');
        $provincesById = $provinces->keyBy('id');
        $territoiresById = $territoires->keyBy('id');
        $communesById = $communes->keyBy('id');
        $clustersById = $clusters->keyBy('id');

        $allIndicators = collect($clusterIndicators)->flatMap(fn ($payload) => $payload['indicators'])->unique('id')->values();
        $allActivities = $allIndicators->flatMap(fn ($indicator) => $indicator->activities)->unique('id')->values();
        $allSubActivities = $allActivities->flatMap(fn ($activity) => $activity->subActivities)->unique('id')->values();

        $organisationLabelMap = $this->buildLabelToIdMap($organisations, fn ($organisation) => $this->formatOrganisationLabel($organisation));
        $clusterLabelMap = $this->buildLabelToIdMap($clusters, fn ($cluster) => $this->formatClusterLabel($cluster));
        $projectLabelMap = $this->buildLabelToIdMap($projects, fn ($project) => $this->formatProjectLabel($project));
        $provinceLabelMap = $this->buildLabelToIdMap($provinces, fn ($province) => $this->formatProvinceLabel($province));
        $territoireLabelMap = $this->buildLabelToIdMap($territoires, fn ($territoire) => $this->formatTerritoireLabel($territoire));
        $communeLabelMap = $this->buildLabelToIdMap($communes, fn ($commune) => $this->formatCommuneLabel($commune));
        $siteLabelMap = $this->buildLabelToIdMap($sites, fn ($site) => $this->formatSiteLabel($site));
        $indicatorLabelMap = $this->buildLabelToIdMap($allIndicators, fn ($indicator) => $this->formatIndicatorLabel($indicator));
        $activityLabelMap = $this->buildLabelToIdMap($allActivities, fn ($activity) => $this->formatActivityLabel($activity));
        $subActivityLabelMap = $this->buildLabelToIdMap($allSubActivities, fn ($subActivity) => $this->formatSubActivityLabel($subActivity));

        $allowedIndicatorIdsByCluster = [];
        $allowedActivityIdsByCluster = [];
        $allowedActivityIdsByIndicator = [];
        $allowedSubActivityIdsByActivity = [];
        $indicatorIdByActivity = [];

        foreach ($clusterIndicators as $clusterId => $payload) {
            $allowedIndicatorIdsByCluster[$clusterId] = $payload['indicators']->pluck('id')->map(fn ($id) => (int) $id)->all();
            $allowedActivityIdsByCluster[$clusterId] = [];
            foreach ($payload['indicators'] as $indicator) {
                $allowedActivityIdsByIndicator[(int) $indicator->id] = $indicator->activities->pluck('id')->map(fn ($id) => (int) $id)->all();
                foreach ($allowedActivityIdsByIndicator[(int) $indicator->id] as $activityId) {
                    $allowedActivityIdsByCluster[$clusterId][] = $activityId;
                    $indicatorIdByActivity[$activityId] = (int) $indicator->id;
                }
                foreach ($indicator->activities as $activity) {
                    $allowedSubActivityIdsByActivity[(int) $activity->id] = $activity->subActivities->pluck('id')->map(fn ($id) => (int) $id)->all();
                }
            }

            $allowedActivityIdsByCluster[$clusterId] = array_values(array_unique($allowedActivityIdsByCluster[$clusterId]));
        }

        $reader = new XlsxReader();
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($request->file('fichier_excel')->getRealPath());
        $sheet = $spreadsheet->getSheetByName('IMPORTATION') ?? $spreadsheet->getActiveSheet();
        $highestRow = min($sheet->getHighestDataRow(), self::LAST_DATA_ROW);

        $successes = 0;
        $errors = [];
        $allowedStatuses = ['pdi', 'retourne', 'refugie', 'communaute_hote', 'autre'];

        for ($row = self::FIRST_DATA_ROW; $row <= $highestRow; $row++) {
            $values = [];
            foreach (range('A', 'U') as $column) {
                $values[$column] = trim((string) $sheet->getCell($column . $row)->getFormattedValue());
            }

            if (collect($values)->every(fn ($value) => $value === '')) {
                continue;
            }

            // Ignore rows that only keep organisation prefilled while no actual import data is provided.
            $nonOrganisationValues = $values;
            unset($nonOrganisationValues['A']);
            if (collect($nonOrganisationValues)->every(fn ($value) => $value === '')) {
                continue;
            }

            // Ignore placeholder rows where required cascade fields are not started.
            if (
                $values['B'] === ''
                && $values['C'] === ''
                && $values['D'] === ''
                && $values['E'] === ''
                && $values['F'] === ''
                && $values['G'] === ''
                && $values['J'] === ''
            ) {
                continue;
            }

            $orgId = $user->isSuperAdmin()
                ? $this->resolveEntityId($values['A'], $organisationLabelMap)
                : (int) $user->organisation_id;
            $clusterId = $this->resolveEntityId($values['B'], $clusterLabelMap);
            $projectId = $this->resolveEntityId($values['C'], $projectLabelMap);
            $provinceId = $this->resolveEntityId($values['E'], $provinceLabelMap);
            $territoireId = $this->resolveEntityId($values['F'], $territoireLabelMap);
            $communeId = $this->resolveEntityId($values['G'], $communeLabelMap);
            $siteId = $this->resolveEntityId($values['H'], $siteLabelMap);
            $indicatorId = $this->resolveEntityId($values['I'], $indicatorLabelMap);
            $activityId = $this->resolveEntityId($values['J'], $activityLabelMap);
            $subActivityId = $this->resolveEntityId($values['K'], $subActivityLabelMap);

            if (!$orgId || !$clusterId || !$projectId || !$provinceId || !$territoireId || !$communeId || !$activityId || $values['D'] === '') {
                $errors[] = "Ligne {$row}: organisation, cluster, projet, date, province, territoire, zone de santé et activité sont obligatoires.";
                continue;
            }

            if (!$organisations->firstWhere('id', $orgId)) {
                $errors[] = "Ligne {$row}: organisation invalide ou hors périmètre.";
                continue;
            }

            $cluster = $clustersById->get($clusterId);
            if (!$cluster || !in_array($orgId, $cluster->organisations->pluck('id')->all(), true)) {
                $errors[] = "Ligne {$row}: cluster invalide pour l'organisation sélectionnée.";
                continue;
            }

            $clusterCode = mb_strtolower(trim((string) ($cluster->code ?? '')));
            $clusterName = mb_strtolower(trim((string) ($cluster->name ?? '')));
            $isCccmCluster = $clusterCode === 'cccm' || str_contains($clusterName, 'cccm');

            $project = $projectsById->get($projectId);
            if (!$project || (int) $project->organisation_id !== $orgId || (int) $project->cluster_id !== $clusterId) {
                $errors[] = "Ligne {$row}: projet invalide pour l'organisation et le cluster sélectionnés.";
                continue;
            }

            $province = $provincesById->get($provinceId);
            $territoire = $territoiresById->get($territoireId);
            $commune = $communesById->get($communeId);
            if (!$province || !$territoire || !$commune || (int) $territoire->province_id !== $provinceId || (int) $commune->territoire_id !== $territoireId) {
                $errors[] = "Ligne {$row}: cascade géographique invalide.";
                continue;
            }

            if (!in_array($activityId, $allowedActivityIdsByCluster[$clusterId] ?? [], true)) {
                $errors[] = "Ligne {$row}: activité invalide pour le cluster sélectionné.";
                continue;
            }

            $derivedIndicatorId = $indicatorIdByActivity[$activityId] ?? null;
            if (!$derivedIndicatorId) {
                $errors[] = "Ligne {$row}: impossible de déterminer l'indicateur à partir de l'activité.";
                continue;
            }

            if ($indicatorId && !in_array($indicatorId, $allowedIndicatorIdsByCluster[$clusterId] ?? [], true)) {
                $errors[] = "Ligne {$row}: indicateur invalide pour le cluster sélectionné.";
                continue;
            }

            if ($indicatorId && $indicatorId !== $derivedIndicatorId) {
                // Keep import resilient to stale indicator dropdown values by trusting the selected activity.
                $indicatorId = $derivedIndicatorId;
            }

            if (!$indicatorId) {
                $indicatorId = $derivedIndicatorId;
            }

            if ($subActivityId && !in_array($subActivityId, $allowedSubActivityIdsByActivity[$activityId] ?? [], true)) {
                $errors[] = "Ligne {$row}: sous-activité invalide pour l'activité sélectionnée.";
                continue;
            }

            $site = null;
            if ($siteId) {
                $site = $sitesById->get($siteId);
                if (!$site || (int) $site->commune_id !== $communeId) {
                    $errors[] = "Ligne {$row}: site invalide pour la zone de santé sélectionnée.";
                    continue;
                }
            } elseif ($isCccmCluster) {
                $errors[] = "Ligne {$row}: le site est obligatoire pour le cluster CCCM.";
                continue;
            }

            $status = strtolower(trim($values['L']));
            if ($status !== '' && !in_array($status, $allowedStatuses, true)) {
                $errors[] = "Ligne {$row}: statut bénéficiaire invalide.";
                continue;
            }

            $girls0To17 = $this->parseInteger($values['M']);
            $girls18To59 = $this->parseInteger($values['N']);
            $girls60Plus = $this->parseInteger($values['O']);
            $boys0To17 = $this->parseInteger($values['P']);
            $boys18To59 = $this->parseInteger($values['Q']);
            $boys60Plus = $this->parseInteger($values['R']);
            $personsWithDisabilities = $this->parseInteger($values['S']);
            $activityCost = $this->parseDecimal($values['T']);
            $reportingDate = $this->parseDate($values['D']);

            if ($reportingDate === null) {
                $errors[] = "Ligne {$row}: date de rapportage invalide, utilisez le format AAAA-MM-JJ.";
                continue;
            }

            if (in_array(null, [$girls0To17, $girls18To59, $girls60Plus, $boys0To17, $boys18To59, $boys60Plus, $personsWithDisabilities], true)) {
                $errors[] = "Ligne {$row}: les colonnes bénéficiaires et personnes handicapées doivent contenir des nombres entiers valides.";
                continue;
            }

            if ($activityCost === false) {
                $errors[] = "Ligne {$row}: coût d'activité invalide.";
                continue;
            }

            $subActivity = $subActivityId ? ProgramSubActivity::query()->find($subActivityId, ['id', 'label']) : null;
            $activityLabel = null;
            if (!$subActivity) {
                $activityLabel = optional(collect($clusterIndicators[$clusterId]['indicators'] ?? [])->flatMap->activities->firstWhere('id', $activityId))->label;
            }

            $beneficiariesByStatus = [];
            if ($status !== '') {
                $beneficiariesByStatus[$status] = [
                    'girls_0_17' => $girls0To17,
                    'girls_18_59' => $girls18To59,
                    'girls_60_plus' => $girls60Plus,
                    'boys_0_17' => $boys0To17,
                    'boys_18_59' => $boys18To59,
                    'boys_60_plus' => $boys60Plus,
                ];
            }

            ProjectActivity::create([
                'project_id' => $projectId,
                'activity_name' => trim((string) ($subActivity?->label ?? $activityLabel ?? 'Activite importee')),
                'program_indicator_id' => $indicatorId,
                'program_activity_id' => $activityId,
                'program_sub_activity_id' => $subActivityId,
                'activity_cost' => $activityCost,
                'site_id' => $site?->id,
                'province_id' => $provinceId,
                'territoire_id' => $territoireId,
                'commune_id' => $communeId,
                'statut_beneficiaire' => $status !== '' ? $status : null,
                'beneficiaries_by_status' => $beneficiariesByStatus ?: null,
                'girls_0_17' => $girls0To17,
                'girls_18_59' => $girls18To59,
                'girls_60_plus' => $girls60Plus,
                'boys_0_17' => $boys0To17,
                'boys_18_59' => $boys18To59,
                'boys_60_plus' => $boys60Plus,
                'persons_with_disabilities' => $personsWithDisabilities,
                'comment' => $values['U'] !== '' ? $values['U'] : null,
                'reporting_date' => $reportingDate,
            ]);

            $successes++;
        }

        $message = $successes . ' activité(s) importée(s) avec succès.';

        if (!empty($errors)) {
            $message .= ' ' . count($errors) . ' ligne(s) contiennent des erreurs.';
        }

        return redirect()->route('project-activities-import.index')
            ->with('success', $message)
            ->with('import_errors', $errors);
    }

    private function loadTemplateContext($user, ?int $overrideOrganisationId = null): array
    {
        $organisationQuery = Organisation::query()
            ->orderBy('name')
            ->with([
                'clusters' => fn ($query) => $query->where('is_active', true)->orderBy('name'),
                'projects' => fn ($query) => $query->whereNotNull('cluster_id')->orderBy('name'),
            ]);

        if ($overrideOrganisationId) {
            // Superadmin picked a specific organisation — scope to it.
            $organisationQuery->where('id', $overrideOrganisationId);
        } elseif (!$user->isSuperAdmin()) {
            $organisationQuery->where('id', $user->organisation_id);
        }

        $organisations = $organisationQuery->get(['id', 'name', 'code']);
        $organisationIds = $organisations->pluck('id');

        $clusters = Cluster::query()
            ->whereHas('organisations', fn ($query) => $query->whereIn('organisations.id', $organisationIds))
            ->where('is_active', true)
            ->with(['organisations:id'])
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $projects = Project::query()
            ->whereIn('organisation_id', $organisationIds)
            ->whereNotNull('cluster_id')
            ->orderBy('name')
            ->get(['id', 'organisation_id', 'cluster_id', 'name', 'code']);

        $provinces = Province::orderBy('name')->get(['id', 'name']);
        $territoires = Territoire::orderBy('name')->get(['id', 'province_id', 'name']);
        $communes = Commune::orderBy('name')->get(['id', 'province_id', 'territoire_id', 'name', 'pcode']);
        $sites = Site::query()
            ->orderBy('nom')
            ->get(['id', 'organisation_id', 'commune_id', 'nom', 'code_site', 'zone_sante', 'code_zone_sante']);

        // Fallback: many legacy sites are not linked by commune_id yet; map by zone_sante label.
        $communesByPcode = $communes
            ->filter(fn ($commune) => !empty($commune->pcode))
            ->keyBy(fn ($commune) => mb_strtolower(trim((string) $commune->pcode)));

        $communesByNormalizedName = $communes
            ->groupBy(fn ($commune) => $this->normalizeLabel($commune->name) ?? '')
            ->filter(fn ($group, $key) => $key !== '');

        $sites = $sites
            ->map(function ($site) use ($communesByPcode, $communesByNormalizedName) {
                if (!$site->commune_id) {
                    $pcode = mb_strtolower(trim((string) ($site->code_zone_sante ?? '')));
                    if ($pcode !== '' && $communesByPcode->has($pcode)) {
                        $matchedCommune = $communesByPcode->get($pcode);
                        $site->commune_id = (int) $matchedCommune->id;
                    }
                }

                if (!$site->commune_id) {
                    $key = $this->normalizeLabel($site->zone_sante ?? null);
                    if ($key && $communesByNormalizedName->has($key)) {
                        $matchedCommune = $communesByNormalizedName->get($key)->first();
                        if ($matchedCommune) {
                            $site->commune_id = (int) $matchedCommune->id;
                        }
                    }
                }

                return $site;
            })
            ->filter(fn ($site) => !empty($site->commune_id))
            ->values();

        $clusterIds = $clusters->pluck('id')->all();
        $clustersWithHierarchy = Cluster::query()
            ->whereIn('id', $clusterIds)
            ->with(['sectorObjectives.strategicObjectives.indicators.activities.subActivities'])
            ->get();

        $globalIndicators = ProgramIndicator::query()
            ->with(['activities.subActivities'])
            ->orderBy('code')
            ->get()
            ->unique('id')
            ->values();

        $clusterIndicators = [];
        foreach ($clustersWithHierarchy as $cluster) {
            $indicators = $cluster->sectorObjectives
                ->flatMap(fn ($objective) => $objective->strategicObjectives)
                ->flatMap(fn ($objective) => $objective->indicators)
                ->unique('id')
                ->sortBy('code')
                ->values();

            // Fallback: if cluster linkage is not populated yet, expose global program lists.
            if ($indicators->isEmpty()) {
                $indicators = $globalIndicators;
            }

            $clusterIndicators[$cluster->id] = [
                'indicators' => $indicators,
            ];
        }

        return [$organisations, $provinces, $territoires, $communes, $sites, $clusters, $projects, $clusterIndicators];
    }

    private function buildInstructionsSheet(Spreadsheet $spreadsheet, bool $isSuperAdmin): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('MODE_EMPLOI');
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'MODE D\'EMPLOI — IMPORT DES ACTIVITÉS PROJETS');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $instructions = [
            '1. Remplissez uniquement la feuille IMPORTATION à partir de la ligne 6.',
            '2. Respectez l’ordre des cascades: organisation → cluster → projet, puis province → territoire → zone de santé → site.',
            '3. Pour la programmation: choisissez cluster → indicateur → activité → sous-activité.',
            '4. La colonne statut_beneficiaire accepte une seule valeur par ligne. Si plusieurs statuts existent, dupliquez la ligne.',
            '5. La colonne site est optionnelle. Si renseignée, elle doit appartenir à la zone de santé sélectionnée.',
            '6. Exception: si le cluster choisi est CCCM, la colonne site devient obligatoire.',
            '7. Les colonnes grisées ou marquées auto sont calculées automatiquement.',
            $isSuperAdmin
                ? '8. En tant que super administrateur, vous devez sélectionner l’organisation et le cluster concernés pour chaque ligne.'
                : '8. En tant qu’organisation, la colonne organisation est pré-remplie et protégée.',
        ];

        foreach ($instructions as $index => $instruction) {
            $row = $index + 3;
            $sheet->mergeCells("A{$row}:G{$row}");
            $sheet->setCellValue("A{$row}", $instruction);
            $sheet->getStyle("A{$row}")->applyFromArray([
                'alignment' => ['wrapText' => true],
                'font' => ['size' => 10, 'color' => ['rgb' => '374151']],
            ]);
        }

        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setWidth(24);
        }
    }

    private function decorateImportSheet(Worksheet $sheet, array $headers): void
    {
        $sheet->mergeCells('A1:V1');
        $sheet->setCellValue('A1', 'IMPORTATION DES ACTIVITÉS RÉALISÉES — NE PAS MODIFIER LES LIGNES 1 À 4');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->mergeCells('A2:V2');
        $sheet->setCellValue('A2', 'Sélectionnez les valeurs uniquement via les listes déroulantes pour conserver les liens de cascade.');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '92400E']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'wrapText' => true],
        ]);

        foreach ($headers as $column => $info) {
            $headerCell = $column . '3';
            $commentCell = $column . '4';
            $isAuto = str_contains($info['label'], 'auto');

            $sheet->setCellValue($headerCell, $info['label']);
            $sheet->setCellValue($commentCell, $info['comment']);

            $sheet->getStyle($headerCell)->applyFromArray([
                'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => $isAuto ? '374151' : 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $isAuto ? 'D1FAE5' : '2563EB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
            ]);

            $sheet->getStyle($commentCell)->applyFromArray([
                'font' => ['italic' => true, 'size' => 8, 'color' => ['rgb' => '6B7280']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
                'alignment' => ['wrapText' => true],
            ]);
        }

        $widths = [
            'A' => 28, 'B' => 24, 'C' => 34, 'D' => 16, 'E' => 22, 'F' => 24, 'G' => 24, 'H' => 28,
            'I' => 34, 'J' => 34, 'K' => 34, 'L' => 20, 'M' => 12, 'N' => 12, 'O' => 12, 'P' => 12,
            'Q' => 12, 'R' => 12, 'S' => 18, 'T' => 16, 'U' => 28, 'V' => 18,
        ];

        foreach ($widths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension(2)->setRowHeight(30);
        $sheet->getRowDimension(3)->setRowHeight(30);
        $sheet->getRowDimension(4)->setRowHeight(32);
    }

    private function addNamedList(Spreadsheet $spreadsheet, Worksheet $sheet, int &$columnIndex, string $rangeName, array $values): void
    {
        $values = array_values(array_filter($values, fn ($value) => $value !== null));
        if (empty($values)) {
            $values = [''];
        }

        $column = Coordinate::stringFromColumnIndex($columnIndex++);
        foreach ($values as $index => $value) {
            $sheet->setCellValue($column . ($index + 1), (string) $value);
        }

        $spreadsheet->addNamedRange(new NamedRange(
            $rangeName,
            $sheet,
            '$' . $column . '$1:$' . $column . '$' . count($values)
        ));
    }

    private function applyListValidation(Worksheet $sheet, string $cell, string $formula, bool $allowBlank, string $errorTitle, string $errorMessage): void
    {
        $validation = $sheet->getCell($cell)->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank($allowBlank);
        // PhpSpreadsheet inverts this on write; true here keeps the arrow visible in Excel.
        $validation->setShowDropDown(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle($errorTitle);
        $validation->setError($errorMessage);
        $validation->setFormula1(ltrim(trim($formula), '='));
    }

    private function addCascadeInvalidationHighlight(Worksheet $sheet, string $range, string $formula): void
    {
        $conditional = new Conditional();
        $conditional->setConditionType(Conditional::CONDITION_EXPRESSION);
        $conditional->addCondition($formula);
        $conditional->getStyle()->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FEE2E2'],
            ],
            'font' => [
                'color' => ['rgb' => '991B1B'],
                'bold' => true,
            ],
        ]);

        $existing = $sheet->getStyle($range)->getConditionalStyles();
        $existing[] = $conditional;
        $sheet->getStyle($range)->setConditionalStyles($existing);
    }

    private function formatOrganisationLabel($organisation): string
    {
        return $this->formatWithCode($organisation->code ?? null, (string) $organisation->name);
    }

    private function formatClusterLabel($cluster): string
    {
        return $this->formatWithCode($cluster->code ?? null, (string) $cluster->name);
    }

    private function formatProjectLabel($project): string
    {
        return $this->formatWithCode($project->code ?? null, (string) $project->name);
    }

    private function formatProvinceLabel($province): string
    {
        return trim((string) $province->name);
    }

    private function formatTerritoireLabel($territoire): string
    {
        return trim((string) $territoire->name);
    }

    private function formatCommuneLabel($commune): string
    {
        return trim((string) $commune->name);
    }

    private function formatSiteLabel($site): string
    {
        return $this->formatWithCode($site->code_site ?? null, (string) $site->nom);
    }

    private function formatIndicatorLabel($indicator): string
    {
        return $this->formatWithCode($indicator->code ?? null, (string) $indicator->label);
    }

    private function formatActivityLabel($activity): string
    {
        return $this->formatWithCode($activity->code ?? null, (string) $activity->label);
    }

    private function formatSubActivityLabel($subActivity): string
    {
        return $this->formatWithCode($subActivity->code ?? null, (string) $subActivity->label);
    }

    private function formatWithCode(?string $code, string $label): string
    {
        $code = trim((string) $code);
        $label = trim($label);

        return $code !== '' ? ($code . ' - ' . $label) : $label;
    }

    private function resolveEntityId(?string $value, array $labelMap): ?int
    {
        $id = $this->extractEntityId($value);
        if ($id) {
            return $id;
        }

        $normalized = $this->normalizeLabel($value);
        if ($normalized === null) {
            return null;
        }

        return $labelMap[$normalized] ?? null;
    }

    private function buildLabelToIdMap($items, callable $formatter): array
    {
        $map = [];

        foreach ($items as $item) {
            $key = $this->normalizeLabel($formatter($item));
            if ($key === null) {
                continue;
            }

            $map[$key] = (int) $item->id;
        }

        return $map;
    }

    private function normalizeLabel(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\s+/', ' ', trim($value));
        if ($normalized === null || $normalized === '') {
            return null;
        }

        return mb_strtolower($normalized);
    }

    private function extractEntityId(?string $value): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d+$/', $value)) {
            return (int) $value;
        }

        if (preg_match('/^\[(\d+)\]/', $value, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function parseInteger(?string $value): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 0;
        }

        if (!preg_match('/^-?\d+$/', $value)) {
            return null;
        }

        return (int) $value;
    }

    private function parseDecimal(?string $value): float|false|null
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $normalized = str_replace([' ', ','], ['', '.'], $value);
        if (!is_numeric($normalized)) {
            return false;
        }

        return (float) $normalized;
    }

    private function parseDate(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        // Support Excel serial date values (e.g. 46082).
        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return $value;
    }
}