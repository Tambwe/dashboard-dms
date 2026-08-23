<?php

namespace App\Http\Controllers;

use App\Models\OssatReport;
use App\Models\Site;
use App\Services\SitePopulationService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OrganisationSiteController extends Controller
{
    /**
     * Display a listing of the organisation's sites.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Vérifier que l'utilisateur appartient à une organisation
        if (!$user->organisation_id) {
            abort(403, 'Vous devez être membre d\'une organisation pour accéder à cette page.');
        }

        $orgUserIds = \App\Models\User::where('organisation_id', $user->organisation_id)->pluck('id');
        $accessSiteIds = \Illuminate\Support\Facades\DB::table('site_user_access')
            ->whereIn('user_id', $orgUserIds)
            ->pluck('site_id');

        $query = Site::where(function ($q) use ($user, $accessSiteIds) {
            $q->where('organisation_id', $user->organisation_id);
            if ($accessSiteIds->isNotEmpty()) {
                $q->orWhereIn('id', $accessSiteIds);
            }
        })->with(['typeSite', 'commune', 'categorieSite', 'mouvementsPopulationValides']);

        // Recherche
        if ($request->filled('search')) {
            $query->where('nom', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('gps_status')) {
            if ($request->gps_status === 'missing') {
                $query->where(function ($gpsQuery) {
                    $gpsQuery->whereNull('latitude')
                        ->orWhereNull('longitude')
                        ->orWhere('latitude', 0)
                        ->orWhere('longitude', 0);
                });
            } elseif ($request->gps_status === 'present') {
                $query->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->where('latitude', '!=', 0)
                    ->where('longitude', '!=', 0);
            }
        }

        $sites = $query->paginate(20);

        return view('organisation.sites.index', compact('sites'));
    }

    /**
     * Show the form for editing the specified site.
     */
    public function edit(Site $site)
    {
        $user = auth()->user();
        
        // Vérifier que le site appartient à l'organisation de l'utilisateur
        if ($site->organisation_id !== $user->organisation_id) {
            abort(403, 'Vous n\'avez pas accès à ce site.');
        }

        // Charger les relations de gestion du site
        $site->load(['organisation', 'gestionnaire', 'coordinateur', 'mouvementsPopulationValides']);

        $ossatReport = OssatReport::where('site_id', $site->id)->latest('today')->first();

        $populationMouvement = app(SitePopulationService::class)->snapshotForSite($site->id);

        return view('organisation.sites.edit', compact('site', 'ossatReport', 'populationMouvement'));
    }

    /**
     * Update the specified site in storage.
     */
    public function update(Request $request, Site $site)
    {
        $user = auth()->user();
        
        // Vérifier que le site appartient à l'organisation de l'utilisateur
        if ($site->organisation_id !== $user->organisation_id) {
            abort(403, 'Vous n\'avez pas accès à ce site.');
        }

        $validated = $request->validate([
            'longitude' => 'nullable|numeric|between:-180,180',
            'latitude' => 'nullable|numeric|between:-90,90',
            'geojson_active_layer' => 'nullable|string|in:geojson_data,geojson_layer_ecole,geojson_layer_robinet,geojson_layer_lavage_main,geojson_layer_bloc_sites,geojson_layer_centre_sante',
            'geojson_data' => 'nullable|json',
            'geojson_layer_ecole' => 'nullable|json',
            'geojson_layer_robinet' => 'nullable|json',
            'geojson_layer_lavage_main' => 'nullable|json',
            'geojson_layer_bloc_sites' => 'nullable|json',
            'geojson_layer_centre_sante' => 'nullable|json',
            'photos.*' => 'nullable|image|max:5120', // 5MB max par image
        ]);

        // Gérer les coordonnées GPS
        if ($request->has('longitude')) {
            $site->longitude = $request->longitude;
        }
        
        if ($request->has('latitude')) {
            $site->latitude = $request->latitude;
        }

        $geojsonInputKeys = [
            'geojson_data',
            'geojson_layer_ecole',
            'geojson_layer_robinet',
            'geojson_layer_lavage_main',
            'geojson_layer_bloc_sites',
            'geojson_layer_centre_sante',
        ];

        if ($request->hasAny($geojsonInputKeys) || $request->filled('geojson_active_layer')) {
            $layerDefinitions = [
                'ecole' => ['field' => 'geojson_layer_ecole', 'name' => 'Ecole'],
                'robinet' => ['field' => 'geojson_layer_robinet', 'name' => 'Robinet'],
                'lavage_main' => ['field' => 'geojson_layer_lavage_main', 'name' => 'Lavage main'],
                'bloc_sites' => ['field' => 'geojson_layer_bloc_sites', 'name' => 'Bloc sites'],
                'centre_sante' => ['field' => 'geojson_layer_centre_sante', 'name' => 'Centre de sante'],
            ];

            $activeLayerField = (string) $request->input('geojson_active_layer', '');
            $isPartialLayerUpdate = in_array($activeLayerField, $geojsonInputKeys, true);
            $existingLayersByField = $this->extractLayerPayloadsByField($site->geojson_data);

            $layers = [];
            $geojsonErrors = new MessageBag();

            foreach ($layerDefinitions as $key => $definition) {
                $fieldName = $definition['field'];
                if ($isPartialLayerUpdate && $fieldName !== $activeLayerField) {
                    foreach ($existingLayersByField[$fieldName] ?? [] as $existingLayer) {
                        $layers[] = $existingLayer;
                    }
                    continue;
                }

                $rawValue = trim((string) $request->input($fieldName, ''));
                if ($rawValue === '') {
                    continue;
                }

                $validatedLayers = $this->parseGeojsonLayersFromField(
                    $rawValue,
                    $fieldName,
                    $definition['name'],
                    $key,
                    $geojsonErrors
                );

                foreach ($validatedLayers as $validatedLayer) {
                    $layers[] = $validatedLayer;
                }
            }

            if ($isPartialLayerUpdate && $activeLayerField !== 'geojson_data') {
                foreach ($existingLayersByField['geojson_data'] ?? [] as $existingLayer) {
                    $layers[] = $existingLayer;
                }
            } else {
                $rawDefaultGeojson = trim((string) $request->input('geojson_data', ''));
                if ($rawDefaultGeojson !== '') {
                    $validatedDefaultLayers = $this->parseGeojsonLayersFromField(
                        $rawDefaultGeojson,
                        'geojson_data',
                        'Autre couche',
                        'autre',
                        $geojsonErrors
                    );

                    foreach ($validatedDefaultLayers as $validatedLayer) {
                        $layers[] = $validatedLayer;
                    }
                }
            }

            if ($geojsonErrors->isNotEmpty()) {
                return redirect()->back()->withErrors($geojsonErrors)->withInput();
            }

            if (empty($layers)) {
                $site->geojson_data = null;
            } elseif (count($layers) === 1 && str_starts_with((string) ($layers[0]['key'] ?? ''), 'autre')) {
                $site->geojson_data = $layers[0]['geojson'];
            } else {
                $site->geojson_data = [
                    'type' => 'LayerCollection',
                    'version' => 1,
                    'layers' => $layers,
                ];
            }
        }

        // Gérer les photos
        if ($request->hasFile('photos')) {
            $photos = $site->photos ?? [];
            
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('sites/photos', 'public');
                $photos[] = $path;
            }
            
            $site->photos = $photos;
        }

        $site->save();

        return redirect()->route('organisation.sites.edit', $site)
            ->with('success', 'Site mis à jour avec succès.');
    }

    private function isWgs84Geojson(array $geojson): bool
    {
        $coordinates = [];
        $this->collectCoordinates($geojson, $coordinates);

        if (empty($coordinates)) {
            return false;
        }

        foreach ($coordinates as $coordinate) {
            $lon = $coordinate[0];
            $lat = $coordinate[1];

            if (abs($lon) > 180 || abs($lat) > 90) {
                return false;
            }
        }

        return true;
    }

    private function parseGeojsonLayersFromField(
        string $rawValue,
        string $fieldName,
        string $fallbackLayerName,
        string $baseKey,
        MessageBag $geojsonErrors
    ): array {
        $decoded = json_decode($rawValue, true);

        if (!is_array($decoded)) {
            $geojsonErrors->add($fieldName, "La couche {$fallbackLayerName} n'est pas un GeoJSON valide.");
            return [];
        }

        if (isset($decoded['layers']) && is_array($decoded['layers'])) {
            $layers = [];

            foreach ($decoded['layers'] as $index => $layerItem) {
                if (!is_array($layerItem)) {
                    $geojsonErrors->add($fieldName, "La couche {$fallbackLayerName} (sous-couche " . ($index + 1) . ") n'est pas un GeoJSON valide.");
                    continue;
                }

                $layerGeojson = $layerItem['geojson'] ?? $layerItem['data'] ?? null;
                $layerName = trim((string) ($layerItem['name'] ?? $layerItem['label'] ?? ''));
                if ($layerName === '') {
                    $layerName = $fallbackLayerName . ' ' . ($index + 1);
                }

                if (!is_array($layerGeojson) || !isset($layerGeojson['type'])) {
                    $geojsonErrors->add($fieldName, "La couche {$layerName} n'est pas un GeoJSON valide.");
                    continue;
                }

                if (!$this->isWgs84Geojson($layerGeojson)) {
                    $geojsonErrors->add($fieldName, "La couche {$layerName} doit etre en WGS84 (longitude/latitude). Reprojetez en EPSG:4326.");
                    continue;
                }

                $layers[] = [
                    'key' => $index === 0 ? $baseKey : ($baseKey . '_' . ($index + 1)),
                    'name' => $layerName,
                    'geojson' => $layerGeojson,
                ];
            }

            return $layers;
        }

        if (!isset($decoded['type'])) {
            $geojsonErrors->add($fieldName, "La couche {$fallbackLayerName} n'est pas un GeoJSON valide.");
            return [];
        }

        if (!$this->isWgs84Geojson($decoded)) {
            $geojsonErrors->add($fieldName, "La couche {$fallbackLayerName} doit etre en WGS84 (longitude/latitude). Reprojetez en EPSG:4326.");
            return [];
        }

        return [[
            'key' => $baseKey,
            'name' => $fallbackLayerName,
            'geojson' => $decoded,
        ]];
    }

    private function extractLayerPayloadsByField($geojson): array
    {
        $grouped = [
            'geojson_data' => [],
            'geojson_layer_ecole' => [],
            'geojson_layer_robinet' => [],
            'geojson_layer_lavage_main' => [],
            'geojson_layer_bloc_sites' => [],
            'geojson_layer_centre_sante' => [],
        ];

        $keyToField = [
            'ecole' => 'geojson_layer_ecole',
            'robinet' => 'geojson_layer_robinet',
            'lavage_main' => 'geojson_layer_lavage_main',
            'bloc_sites' => 'geojson_layer_bloc_sites',
            'centre_sante' => 'geojson_layer_centre_sante',
            'autre' => 'geojson_data',
        ];

        if (!is_array($geojson)) {
            return $grouped;
        }

        if (isset($geojson['layers']) && is_array($geojson['layers'])) {
            foreach ($geojson['layers'] as $layerIndex => $layer) {
                if (!is_array($layer)) {
                    continue;
                }

                $layerGeojson = $layer['geojson'] ?? $layer['data'] ?? null;
                if (!is_array($layerGeojson) || !isset($layerGeojson['type'])) {
                    continue;
                }

                $layerKey = (string) ($layer['key'] ?? '');
                $baseLayerKey = $layerKey;
                if (strpos($baseLayerKey, '_') !== false) {
                    $baseLayerKey = strstr($baseLayerKey, '_', true) ?: $baseLayerKey;
                }

                $targetField = $keyToField[$baseLayerKey] ?? 'geojson_data';
                $fallbackName = $targetField === 'geojson_data' ? 'Autre couche' : ('Couche ' . ($layerIndex + 1));
                $layerName = trim((string) ($layer['name'] ?? $layer['label'] ?? ''));

                $grouped[$targetField][] = [
                    'key' => $layerKey !== '' ? $layerKey : $baseLayerKey,
                    'name' => $layerName !== '' ? $layerName : $fallbackName,
                    'geojson' => $layerGeojson,
                ];
            }

            return $grouped;
        }

        if (isset($geojson['type'])) {
            $grouped['geojson_data'][] = [
                'key' => 'autre',
                'name' => 'Autre couche',
                'geojson' => $geojson,
            ];
        }

        return $grouped;
    }

    private function collectCoordinates($node, array &$coordinates): void
    {
        if (!is_array($node)) {
            return;
        }

        if (
            array_key_exists(0, $node)
            && array_key_exists(1, $node)
            && is_numeric($node[0])
            && is_numeric($node[1])
            && !is_array($node[0])
        ) {
            $coordinates[] = [(float) $node[0], (float) $node[1]];
            return;
        }

        foreach ($node as $value) {
            $this->collectCoordinates($value, $coordinates);
        }
    }

    /**
     * Delete a photo from the site.
     */
    public function deletePhoto(Request $request, Site $site)
    {
        $user = auth()->user();
        
        // Vérifier que le site appartient à l'organisation de l'utilisateur
        if ($site->organisation_id !== $user->organisation_id) {
            abort(403, 'Vous n\'avez pas accès à ce site.');
        }

        $request->validate([
            'photo_path' => 'required|string',
        ]);

        $photos = $site->photos ?? [];
        
        // Trouver et supprimer la photo
        $index = array_search($request->photo_path, $photos);
        
        if ($index !== false) {
            // Supprimer le fichier du stockage
            Storage::disk('public')->delete($request->photo_path);
            
            // Retirer de la liste
            unset($photos[$index]);
            $site->photos = array_values($photos);
            $site->save();
        }

        return redirect()->back()->with('success', 'Photo supprimée avec succès.');
    }
}
