<?php

namespace App\Http\Controllers;

use App\Models\OssatReport;
use App\Models\Site;
use App\Models\SiteMouvementPopulation;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class UserSiteController extends Controller
{
    /**
     * Display a listing of sites the user has access to.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->isSuperAdmin() || $user->isSigUser()) {
            $query = Site::query()->with(['typeSite', 'commune', 'categorieSite', 'organisation']);
        } else {
        
            // Si l'utilisateur n'a pas d'organisation et n'est pas super admin
            if (!$user->organisation_id && !$user->isSuperAdmin()) {
                // Récupérer uniquement les sites assignés
                $query = $user->assignedSites()->with(['typeSite', 'commune', 'categorieSite', 'organisation']);
            } else {
                // Sites de l'organisation OU sites assignés
                $assignedSiteIds = $user->assignedSites()->pluck('sites.id');

                $query = Site::query()
                    ->where(function($q) use ($user, $assignedSiteIds) {
                        if ($user->organisation_id) {
                            $q->where('organisation_id', $user->organisation_id);
                        }
                        if ($assignedSiteIds->isNotEmpty()) {
                            $q->orWhereIn('id', $assignedSiteIds);
                        }
                    })
                    ->with(['typeSite', 'commune', 'categorieSite', 'organisation']);
            }
        }

        // Recherche
        if ($request->filled('search')) {
            $query->where('nom', 'like', '%' . $request->search . '%');
        }

        $sites = $query->paginate(20);
        $geojsonLayersMetaBySite = [];

        foreach ($sites as $site) {
            $geojsonLayersMetaBySite[$site->id] = $this->extractGeojsonLayersMeta($site->geojson_data);
        }

        return view('user.sites.index', compact('sites', 'geojsonLayersMetaBySite'));
    }

    /**
     * Show the form for editing the specified site.
     */
    public function edit(Site $site)
    {
        $user = auth()->user();
        
        // Vérifier l'accès
        if (!$user->hasAccessToSite($site)) {
            abort(403, 'Vous n\'avez pas accès à ce site.');
        }

        // Charger les permissions spécifiques
        $access = $user->assignedSites()->where('sites.id', $site->id)->first();
        $canEdit = $user->canEditSite($site);
        $canEditMedia = $user->canEditSiteMedia($site);

        // Charger les relations de gestion du site
        $site->load(['organisation', 'gestionnaire', 'coordinateur']);

        $ossatReport = OssatReport::where('site_id', $site->id)->latest('today')->first();

        $populationMouvement = SiteMouvementPopulation::where('site_id', $site->id)
            ->where('statut', 'valide')
            ->latest('date_mouvement')
            ->first()
            ?? SiteMouvementPopulation::where('site_id', $site->id)
            ->latest('date_mouvement')
            ->first();

        $geojsonLayersMeta = $this->extractGeojsonLayersMeta($site->geojson_data);

        return view('user.sites.edit', compact('site', 'canEdit', 'canEditMedia', 'access', 'ossatReport', 'populationMouvement', 'geojsonLayersMeta'));
    }

    /**
     * Return site GeoJSON payload for deferred map rendering.
     */
    public function geojson(Request $request, Site $site)
    {
        $user = auth()->user();

        if (!$user->hasAccessToSite($site)) {
            abort(403, 'Vous n\'avez pas accès à ce site.');
        }

        $validated = $request->validate([
            'layer' => 'nullable|integer|min:0',
            'preview' => 'nullable|boolean',
            'raw' => 'nullable|boolean',
        ]);

        $layerIndex = $validated['layer'] ?? null;
        $isPreview = filter_var($request->query('preview', false), FILTER_VALIDATE_BOOLEAN);
        $isRaw = filter_var($request->query('raw', false), FILTER_VALIDATE_BOOLEAN);

        $sourceGeojson = $site->geojson_data;
        $layerGeojson = $layerIndex !== null
            ? $this->extractGeojsonLayer($sourceGeojson, (int) $layerIndex)
            : $this->getDefaultRenderableGeojson($sourceGeojson);

        $estimatedComplexity = $this->estimateGeojsonComplexity($layerGeojson);

        $simplifyStep = $isRaw ? null : $this->determineSimplifyStep($estimatedComplexity, $isPreview);

        $cacheVersion = $site->updated_at ? $site->updated_at->timestamp : 'no-updated-at';
        $cacheLayer = $layerIndex === null ? 'all' : (string) $layerIndex;
        $cacheMode = $isRaw ? 'raw' : ($isPreview ? 'preview' : 'normal');
        $cacheBucket = $this->getGeojsonComplexityBucket($estimatedComplexity);
        $cacheKey = "site_geojson:{$site->id}:{$cacheVersion}:{$cacheLayer}:{$cacheMode}:{$cacheBucket}";

        $sourceWeight = $this->estimateGeojsonWeight($sourceGeojson);
        $freeDiskBytes = @disk_free_space(base_path());
        $hasEnoughDiskForCache = is_numeric($freeDiskBytes) ? ((int) $freeDiskBytes >= (50 * 1024 * 1024)) : true;
        $shouldCache = !$isRaw
            && $estimatedComplexity < 25000
            && $sourceWeight < 1200000
            && $hasEnoughDiskForCache;

        if ($shouldCache) {
            try {
                $payload = Cache::remember($cacheKey, now()->addMinutes(3), function () use ($site, $layerIndex, $simplifyStep, $isRaw, $isPreview) {
                    return $this->buildGeojsonPayload($site->geojson_data, $layerIndex, $simplifyStep, $isRaw, $isPreview);
                });
            } catch (Throwable $exception) {
                // Fallback sans cache si le stockage est plein ou indisponible.
                $payload = $this->buildGeojsonPayload($sourceGeojson, $layerIndex, $simplifyStep, $isRaw, $isPreview);
            }
        } else {
            $payload = $this->buildGeojsonPayload($sourceGeojson, $layerIndex, $simplifyStep, $isRaw, $isPreview);
        }

        return response()->json([
            'geojson_data' => $payload['geojson_data'] ?? null,
            'geojson_chunks' => $payload['geojson_chunks'] ?? [],
            'layers' => $this->extractGeojsonLayersMeta($sourceGeojson),
        ]);
    }

    private function buildGeojsonPayload($geojsonData, ?int $layerIndex, ?int $simplifyStep, bool $isRaw, bool $isPreview): array
    {
        if ($layerIndex !== null) {
            $geojsonData = $this->extractGeojsonLayer($geojsonData, $layerIndex);
        } else {
            $geojsonData = $this->getDefaultRenderableGeojson($geojsonData);
        }

        if (!$isRaw && $simplifyStep !== null) {
            $geojsonData = $this->simplifyGeojson($geojsonData, $simplifyStep);
        }

        if ($isRaw) {
            return [
                'geojson_data' => $geojsonData,
                'geojson_chunks' => [],
            ];
        }

        $chunks = $this->splitGeojsonForPreview($geojsonData, $isPreview);

        return [
            'geojson_data' => $chunks[0] ?? $geojsonData,
            'geojson_chunks' => $chunks,
        ];
    }

    private function extractGeojsonLayer($geojson, int $layerIndex)
    {
        $layers = $this->extractGeojsonLayers($geojson);
        return $layers[$layerIndex]['geojson'] ?? null;
    }

    private function getDefaultRenderableGeojson($geojson)
    {
        $layers = $this->extractGeojsonLayers($geojson);
        return $layers[0]['geojson'] ?? null;
    }

    private function extractGeojsonLayersMeta($geojson): array
    {
        $layers = $this->extractGeojsonLayers($geojson);

        return array_map(function ($layer, $index) {
            return [
                'index' => $index,
                'key' => $layer['key'] ?? null,
                'name' => $layer['name'],
            ];
        }, $layers, array_keys($layers));
    }

    private function extractGeojsonLayers($geojson): array
    {
        if (!is_array($geojson)) {
            return [];
        }

        $normalizedLayers = [];

        if (isset($geojson['layers']) && is_array($geojson['layers'])) {
            foreach ($geojson['layers'] as $index => $layer) {
                if (!is_array($layer)) {
                    continue;
                }

                $layerGeojson = $layer['geojson'] ?? $layer['data'] ?? null;
                if (!is_array($layerGeojson) || !isset($layerGeojson['type'])) {
                    continue;
                }

                $layerName = trim((string) ($layer['name'] ?? $layer['label'] ?? ''));
                if ($layerName === '') {
                    $layerName = 'Couche ' . ($index + 1);
                }

                $normalizedLayers[] = [
                    'key' => trim((string) ($layer['key'] ?? '')) ?: ('layer_' . $index),
                    'name' => $layerName,
                    'geojson' => $layerGeojson,
                ];
            }

            if (!empty($normalizedLayers)) {
                return $normalizedLayers;
            }
        }

        if (isset($geojson['type'])) {
            return [[
                'key' => trim((string) ($geojson['key'] ?? 'autre')) ?: 'autre',
                'name' => trim((string) ($geojson['name'] ?? 'GeoJSON')) ?: 'GeoJSON',
                'geojson' => $geojson,
            ]];
        }

        if (array_is_list($geojson)) {
            foreach ($geojson as $index => $layer) {
                if (!is_array($layer)) {
                    continue;
                }

                $layerGeojson = $layer['geojson'] ?? $layer['data'] ?? $layer;
                if (!is_array($layerGeojson) || !isset($layerGeojson['type'])) {
                    continue;
                }

                $layerName = trim((string) ($layer['name'] ?? $layer['label'] ?? ''));
                if ($layerName === '') {
                    $layerName = 'Couche ' . ($index + 1);
                }

                $normalizedLayers[] = [
                    'key' => trim((string) ($layer['key'] ?? '')) ?: ('layer_' . $index),
                    'name' => $layerName,
                    'geojson' => $layerGeojson,
                ];
            }
        }

        return $normalizedLayers;
    }

    private function estimateGeojsonWeight($geojson): int
    {
        if (!is_array($geojson)) {
            return 0;
        }

        $encoded = json_encode($geojson);
        return is_string($encoded) ? strlen($encoded) : 0;
    }

    private function splitGeojsonForPreview($geojson, bool $isPreview): array
    {
        if (!is_array($geojson)) {
            return [];
        }

        if (($geojson['type'] ?? null) !== 'FeatureCollection' || !isset($geojson['features']) || !is_array($geojson['features'])) {
            return [$geojson];
        }

        $features = $geojson['features'];
        $featureCount = count($features);
        $weight = strlen(json_encode($geojson));
        $shouldSplit = $isPreview && ($featureCount > 30 || $weight > 180000);

        if (!$shouldSplit) {
            return [$geojson];
        }

        $chunkSize = $featureCount > 500 ? 20 : 30;
        $chunks = [];

        foreach (array_chunk($features, $chunkSize) as $chunk) {
            $chunks[] = [
                'type' => 'FeatureCollection',
                'features' => array_values($chunk),
            ];
        }

        return $chunks;
    }

    private function simplifyGeojson($geojson, int $step)
    {
        if (!is_array($geojson) || $step <= 1) {
            return $geojson;
        }

        $type = $geojson['type'] ?? null;

        if ($type === 'FeatureCollection' && isset($geojson['features']) && is_array($geojson['features'])) {
            $geojson['features'] = array_map(function ($feature) use ($step) {
                return $this->simplifyGeojson($feature, $step);
            }, $geojson['features']);
            return $geojson;
        }

        if ($type === 'Feature' && isset($geojson['geometry']) && is_array($geojson['geometry'])) {
            $geojson['geometry'] = $this->simplifyGeojson($geojson['geometry'], $step);
            return $geojson;
        }

        if (!isset($geojson['coordinates']) || !is_array($geojson['coordinates'])) {
            return $geojson;
        }

        $geojson['coordinates'] = $this->simplifyCoordinates($geojson['coordinates'], $step);
        return $geojson;
    }

    private function simplifyCoordinates(array $coordinates, int $step)
    {
        if (empty($coordinates)) {
            return $coordinates;
        }

        $first = $coordinates[0];
        if (!is_array($first)) {
            return $coordinates;
        }

        if ($this->isPosition($first)) {
            return $this->decimatePositions($coordinates, $step);
        }

        return array_map(function ($item) use ($step) {
            return is_array($item)
                ? $this->simplifyCoordinates($item, $step)
                : $item;
        }, $coordinates);
    }

    private function isPosition(array $value): bool
    {
        return count($value) >= 2
            && isset($value[0], $value[1])
            && is_numeric($value[0])
            && is_numeric($value[1]);
    }

    private function decimatePositions(array $positions, int $step)
    {
        $count = count($positions);
        if ($count <= 8) {
            return $positions;
        }

        $decimated = [];
        for ($i = 0; $i < $count; $i++) {
            if ($i === 0 || $i === $count - 1 || $i % $step === 0) {
                $decimated[] = $positions[$i];
            }
        }

        if ($count >= 4 && $this->isPosition($positions[0]) && $this->isPosition($positions[$count - 1])) {
            $first = $positions[0];
            $last = end($decimated);
            if (is_array($last) && $first[0] == $positions[$count - 1][0] && $first[1] == $positions[$count - 1][1]) {
                if ($last[0] != $first[0] || $last[1] != $first[1]) {
                    $decimated[] = $first;
                }
            }
        }

        return count($decimated) >= 2 ? $decimated : $positions;
    }

    private function determineSimplifyStep(int $complexity, bool $isPreview): int
    {
        if ($complexity <= 0) {
            return $isPreview ? 4 : 6;
        }

        if ($complexity < 500) {
            return $isPreview ? 4 : 6;
        }

        if ($complexity < 2500) {
            return $isPreview ? 6 : 8;
        }

        if ($complexity < 10000) {
            return $isPreview ? 8 : 10;
        }

        return $isPreview ? 10 : 12;
    }

    private function getGeojsonComplexityBucket(int $complexity): string
    {
        if ($complexity < 500) {
            return 'tiny';
        }

        if ($complexity < 2500) {
            return 'small';
        }

        if ($complexity < 10000) {
            return 'medium';
        }

        return 'large';
    }

    private function estimateGeojsonComplexity($geojson): int
    {
        if (!is_array($geojson)) {
            return 0;
        }

        $type = $geojson['type'] ?? null;

        if ($type === 'FeatureCollection' && isset($geojson['features']) && is_array($geojson['features'])) {
            $complexity = count($geojson['features']) * 25;

            foreach ($geojson['features'] as $feature) {
                $complexity += $this->estimateGeojsonComplexity($feature);
            }

            return $complexity;
        }

        if ($type === 'Feature' && isset($geojson['geometry']) && is_array($geojson['geometry'])) {
            return 10 + $this->estimateGeojsonComplexity($geojson['geometry']);
        }

        if (isset($geojson['coordinates']) && is_array($geojson['coordinates'])) {
            return $this->countCoordinateComplexity($geojson['coordinates']);
        }

        return 0;
    }

    private function countCoordinateComplexity(array $coordinates): int
    {
        if (empty($coordinates)) {
            return 0;
        }

        $first = $coordinates[0];
        if (!is_array($first)) {
            return count($coordinates);
        }

        if ($this->isPosition($first)) {
            return count($coordinates);
        }

        $total = 0;
        foreach ($coordinates as $item) {
            if (is_array($item)) {
                $total += $this->countCoordinateComplexity($item);
            }
        }

        return $total;
    }

    /**
     * Update the specified site in storage.
     */
    public function update(Request $request, Site $site)
    {
        $user = auth()->user();
        
        // Le profil SIG peut modifier uniquement couches GeoJSON et photos.
        if ($user->isSigUser()) {
            if (!$user->canEditSiteMedia($site)) {
                abort(403, 'Vous n\'avez pas la permission de modifier ce site.');
            }

            if ($request->hasAny(['longitude', 'latitude'])) {
                abort(403, 'Le profil SIG ne peut pas modifier les coordonnees GPS.');
            }
        } elseif (!$user->canEditSite($site)) {
            abort(403, 'Vous n\'avez pas la permission de modifier ce site.');
        }

        $validated = $request->validate([
            'longitude' => 'nullable|numeric|between:-180,180',
            'latitude' => 'nullable|numeric|between:-90,90',
            'geojson_active_layer' => 'nullable|string|in:geojson_data,geojson_layer_ecole,geojson_layer_robinet,geojson_layer_lavage_main,geojson_layer_bloc_sites,geojson_layer_centre_sante',
            'geojson_active_layer_key' => 'nullable|string|max:120',
            'geojson_layer_name' => 'nullable|string|max:120',
            'geojson_data' => 'nullable|json',
            'geojson_layer_ecole' => 'nullable|json',
            'geojson_layer_robinet' => 'nullable|json',
            'geojson_layer_lavage_main' => 'nullable|json',
            'geojson_layer_bloc_sites' => 'nullable|json',
            'geojson_layer_centre_sante' => 'nullable|json',
            'photos.*' => 'nullable|image|max:5120',
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
                'geojson_layer_ecole' => ['key' => 'ecole', 'name' => 'Ecole'],
                'geojson_layer_robinet' => ['key' => 'robinet', 'name' => 'Robinet'],
                'geojson_layer_lavage_main' => ['key' => 'lavage_main', 'name' => 'Lavage main'],
                'geojson_layer_bloc_sites' => ['key' => 'bloc_sites', 'name' => 'Bloc sites'],
                'geojson_layer_centre_sante' => ['key' => 'centre_sante', 'name' => 'Centre de sante'],
                'geojson_data' => ['key' => 'autre', 'name' => 'Autre couche'],
            ];

            $activeLayerField = $request->input('geojson_active_layer');
            if (!is_string($activeLayerField) || !array_key_exists($activeLayerField, $layerDefinitions)) {
                $activeLayerField = null;
            }

            $layers = [];
            $geojsonErrors = new MessageBag();

            if ($activeLayerField !== null) {
                $existingLayers = [];
                $storedGeojson = $site->geojson_data;

                if (is_array($storedGeojson) && isset($storedGeojson['layers']) && is_array($storedGeojson['layers'])) {
                    foreach ($storedGeojson['layers'] as $index => $layerItem) {
                        if (!is_array($layerItem)) {
                            continue;
                        }

                        $layerGeojson = $layerItem['geojson'] ?? $layerItem['data'] ?? null;
                        if (!is_array($layerGeojson) || !isset($layerGeojson['type'])) {
                            continue;
                        }

                        $layerKey = trim((string) ($layerItem['key'] ?? ''));
                        if ($layerKey === '') {
                            $layerKey = 'legacy_' . $index;
                        }

                        $layerName = trim((string) ($layerItem['name'] ?? $layerItem['label'] ?? ''));
                        if ($layerName === '') {
                            $layerName = 'Couche ' . ($index + 1);
                        }

                        $existingLayers[$layerKey] = [
                            'key' => $layerKey,
                            'name' => $layerName,
                            'geojson' => $layerGeojson,
                        ];
                    }
                } elseif (is_array($storedGeojson) && isset($storedGeojson['type'])) {
                    $existingLayers['autre'] = [
                        'key' => 'autre',
                        'name' => 'Autre couche',
                        'geojson' => $storedGeojson,
                    ];
                }

                $activeDefinition = $layerDefinitions[$activeLayerField];
                $rawValue = trim((string) $request->input($activeLayerField, ''));
                $customLayerKey = trim((string) $request->input('geojson_active_layer_key', ''));
                $customLayerName = trim((string) $request->input('geojson_layer_name', ''));

                $targetLayerKey = $activeDefinition['key'];
                $targetLayerName = $activeDefinition['name'];

                if ($activeLayerField === 'geojson_data') {
                    if ($customLayerKey !== '') {
                        $targetLayerKey = $customLayerKey;
                    } elseif ($rawValue !== '') {
                        $targetLayerKey = 'custom_' . Str::uuid()->toString();
                    }

                    if ($customLayerName !== '') {
                        $targetLayerName = $customLayerName;
                    } elseif (str_starts_with($targetLayerKey, 'custom_')) {
                        $targetLayerName = 'Nouvelle couche';
                    }
                }

                if ($rawValue === '') {
                    unset($existingLayers[$targetLayerKey]);
                } else {
                    $decoded = json_decode($rawValue, true);
                    if (!is_array($decoded) || !isset($decoded['type'])) {
                        $geojsonErrors->add($activeLayerField, "La couche {$targetLayerName} n'est pas un GeoJSON valide.");
                    } elseif (!$this->isWgs84Geojson($decoded)) {
                        $geojsonErrors->add($activeLayerField, "La couche {$targetLayerName} doit etre en WGS84 (longitude/latitude). Reprojetez en EPSG:4326.");
                    } else {
                        $existingLayers[$targetLayerKey] = [
                            'key' => $targetLayerKey,
                            'name' => $targetLayerName,
                            'geojson' => $decoded,
                        ];
                    }
                }

                if ($geojsonErrors->isNotEmpty()) {
                    return redirect()->back()->withErrors($geojsonErrors)->withInput();
                }

                $layers = array_values($existingLayers);
            } else {
                $legacyDefinitions = [
                    'ecole' => ['field' => 'geojson_layer_ecole', 'name' => 'Ecole'],
                    'robinet' => ['field' => 'geojson_layer_robinet', 'name' => 'Robinet'],
                    'lavage_main' => ['field' => 'geojson_layer_lavage_main', 'name' => 'Lavage main'],
                    'bloc_sites' => ['field' => 'geojson_layer_bloc_sites', 'name' => 'Bloc sites'],
                    'centre_sante' => ['field' => 'geojson_layer_centre_sante', 'name' => 'Centre de sante'],
                ];

                foreach ($legacyDefinitions as $key => $definition) {
                    $rawValue = trim((string) $request->input($definition['field'], ''));
                    if ($rawValue === '') {
                        continue;
                    }

                    $validatedLayers = $this->parseGeojsonLayersFromField(
                        $rawValue,
                        $definition['field'],
                        $definition['name'],
                        $key,
                        $geojsonErrors
                    );

                    foreach ($validatedLayers as $validatedLayer) {
                        $layers[] = $validatedLayer;
                    }
                }

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

                if ($geojsonErrors->isNotEmpty()) {
                    return redirect()->back()->withErrors($geojsonErrors)->withInput();
                }
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

        return redirect()->route('user.sites.edit', $site)
            ->with('success', 'Site mis à jour avec succès.');
    }

    private function isValidGeojsonPayload(array $decoded): bool
    {
        if (isset($decoded['type'])) {
            return true;
        }

        if (!isset($decoded['layers']) || !is_array($decoded['layers'])) {
            return false;
        }

        foreach ($decoded['layers'] as $layer) {
            if (!is_array($layer)) {
                return false;
            }

            $layerGeojson = $layer['geojson'] ?? $layer['data'] ?? null;
            if (!is_array($layerGeojson) || !isset($layerGeojson['type'])) {
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
        
        // Vérifier l'accès et la permission d'édition média
        if (!$user->canEditSiteMedia($site)) {
            abort(403, 'Vous n\'avez pas la permission de modifier ce site.');
        }

        $request->validate([
            'photo_path' => 'required|string',
        ]);

        $photos = $site->photos ?? [];
        
        $index = array_search($request->photo_path, $photos);
        
        if ($index !== false) {
            Storage::disk('public')->delete($request->photo_path);
            unset($photos[$index]);
            $site->photos = array_values($photos);
            $site->save();
        }

        return redirect()->back()->with('success', 'Photo supprimée avec succès.');
    }
}
