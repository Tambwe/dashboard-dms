<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\SiteMouvementPopulation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SiteMasterListController extends Controller
{
    /**
     * Affiche la Master List des sites avec variations mensuelles
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $search = $request->input('search');
        $perPage = $request->input('per_page', 50);
        
        // Calculer la date du mois dernier
        $currentMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        
        // Query de base
        $query = Site::with([
            'province',
            'territoire',
            'commune',
            'coordinateur',
            'gestionnaire',
            'typeSite',
            'categorieSite'
        ]);
        
        // Filtre par organisation si nécessaire
        if ($user->role !== 'super_admin') {
            $query->where('organisation_id', $user->organisation_id);
        }
        
        // Recherche
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nom', 'LIKE', "%{$search}%")
                  ->orWhere('code_site', 'LIKE', "%{$search}%")
                  ->orWhereHas('province', function($sq) use ($search) {
                      $sq->where('name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('territoire', function($sq) use ($search) {
                      $sq->where('name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('commune', function($sq) use ($search) {
                      $sq->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }
        
        // Pagination
        $sites = $query->orderBy('nom')->paginate($perPage)->withQueryString();
        
        // Calculer les variations pour chaque site
        foreach ($sites as $site) {
            $site->variation = $this->calculateMonthlyVariation($site->id, $lastMonth, $currentMonth);
        }
        
        return view('sites.master-list', compact('sites', 'search', 'perPage'));
    }
    
    /**
     * Calcule la variation mensuelle pour un site
     */
    private function calculateMonthlyVariation($siteId, $lastMonth, $currentMonth)
    {
        // Récupérer le dernier recensement validé du mois précédent
        $lastMonthData = SiteMouvementPopulation::where('site_id', $siteId)
            ->where('statut', 'valide')
            ->where('type_mouvement', 'recensement')
            ->where('date_mouvement', '>=', $lastMonth->copy()->subMonth())
            ->where('date_mouvement', '<', $currentMonth)
            ->orderBy('date_mouvement', 'desc')
            ->first();
        
        // Récupérer les données actuelles du site
        $site = Site::find($siteId);
        
        if (!$lastMonthData || !$site) {
            return [
                'menages_variation' => 0,
                'menages_percentage' => 0,
                'individus_variation' => 0,
                'individus_percentage' => 0,
                'has_data' => false
            ];
        }
        
        $menagesVariation = $site->menages - $lastMonthData->menages;
        $individusVariation = $site->individus - $lastMonthData->individus;
        
        $menagesPercentage = $lastMonthData->menages > 0 
            ? round(($menagesVariation / $lastMonthData->menages) * 100, 1) 
            : 0;
            
        $individusPercentage = $lastMonthData->individus > 0 
            ? round(($individusVariation / $lastMonthData->individus) * 100, 1) 
            : 0;
        
        return [
            'menages_variation' => $menagesVariation,
            'menages_percentage' => $menagesPercentage,
            'menages_previous' => $lastMonthData->menages,
            'individus_variation' => $individusVariation,
            'individus_percentage' => $individusPercentage,
            'individus_previous' => $lastMonthData->individus,
            'has_data' => true
        ];
    }
    
    /**
     * Export Excel de la Master List
     */
    public function exportExcel(Request $request)
    {
        $user = auth()->user();
        $search = $request->input('search');
        
        // Calculer les dates
        $currentMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        
        // Query identique à index()
        $query = Site::with([
            'province',
            'territoire',
            'commune',
            'coordinateur',
            'gestionnaire',
            'typeSite',
            'categorieSite'
        ]);
        
        if ($user->role !== 'super_admin') {
            $query->where('organisation_id', $user->organisation_id);
        }
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nom', 'LIKE', "%{$search}%")
                  ->orWhere('code_site', 'LIKE', "%{$search}%");
            });
        }
        
        $sites = $query->orderBy('nom')->get();
        
        // Calculer les variations
        foreach ($sites as $site) {
            $site->variation = $this->calculateMonthlyVariation($site->id, $lastMonth, $currentMonth);
        }
        
        return $this->generateExcel($sites);
    }
    
    /**
     * Génère le fichier Excel
     */
    private function generateExcel($sites)
    {
        $filename = 'master-list-sites-' . date('Y-m-d-His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];
        
        $callback = function() use ($sites) {
            $file = fopen('php://output', 'w');
            
            // BOM UTF-8 pour Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // En-têtes
            fputcsv($file, [
                'Code Site',
                'Nom du Site',
                'Province',
                'Territoire',
                'Zone de Santé',
                'Type Site',
                'Catégorie',
                'Coordinateur',
                'Gestionnaire',
                'Ménages Actuels',
                'Individus Actuels',
                'Ménages Mois Dernier',
                'Variation Ménages',
                'Variation % Ménages',
                'Individus Mois Dernier',
                'Variation Individus',
                'Variation % Individus',
                'Date Mise à Jour'
            ], ';');
            
            // Données
            foreach ($sites as $site) {
                fputcsv($file, [
                    $site->code_site,
                    $site->nom,
                    $site->province->name ?? '',
                    $site->territoire->name ?? '',
                    $site->commune->name ?? '',
                    $site->typeSite->name ?? '',
                    $site->categorieSite->name ?? '',
                    $site->coordinateur->nom ?? '',
                    $site->gestionnaire->nom ?? '',
                    $site->menages,
                    $site->individus,
                    $site->variation['menages_previous'] ?? '',
                    $site->variation['menages_variation'] ?? 0,
                    $site->variation['menages_percentage'] ?? 0,
                    $site->variation['individus_previous'] ?? '',
                    $site->variation['individus_variation'] ?? 0,
                    $site->variation['individus_percentage'] ?? 0,
                    $site->date_mise_a_jour ? $site->date_mise_a_jour->format('d/m/Y') : ''
                ], ';');
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
