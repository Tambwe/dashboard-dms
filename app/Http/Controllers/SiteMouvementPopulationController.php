<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\SiteMouvementPopulation;
use App\Models\CategorieMouvement;
use App\Models\RaisonMouvement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\NamedRange;

class SiteMouvementPopulationController extends Controller
{
    /**
     * Récupère l'historique des mouvements d'un site
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $siteId = $request->input('site_id');
        $typeMouvement = $request->input('type_mouvement');
        $statut = $request->input('statut');
        $periode = $request->input('periode');
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');
        
        // Résoudre les IDs de sites accessibles pour l'utilisateur
        $allowedSiteIds = $this->getAccessibleSiteIdsForUser($user);

        $query = SiteMouvementPopulation::with(['site', 'createdBy', 'validatedBy', 'raisonMouvement']);
        
        if ($allowedSiteIds !== null) {
            $query->whereIn('site_id', $allowedSiteIds);
        }
        
        // Filtres
        if ($siteId) {
            $query->where('site_id', $siteId);
        }
        
        if ($typeMouvement) {
            $query->where('type_mouvement', $typeMouvement);
        }
        
        if ($statut) {
            $query->where('statut', $statut);
        }
        
        // Filtre période par mois/année (format: YYYY-MM)
        if ($periode && preg_match('/^\d{4}-\d{2}$/', $periode)) {
            [$year, $month] = explode('-', $periode);
            $query->whereYear('date_mouvement', (int) $year)
                ->whereMonth('date_mouvement', (int) $month);
        } elseif ($dateDebut && $dateFin) {
            $query->whereBetween('date_mouvement', [$dateDebut, $dateFin]);
        }
        
        $mouvements = $query->orderBy('date_mouvement', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);
        
        if ($request->expectsJson()) {
            return response()->json($mouvements);
        }
        
        // Charger les sites accessibles pour le filtre
        $sitesQuery = Site::query();
        if ($allowedSiteIds !== null) {
            $sitesQuery->whereIn('id', $allowedSiteIds);
        }
        $sites = $sitesQuery->orderBy('nom')->get(['id', 'nom', 'code_site']);
        
        return view('admin.mouvements.index', compact('mouvements', 'sites'));
    }

    /**
     * Affiche le formulaire de création
     */
    public function create()
    {
        $user = auth()->user();
        
        // Récupérer les sites accessibles
        $sites = Site::query();
        $allowedSiteIds = $this->getAccessibleSiteIdsForUser($user);
        if ($allowedSiteIds !== null) {
            $sites->whereIn('id', $allowedSiteIds);
        }
        $sites = $sites->orderBy('nom')->get();
        
        // Récupérer les raisons de mouvement
        $raisonsEntree = RaisonMouvement::whereHas('categorieMouvement', function($q) {
            $q->where('name', 'LIKE', '%nouvelle%entree%');
        })->get();
        
        $raisonsSortie = RaisonMouvement::whereHas('categorieMouvement', function($q) {
            $q->where('name', 'LIKE', '%sortie%');
        })->get();
        
        return view('admin.mouvements.create', compact(
            'sites',
            'raisonsEntree',
            'raisonsSortie'
        ));
    }

    /**
     * Enregistre un nouveau mouvement de population
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'date_mouvement' => 'required|date',
            'type_mouvement' => 'required|in:arrivee,depart,ajustement,recensement',
            'raison_mouvement_id' => 'nullable|exists:raison_mouvements,id',
            'periode' => 'nullable|string|max:255',
            'menages' => 'required|integer',
            'individus' => 'required|integer',
            'f_0_5' => 'required|integer|min:0',
            'f_6_17' => 'required|integer|min:0',
            'f_18_59' => 'required|integer|min:0',
            'f_60_plus' => 'required|integer|min:0',
            'h_0_5' => 'required|integer|min:0',
            'h_6_17' => 'required|integer|min:0',
            'h_18_59' => 'required|integer|min:0',
            'h_60_plus' => 'required|integer|min:0',
            'raison' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'source' => 'nullable|string|max:255',
            'round' => 'nullable|string|max:255',
        ]);

        // Vérifier l'accès au site ciblé
        $allowedSiteIds = $this->getAccessibleSiteIdsForUser($user);
        if ($allowedSiteIds !== null && !$allowedSiteIds->contains((int) $validated['site_id'])) {
            return back()->withInput()
                ->withErrors(['error' => 'Vous n\'avez pas accès à ce site.']);
        }
        
        DB::beginTransaction();
        
        try {
            // Pour les départs, convertir en valeurs négatives
            if ($validated['type_mouvement'] === 'depart') {
                $validated['menages'] = -abs($validated['menages']);
                $validated['individus'] = -abs($validated['individus']);
                $validated['f_0_5'] = -abs($validated['f_0_5']);
                $validated['f_6_17'] = -abs($validated['f_6_17']);
                $validated['f_18_59'] = -abs($validated['f_18_59']);
                $validated['f_60_plus'] = -abs($validated['f_60_plus']);
                $validated['h_0_5'] = -abs($validated['h_0_5']);
                $validated['h_6_17'] = -abs($validated['h_6_17']);
                $validated['h_18_59'] = -abs($validated['h_18_59']);
                $validated['h_60_plus'] = -abs($validated['h_60_plus']);
            }
            
            // Créer le mouvement avec statut en_attente
            $mouvement = SiteMouvementPopulation::create([
                ...$validated,
                'created_by' => $user->id,
                'statut' => 'en_attente',
            ]);
            
            // NOTE: Les populations du site ne sont PAS mises à jour ici
            // Elles seront mises à jour uniquement lors de la validation par le super admin
            
            DB::commit();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Mouvement enregistré et en attente de validation',
                    'mouvement' => $mouvement->load(['site', 'createdBy']),
                ], 201);
            }
            
            return redirect()->route('admin.mouvements.index')
                ->with('success', 'Mouvement de population enregistré et en attente de validation par le super admin!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'enregistrement du mouvement',
                    'error' => $e->getMessage(),
                ], 500);
            }
            
            return back()->withInput()
                ->withErrors(['error' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()]);
        }
    }

    /**
     * Récupère les détails d'un mouvement
     */
    public function show(string $id)
    {
        $mouvement = SiteMouvementPopulation::with(['site', 'createdBy'])->findOrFail($id);
        
        return response()->json($mouvement);
    }

    /**
     * Récupère les statistiques de mouvements pour un site
     */
    public function statistics(Request $request, $siteId)
    {
        $site = Site::findOrFail($siteId);
        
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');
        
        $query = SiteMouvementPopulation::where('site_id', $siteId);
        
        if ($dateDebut && $dateFin) {
            $query->whereBetween('date_mouvement', [$dateDebut, $dateFin]);
        }
        
        $stats = [
            'site' => $site->only(['id', 'nom', 'code_site']),
            'population_actuelle' => [
                'menages' => $site->menages,
                'individus' => $site->individus,
            ],
            'mouvements' => [
                'total' => $query->count(),
                'recensements' => (clone $query)->recensements()->count(),
                'arrivees' => [
                    'nombre' => (clone $query)->arrivees()->count(),
                    'total_individus' => (clone $query)->arrivees()->sum('individus'),
                ],
                'departs' => [
                    'nombre' => (clone $query)->departs()->count(),
                    'total_individus' => (clone $query)->departs()->sum('individus'),
                ],
            ],
            'periode' => [
                'debut' => $dateDebut,
                'fin' => $dateFin,
            ],
        ];
        
        return response()->json($stats);
    }

    /**
     * Récupère toutes les catégories de mouvements avec leurs raisons
     */
    public function getCategories()
    {
        $categories = CategorieMouvement::with('raisonMouvements')->get();
        
        return response()->json($categories);
    }

    /**
     * Récupère toutes les raisons de mouvements
     */
    public function getRaisons(Request $request)
    {
        $categorieId = $request->input('categorie_id');
        
        $query = RaisonMouvement::with('categorieMouvement');
        
        if ($categorieId) {
            $query->where('categorie_mouvement_id', $categorieId);
        }
        
        $raisons = $query->orderBy('name')->get();
        
        return response()->json($raisons);
    }

    /**
     * Valider un mouvement de population (réservé au super admin)
     */
    public function approuve(Request $request, $id)
    {
        $user = auth()->user();
        
        // Vérifier que l'utilisateur est super admin
        if ($user->role !== 'super_admin') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seul le super admin peut valider les mouvements',
                ], 403);
            }
            
            return back()->withErrors(['error' => 'Vous n\'avez pas les droits pour valider les mouvements']);
        }
        
        DB::beginTransaction();
        
        try {
            $mouvement = SiteMouvementPopulation::findOrFail($id);
            
            // Vérifier que le mouvement est en attente
            if ($mouvement->statut !== 'en_attente') {
                throw new \Exception('Ce mouvement a déjà été traité (statut: ' . $mouvement->statut . ')');
            }
            
            // Mettre à jour le mouvement
            $mouvement->update([
                'statut' => 'valide',
                'validated_at' => now(),
                'validated_by' => $user->id,
            ]);
            
            // Mettre à jour les populations du site
            $site = Site::findOrFail($mouvement->site_id);
            
            if ($mouvement->type_mouvement === 'recensement') {
                // Pour un recensement, remplacer les valeurs (utiliser les valeurs absolues)
                $site->update([
                    'menages' => abs($mouvement->menages),
                    'individus' => abs($mouvement->individus),
                    'f_0_5' => abs($mouvement->f_0_5),
                    'f_6_17' => abs($mouvement->f_6_17),
                    'f_18_59' => abs($mouvement->f_18_59),
                    'f_60_plus' => abs($mouvement->f_60_plus),
                    'h_0_5' => abs($mouvement->h_0_5),
                    'h_6_17' => abs($mouvement->h_6_17),
                    'h_18_59' => abs($mouvement->h_18_59),
                    'h_60_plus' => abs($mouvement->h_60_plus),
                    'date_mise_a_jour' => $mouvement->date_mouvement,
                ]);
            } else {
                // Pour arrivée/départ/ajustement, additionner les valeurs (qui peuvent être négatives)
                $site->increment('menages', $mouvement->menages);
                $site->increment('individus', $mouvement->individus);
                $site->increment('f_0_5', $mouvement->f_0_5);
                $site->increment('f_6_17', $mouvement->f_6_17);
                $site->increment('f_18_59', $mouvement->f_18_59);
                $site->increment('f_60_plus', $mouvement->f_60_plus);
                $site->increment('h_0_5', $mouvement->h_0_5);
                $site->increment('h_6_17', $mouvement->h_6_17);
                $site->increment('h_18_59', $mouvement->h_18_59);
                $site->increment('h_60_plus', $mouvement->h_60_plus);
                $site->update(['date_mise_a_jour' => $mouvement->date_mouvement]);
            }
            
            DB::commit();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Mouvement validé avec succès',
                    'mouvement' => $mouvement->fresh(['site', 'createdBy', 'validatedBy']),
                ]);
            }
            
            return back()->with('success', 'Mouvement validé avec succès! Les populations du site ont été mises à jour.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la validation',
                    'error' => $e->getMessage(),
                ], 500);
            }
            
            return back()->withErrors(['error' => 'Erreur lors de la validation: ' . $e->getMessage()]);
        }
    }

    /**
     * Rejeter un mouvement de population (réservé au super admin)
     */
    public function reject(Request $request, $id)
    {
        $user = auth()->user();
        
        // Vérifier que l'utilisateur est super admin
        if ($user->role !== 'super_admin') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seul le super admin peut rejeter les mouvements',
                ], 403);
            }
            
            return back()->withErrors(['error' => 'Vous n\'avez pas les droits pour rejeter les mouvements']);
        }
        
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);
        
        try {
            $mouvement = SiteMouvementPopulation::findOrFail($id);
            
            // Vérifier que le mouvement est en attente
            if ($mouvement->statut !== 'en_attente') {
                throw new \Exception('Ce mouvement a déjà été traité (statut: ' . $mouvement->statut . ')');
            }
            
            // Mettre à jour le mouvement
            $mouvement->update([
                'statut' => 'rejete',
                'validated_at' => now(),
                'validated_by' => $user->id,
                'rejection_reason' => $validated['rejection_reason'],
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Mouvement rejeté',
                    'mouvement' => $mouvement->fresh(['site', 'createdBy', 'validatedBy']),
                ]);
            }
            
            return back()->with('info', 'Mouvement rejeté. Les populations du site n\'ont pas été modifiées.');
            
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors du rejet',
                    'error' => $e->getMessage(),
                ], 500);
            }
            
            return back()->withErrors(['error' => 'Erreur lors du rejet: ' . $e->getMessage()]);
        }
    }

    /**
     * Télécharge un modèle Excel vierge pour l'importation de mouvements
     */
    public function downloadTemplate()
    {
        $user = auth()->user();

        // Charger les sites disponibles pour cet utilisateur
        $sitesQuery = Site::orderBy('nom');
        $allowedSiteIds = $this->getAccessibleSiteIdsForUser($user);
        if ($allowedSiteIds !== null) {
            $sitesQuery->whereIn('id', $allowedSiteIds);
        }
        $sites = $sitesQuery->get(['id', 'nom', 'code_site']);

        // Charger toutes les catégories et raisons
        $categories = CategorieMouvement::with('raisonMouvements')->orderBy('name')->get();
        $raisons = RaisonMouvement::with('categorieMouvement')->orderBy('name')->get(['id', 'name', 'code', 'categorie_mouvement_id']);

        $spreadsheet = new Spreadsheet();

        // ─── Feuille 1 : IMPORTATION ─────────────────────────────────────────
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('IMPORTATION');

        // En-têtes
        $headers = [
            'A' => ['label' => 'code_site *',        'comment' => 'Code du site (voir feuille SITES_RÉFÉRENCE)'],
            'B' => ['label' => 'date_mouvement *',    'comment' => 'Date au format AAAA-MM-JJ (ex: 2026-01-15)'],
            'C' => ['label' => 'type_mouvement *',    'comment' => 'arrivee | depart | ajustement | recensement'],
            'D' => ['label' => 'periode',             'comment' => 'Ex: Janvier 2026 (optionnel)'],
            'E' => ['label' => 'source',              'comment' => 'Ex: DTM, Site Management (optionnel)'],
            'F' => ['label' => 'menages *',           'comment' => 'Nombre de ménages (entier ≥ 0)'],
            'G' => ['label' => 'f_0_5',               'comment' => 'Femmes 0-5 ans'],
            'H' => ['label' => 'f_6_17',              'comment' => 'Femmes 6-17 ans'],
            'I' => ['label' => 'f_18_59',             'comment' => 'Femmes 18-59 ans'],
            'J' => ['label' => 'f_60_plus',           'comment' => 'Femmes 60 ans et plus'],
            'K' => ['label' => 'h_0_5',               'comment' => 'Hommes 0-5 ans'],
            'L' => ['label' => 'h_6_17',              'comment' => 'Hommes 6-17 ans'],
            'M' => ['label' => 'h_18_59',             'comment' => 'Hommes 18-59 ans'],
            'N' => ['label' => 'h_60_plus',           'comment' => 'Hommes 60 ans et plus'],
            'O' => ['label' => 'individus (auto)',     'comment' => 'Total calculé automatiquement — ne pas modifier'],
            'P' => ['label' => 'code_raison',         'comment' => 'Raison mouvement dépendante du type (voir feuille RAISONS_RÉFÉRENCE)'],
            'Q' => ['label' => 'raison',              'comment' => 'Description courte de la raison (optionnel)'],
            'R' => ['label' => 'description',         'comment' => 'Notes / observations libres (optionnel)'],
        ];

        // Ligne 1 : titre de la feuille
        $sheet->mergeCells('A1:R1');
        $sheet->setCellValue('A1', 'IMPORTATION DES MOUVEMENTS DE POPULATION — NE PAS MODIFIER LES LIGNES 1 À 4');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        // Ligne 2 : instructions générales
        $sheet->mergeCells('A2:R2');
        $sheet->setCellValue('A2', '⚠ Les listes en cascade : sélectionnez d\'abord type_mouvement (col C), puis code_raison (col P) se met à jour avec les raisons correspondantes');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '92400E']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'wrapText' => true],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(30);

        // Ligne 3 : en-têtes des colonnes
        foreach ($headers as $col => $info) {
            $cell = $col . '3';
            $sheet->setCellValue($cell, $info['label']);
            $isRequired = str_contains($info['label'], '*');
            $isAuto     = str_contains($info['label'], 'auto');
            $sheet->getStyle($cell)->applyFromArray([
                'font' => ['bold' => true, 'size' => 10,
                           'color' => ['rgb' => $isAuto ? '4B5563' : 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID,
                           'startColor' => ['rgb' => $isAuto ? 'D1FAE5'
                               : ($isRequired ? '1D4ED8' : '6B7280')]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
            ]);
        }
        $sheet->getRowDimension(3)->setRowHeight(30);

        // Ligne 4 : descriptions des colonnes
        foreach ($headers as $col => $info) {
            $cell = $col . '4';
            $sheet->setCellValue($cell, $info['comment']);
            $sheet->getStyle($cell)->applyFromArray([
                'font' => ['italic' => true, 'size' => 8, 'color' => ['rgb' => '6B7280']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
                'alignment' => ['wrapText' => true],
            ]);
        }
        $sheet->getRowDimension(4)->setRowHeight(28);

        // Exemple ligne 5
        $sheet->setCellValue('A5', $sites->first()?->code_site ?? 'CODE_SITE');
        $sheet->setCellValue('B5', date('Y-m-d'));
        $sheet->setCellValue('C5', 'arrivee');
        $sheet->setCellValue('D5', date('F Y'));
        $sheet->setCellValue('E5', 'DTM');
        $sheet->setCellValue('F5', 10);
        $sheet->setCellValue('G5', 5);
        $sheet->setCellValue('H5', 8);
        $sheet->setCellValue('I5', 20);
        $sheet->setCellValue('J5', 2);
        $sheet->setCellValue('K5', 4);
        $sheet->setCellValue('L5', 7);
        $sheet->setCellValue('M5', 18);
        $sheet->setCellValue('N5', 3);
        $sheet->setCellValue('O5', '=G5+H5+I5+J5+K5+L5+M5+N5');
        $sheet->setCellValue('P5', '');
        $sheet->setCellValue('Q5', 'Retour sécurisé');
        $sheet->setCellValue('R5', 'Ligne exemple — vous pouvez la supprimer');
        $sheet->getStyle('A5:R5')->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '6B7280']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']],
        ]);

        // Validation dropdown type_mouvement et raison en cascade
        $typesValides = '"arrivee,depart,ajustement,recensement"';
        for ($row = 6; $row <= 1000; $row++) {
            // Formule individus
            $sheet->setCellValue("O{$row}", "=G{$row}+H{$row}+I{$row}+J{$row}+K{$row}+L{$row}+M{$row}+N{$row}");
            $sheet->getStyle("O{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ECFDF5']],
                'font' => ['color' => ['rgb' => '065F46']],
            ]);

            // Dropdown type_mouvement (colonne C)
            $validationType = $sheet->getCell("C{$row}")->getDataValidation();
            $validationType->setType(DataValidation::TYPE_LIST);
            $validationType->setErrorStyle(DataValidation::STYLE_STOP);
            $validationType->setAllowBlank(false);
            $validationType->setShowDropDown(true);
            $validationType->setShowErrorMessage(true);
            $validationType->setErrorTitle('Type invalide');
            $validationType->setError('Choisissez: arrivee, depart, ajustement, ou recensement');
            $validationType->setFormula1($typesValides);

            // Dropdown code_raison (colonne P) — avec cascade basée sur type_mouvement
            // Utiliser INDIRECT pour référencer une plage nommée dynamique basée sur C{$row}
            $validationRaison = $sheet->getCell("P{$row}")->getDataValidation();
            $validationRaison->setType(DataValidation::TYPE_LIST);
            $validationRaison->setErrorStyle(DataValidation::STYLE_STOP);
            $validationRaison->setAllowBlank(true); // raison est optionnelle
            $validationRaison->setShowDropDown(true);
            $validationRaison->setShowErrorMessage(true);
            $validationRaison->setErrorTitle('Raison invalide');
            $validationRaison->setError('Sélectionnez une raison valide pour le type de mouvement');
            
            // Utiliser INDIRECT avec le type de mouvement pour dynamiquement charger la liste
            // La formule pointe vers des plages nommées comme "arrivee_raisons", "depart_raisons", etc.
            $validationRaison->setFormula1('IFERROR(INDIRECT(C'.$row.'&"_raisons"),"")');
        }

        // Protection colonne O (individus auto)
        $sheet->getStyle('O3:O1000')->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_PROTECTED);

        // Largeurs des colonnes
        $widths = ['A' => 18, 'B' => 15, 'C' => 16, 'D' => 16, 'E' => 16,
                   'F' => 12, 'G' => 10, 'H' => 10, 'I' => 10, 'J' => 10,
                   'K' => 10, 'L' => 10, 'M' => 10, 'N' => 10, 'O' => 14,
                   'P' => 18, 'Q' => 22, 'R' => 30];
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Figer les 4 premières lignes et la colonne A
        $sheet->freezePane('B5');

        // ─── Feuille 2 : SITES_RÉFÉRENCE ──────────────────────────────────────
        $sheetSites = $spreadsheet->createSheet();
        $sheetSites->setTitle('SITES_RÉFÉRENCE');
        $sheetSites->setCellValue('A1', 'Code Site');
        $sheetSites->setCellValue('B1', 'Nom du Site');
        $sheetSites->getStyle('A1:B1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row = 2;
        foreach ($sites as $site) {
            $sheetSites->setCellValue("A{$row}", $site->code_site);
            $sheetSites->setCellValue("B{$row}", $site->nom);
            $row++;
        }
        $sheetSites->getColumnDimension('A')->setWidth(18);
        $sheetSites->getColumnDimension('B')->setWidth(40);

        // ─── Feuille 3 : RAISONS_RÉFÉRENCE ────────────────────────────────────
        $sheetRaisons = $spreadsheet->createSheet();
        $sheetRaisons->setTitle('RAISONS_RÉFÉRENCE');
        $sheetRaisons->setCellValue('A1', 'Code Raison');
        $sheetRaisons->setCellValue('B1', 'Nom Raison');
        $sheetRaisons->setCellValue('C1', 'Catégorie');
        $sheetRaisons->getStyle('A1:C1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row = 2;
        foreach ($raisons as $raison) {
            $sheetRaisons->setCellValue("A{$row}", $raison->code);
            $sheetRaisons->setCellValue("B{$row}", $raison->name);
            $sheetRaisons->setCellValue("C{$row}", $raison->categorieMouvement?->name ?? '');
            $row++;
        }
        $sheetRaisons->getColumnDimension('A')->setWidth(18);
        $sheetRaisons->getColumnDimension('B')->setWidth(40);
        $sheetRaisons->getColumnDimension('C')->setWidth(30);

        // ─── Feuilles séparées pour chaque catégorie (pour listes en cascade) ──
        // Créer une feuille par catégorie avec les codes raisons correspondants
        // Noms des feuilles: "arrivee_raisons", "depart_raisons", etc.
        
        // Mapper les catégories aux types de mouvement
        $categoryToType = [
            'nouvelle entrée' => 'arrivee',
            'nouvelle entree' => 'arrivee',
            'arrivée' => 'arrivee',
            'arrivee' => 'arrivee',
            'entrée' => 'arrivee',
            'entree' => 'arrivee',
            'sortie' => 'depart',
            'départ' => 'depart',
            'depart' => 'depart',
        ];

        // Pour chaque type de mouvement créer une feuille avec les raisons
        $typesRaisonsMap = [];
        foreach ($categories as $category) {
            $raisonsForCategory = $category->raisonMouvements()->orderBy('name')->get(['code'])->pluck('code')->toArray();
            
            // Associer la catégorie à son type de mouvement
            $categoryNameLower = strtolower($category->name);
            $typeKey = null;
            
            // Chercher le type correspondant
            foreach ($categoryToType as $pattern => $type) {
                if (str_contains($categoryNameLower, $pattern) || str_contains($pattern, $categoryNameLower)) {
                    $typeKey = $type;
                    break;
                }
            }

            // Par défaut, essayer de déterminer depuis le nom direct
            if (!$typeKey) {
                if (str_contains($categoryNameLower, 'arrivee') || str_contains($categoryNameLower, 'entree')) {
                    $typeKey = 'arrivee';
                } elseif (str_contains($categoryNameLower, 'sortie') || str_contains($categoryNameLower, 'depart')) {
                    $typeKey = 'depart';
                }
            }

            if ($typeKey) {
                if (!isset($typesRaisonsMap[$typeKey])) {
                    $typesRaisonsMap[$typeKey] = [];
                }
                $typesRaisonsMap[$typeKey] = array_merge($typesRaisonsMap[$typeKey], $raisonsForCategory);
            }
        }

        // Créer les feuilles cachées pour les listes en cascade
        $typesWithRaisons = ['arrivee', 'depart', 'ajustement', 'recensement'];
        foreach ($typesWithRaisons as $type) {
            $raisonsForType = $typesRaisonsMap[$type] ?? [];
            
            // Créer la feuille
            $sheetType = $spreadsheet->createSheet();
            $sheetType->setTitle($type . '_raisons');
            $sheetType->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);
            
            // Ajouter les codes raisons dans cette feuille
            $row = 1;
            foreach ($raisonsForType as $code) {
                $sheetType->setCellValue("A{$row}", $code);
                $row++;
            }
            
            // Créer une plage nommée pour cette liste (ex: "arrivee_raisons")
            // La plage nommée sera utilisée dans INDIRECT()
            $rangeName = $type . '_raisons';
            $range = $type . '_raisons!$A$1:$A$' . ($row - 1);
            $spreadsheet->addNamedRange(
                new \PhpOffice\PhpSpreadsheet\NamedRange($rangeName, $sheetType, $range)
            );
        }

        // Revenir à la feuille principale
        $spreadsheet->setActiveSheetIndex(0);

        // Écriture et téléchargement
        $writer = new Xlsx($spreadsheet);
        $filename = 'template_mouvements_population_' . date('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Importe des mouvements depuis un fichier Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'fichier_excel' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $user = auth()->user();
        $file = $request->file('fichier_excel');

        $reader = new XlsxReader();
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file->getRealPath());
        $sheet = $spreadsheet->getSheetByName('IMPORTATION') ?? $spreadsheet->getActiveSheet();

        $highestRow = $sheet->getHighestDataRow();

        $successes = 0;
        $errors = [];

        // Charger les sites indexés par code_site pour l'utilisateur
        $sitesQuery = Site::query();
        $allowedSiteIds = $this->getAccessibleSiteIdsForUser($user);
        if ($allowedSiteIds !== null) {
            $sitesQuery->whereIn('id', $allowedSiteIds);
        }
        $sitesMap = $sitesQuery->pluck('id', 'code_site')->toArray();

        // Charger les raisons indexées par code
        $raisonsMap = RaisonMouvement::whereNotNull('code')->pluck('id', 'code')->toArray();

        $typesValides = ['arrivee', 'depart', 'ajustement', 'recensement'];

        DB::beginTransaction();
        try {
            // Les données commencent à la ligne 5 (ligne 3 = headers, 4 = descriptions, 5 = exemple)
            // On commence à 6 pour éviter la ligne exemple, mais on supporte aussi partir de 5
            $startRow = 5;
            // Détecter si la ligne 5 est l'exemple (colonne R contient "Ligne exemple")
            $remark = (string) $sheet->getCell("R5")->getValue();
            if (str_contains($remark, 'exemple') || str_contains($remark, 'example')) {
                $startRow = 6;
            }

            for ($row = $startRow; $row <= $highestRow; $row++) {
                $codeSite      = trim((string) $sheet->getCell("A{$row}")->getValue());
                $dateMouvement = trim((string) $sheet->getCell("B{$row}")->getCalculatedValue());
                $typeMouvement = trim(strtolower((string) $sheet->getCell("C{$row}")->getValue()));
                $periode       = trim((string) $sheet->getCell("D{$row}")->getValue());
                $source        = trim((string) $sheet->getCell("E{$row}")->getValue());
                $menages       = (int) $sheet->getCell("F{$row}")->getValue();
                $f0_5          = (int) $sheet->getCell("G{$row}")->getValue();
                $f6_17         = (int) $sheet->getCell("H{$row}")->getValue();
                $f18_59        = (int) $sheet->getCell("I{$row}")->getValue();
                $f60plus       = (int) $sheet->getCell("J{$row}")->getValue();
                $h0_5          = (int) $sheet->getCell("K{$row}")->getValue();
                $h6_17         = (int) $sheet->getCell("L{$row}")->getValue();
                $h18_59        = (int) $sheet->getCell("M{$row}")->getValue();
                $h60plus       = (int) $sheet->getCell("N{$row}")->getValue();
                $codeRaison    = trim((string) $sheet->getCell("P{$row}")->getValue());
                $raison        = trim((string) $sheet->getCell("Q{$row}")->getValue());
                $description   = trim((string) $sheet->getCell("R{$row}")->getValue());

                // Ignorer les lignes vides
                if ($codeSite === '' && $dateMouvement === '') {
                    continue;
                }

                $rowErrors = [];

                // Validation
                if ($codeSite === '') {
                    $rowErrors[] = 'code_site manquant';
                } elseif (!isset($sitesMap[$codeSite])) {
                    $rowErrors[] = "code_site '{$codeSite}' introuvable";
                }

                if ($dateMouvement === '') {
                    $rowErrors[] = 'date_mouvement manquante';
                } elseif (!\DateTime::createFromFormat('Y-m-d', $dateMouvement)) {
                    $rowErrors[] = "date_mouvement '{$dateMouvement}' invalide (format attendu: AAAA-MM-JJ)";
                }

                if (!in_array($typeMouvement, $typesValides)) {
                    $rowErrors[] = "type_mouvement '{$typeMouvement}' invalide (attendu: arrivee, depart, ajustement, recensement)";
                }

                if (!empty($rowErrors)) {
                    $errors[] = "Ligne {$row}: " . implode('; ', $rowErrors);
                    continue;
                }

                $individus = $f0_5 + $f6_17 + $f18_59 + $f60plus + $h0_5 + $h6_17 + $h18_59 + $h60plus;
                $siteId    = $sitesMap[$codeSite];
                $raisonId  = ($codeRaison !== '' && isset($raisonsMap[$codeRaison])) ? $raisonsMap[$codeRaison] : null;

                // Pour les départs, convertir en valeurs négatives
                $sign = ($typeMouvement === 'depart') ? -1 : 1;

                SiteMouvementPopulation::create([
                    'site_id'            => $siteId,
                    'date_mouvement'     => $dateMouvement,
                    'type_mouvement'     => $typeMouvement,
                    'periode'            => $periode ?: null,
                    'source'             => $source ?: null,
                    'menages'            => abs($menages) * $sign,
                    'individus'          => abs($individus) * $sign,
                    'f_0_5'              => abs($f0_5) * $sign,
                    'f_6_17'             => abs($f6_17) * $sign,
                    'f_18_59'            => abs($f18_59) * $sign,
                    'f_60_plus'          => abs($f60plus) * $sign,
                    'h_0_5'              => abs($h0_5) * $sign,
                    'h_6_17'             => abs($h6_17) * $sign,
                    'h_18_59'            => abs($h18_59) * $sign,
                    'h_60_plus'          => abs($h60plus) * $sign,
                    'raison_mouvement_id' => $raisonId,
                    'raison'             => $raison ?: null,
                    'description'        => $description ?: null,
                    'created_by'         => $user->id,
                    'statut'             => 'en_attente',
                ]);

                $successes++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erreur lors de l\'importation: ' . $e->getMessage()]);
        }

        $message = "{$successes} mouvement(s) importé(s) avec succès et en attente de validation.";
        if (!empty($errors)) {
            $message .= ' ' . count($errors) . ' ligne(s) ignorée(s).';
            return back()
                ->with('warning', $message)
                ->with('import_errors', $errors);
        }

        return redirect()->route('admin.mouvements.index')
            ->with('success', $message);
    }

    /**
     * Récupère les IDs de sites accessibles pour un utilisateur.
     *
     * - super_admin: accès total (null = pas de restriction)
     * - autres rôles: uniquement les sites de leur organisation
     */
    private function getAccessibleSiteIdsForUser($user)
    {
        if ($user->role === 'super_admin') {
            return null;
        }

        if ($user->organisation_id) {
            return Site::where('organisation_id', $user->organisation_id)
                ->pluck('id')
                ->unique()
                ->values();
        } else {
            return collect();
        }
    }
}
