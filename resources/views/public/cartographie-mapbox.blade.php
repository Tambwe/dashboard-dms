<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cartographie des sites (Mapbox) - DMS CCCM</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <x-vite-manifest-loader :assets="['resources/css/app.css', 'resources/js/app.js']" />

    <link href="https://api.mapbox.com/mapbox-gl-js/v3.4.0/mapbox-gl.css" rel="stylesheet" />

    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; font-family: 'Inter', sans-serif; background: #f8fafc; color: #0f172a; }

        #topbar {
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1rem;
            border-bottom: 1px solid #e2e8f0;
            background: #ffffff;
        }

        #layout {
            height: calc(100% - 56px);
            display: grid;
            grid-template-columns: 320px 1fr;
            overflow: hidden;
        }

        #panel {
            border-right: 1px solid #e2e8f0;
            background: #ffffff;
            overflow-y: auto;
            padding: 1rem;
        }

        #mapWrap { width: 100%; height: 100%; position: relative; }
        #map { width: 100%; height: 100%; }

        #layerControl {
            position: absolute;
            top: 0.9rem;
            right: 0.9rem;
            z-index: 20;
            width: 250px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid #e2e8f0;
            border-radius: 0.6rem;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
            padding: 0.65rem 0.7rem;
            backdrop-filter: blur(6px);
        }

        .layer-title {
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #475569;
            margin-bottom: 0.45rem;
        }

        .layer-item {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.76rem;
            color: #334155;
            margin-bottom: 0.36rem;
        }

        .layer-item:last-child { margin-bottom: 0; }

        .admin-section {
            margin-top: 0.55rem;
            padding-top: 0.5rem;
            border-top: 1px dashed #cbd5e1;
        }

        .layer-line {
            display: inline-block;
            width: 18px;
            height: 3px;
            border-radius: 2px;
            flex: 0 0 auto;
        }

        .focus-legend {
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px dashed #cbd5e1;
        }

        .focus-legend-title {
            font-size: 0.66rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 0.35rem;
        }

        .layer-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            display: inline-block;
            border: 1px solid rgba(15, 23, 42, 0.2);
            flex: 0 0 auto;
        }

        .basemap-section {
            margin-bottom: 0.55rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px dashed #cbd5e1;
        }

        .basemap-btns {
            display: flex;
            flex-wrap: wrap;
            gap: 0.3rem;
        }

        .basemap-btn {
            cursor: pointer;
            border: 1.5px solid #cbd5e1;
            border-radius: 0.4rem;
            background: #f8fafc;
            padding: 0.22rem 0.5rem;
            font-size: 0.7rem;
            color: #475569;
            font-weight: 600;
            transition: all 0.15s;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .basemap-btn:hover {
            background: #e2e8f0;
            border-color: #94a3b8;
        }

        .basemap-btn.active {
            background: #1e40af;
            border-color: #1e40af;
            color: #fff;
        }

        .label {
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            margin-bottom: 0.3rem;
        }

        .input, .select {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            padding: 0.55rem 0.7rem;
            font-size: 0.85rem;
            background: #f8fafc;
            margin-bottom: 0.75rem;
        }

        .btn {
            width: 100%;
            border: none;
            border-radius: 0.5rem;
            padding: 0.62rem 0.8rem;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }

        .btn-secondary {
            margin-top: 0.5rem;
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }

        .count-box {
            margin-bottom: 1rem;
            padding: 0.8rem;
            border-radius: 0.6rem;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
        }

        .count-num { font-size: 1.8rem; font-weight: 800; color: #1d4ed8; line-height: 1; }
        .count-label { font-size: 0.75rem; color: #1e40af; margin-top: 0.2rem; }

        .list-header {
            margin-top: 0.85rem;
            padding: 0.55rem 0.65rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.55rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
        }

        .list-header-title {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
        }

        .list-count-badge {
            font-size: 0.68rem;
            font-weight: 700;
            color: #1e3a8a;
            background: #dbeafe;
            border-radius: 999px;
            padding: 0.1rem 0.55rem;
        }

        #sitesListWrap {
            margin-top: 0.6rem;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            max-height: 34vh;
            overflow-y: auto;
            padding-right: 0.2rem;
        }

        .site-card {
            border: 1px solid #e2e8f0;
            border-radius: 0.55rem;
            background: #ffffff;
            padding: 0.55rem 0.6rem;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .site-card:hover,
        .site-card.active {
            border-color: #93c5fd;
            background: #eff6ff;
        }

        .site-name {
            font-size: 0.8rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.3;
        }

        .site-geo {
            margin-top: 0.2rem;
            font-size: 0.7rem;
            color: #64748b;
        }

        .site-meta {
            margin-top: 0.25rem;
            font-size: 0.68rem;
            color: #475569;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .site-empty {
            text-align: center;
            padding: 1rem 0.4rem;
            color: #94a3b8;
            font-size: 0.76rem;
        }

        .visible-sites-panel {
            position: absolute;
            left: 0.65rem;
            right: 0.65rem;
            bottom: 0.65rem;
            z-index: 19;
            background: rgba(255,255,255,0.95);
            border: 1px solid #dbe3ef;
            border-radius: 10px;
            box-shadow: 0 10px 24px rgba(15,23,42,0.12);
            backdrop-filter: blur(6px);
            overflow: hidden;
            max-height: 190px;
        }

        .visible-sites-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            font-size: 0.72rem;
            color: #334155;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .visible-sites-count {
            font-size: 0.68rem;
            color: #1d4ed8;
            background: #dbeafe;
            border-radius: 999px;
            padding: 2px 8px;
            font-weight: 700;
        }

        .visible-sites-body { max-height: 142px; overflow: auto; }
        .visible-sites-table { width: 100%; border-collapse: collapse; font-size: 0.72rem; color: #334155; }
        .visible-sites-table th, .visible-sites-table td { padding: 6px 8px; border-bottom: 1px solid #eef2f7; text-align: left; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .visible-sites-table th { position: sticky; top: 0; background: #f8fafc; color: #64748b; font-size: 0.66rem; text-transform: uppercase; letter-spacing: 0.04em; }
        .visible-sites-empty { padding: 12px 10px; color: #94a3b8; font-size: 0.72rem; font-style: italic; }

        .print-details { display: none; }
        .print-card-title { font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 6px; }
        .print-card-meta { font-size: 11px; color: #475569; margin-bottom: 8px; }
        .print-card-line { font-size: 11px; color: #334155; line-height: 1.45; margin-bottom: 4px; }
        .print-card-line strong { color: #0f172a; }

        .alert {
            margin: 0.8rem 0;
            padding: 0.7rem;
            border-radius: 0.5rem;
            font-size: 0.82rem;
            line-height: 1.35;
        }

        .alert-warning {
            background: #fff7ed;
            color: #9a3412;
            border: 1px solid #fed7aa;
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        @media (max-width: 960px) {
            #layout { grid-template-columns: 1fr; }
            #panel { max-height: 44vh; border-right: 0; border-bottom: 1px solid #e2e8f0; }
            #map { height: 56vh; }
            #layerControl { width: 220px; top: 0.55rem; right: 0.55rem; }
        }

        @page { size: A4 landscape; margin: 1cm; }
        #print-sites-list-mapbox { display: none; }
        @media print {
            html, body { height: auto !important; background: #fff !important; }
            #topbar, #panel, #layerControl { display: none !important; }
            #layout { display: block !important; height: auto !important; }
            #mapWrap, #map { height: 100vh !important; width: 100% !important; position: relative !important; }
            .visible-sites-panel { display: none !important; }
            .mapboxgl-ctrl-top-right,
            .mapboxgl-ctrl-bottom-right,
            .mapboxgl-ctrl-logo,
            .mapboxgl-ctrl-attrib { display: none !important; }
            .print-details { display: block !important; position: absolute !important; top: 14px !important; left: 14px !important; z-index: 9999 !important; max-width: 360px !important; background: rgba(255,255,255,0.94) !important; border: 1px solid #cbd5e1 !important; border-radius: 10px !important; box-shadow: 0 8px 24px rgba(15,23,42,0.12) !important; padding: 12px 14px !important; }
            #print-sites-list-mapbox { display: block !important; page-break-before: always; padding: 1cm 0 0 0; }
            #print-sites-list-mapbox h2 { font-size: 14px; font-weight: 800; color: #0f172a; margin: 0 0 8px 0; border-bottom: 2px solid #2563eb; padding-bottom: 6px; }
            #print-sites-list-mapbox table { width: 100%; border-collapse: collapse; font-size: 10px; }
            #print-sites-list-mapbox th { background: #1d4ed8; color: #fff; padding: 5px 8px; text-align: left; font-weight: 700; }
            #print-sites-list-mapbox td { padding: 4px 8px; border-bottom: 1px solid #e2e8f0; color: #0f172a; }
            #print-sites-list-mapbox tr:nth-child(even) td { background: #f0f7ff; }
        }
    </style>
</head>
<body>
    <header id="topbar">
        <a href="{{ url('/dashboard') }}" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit;">
            <img src="{{ asset('images/logo-dms-cccm.avif') }}" alt="Logo" style="height:32px;width:auto">
            <span style="font-weight:700">DMS CCCM</span>
        </a>
        <nav style="display:flex;align-items:center;gap:1rem;">
            <a href="{{ url('/about') }}" style="font-size:0.85rem;color:#475569;text-decoration:none;">A propos</a>
            <a href="{{ url('/profil-site') }}" style="font-size:0.85rem;color:#475569;text-decoration:none;">Profil des sites</a>
            <a href="{{ url('/cartographie') }}" style="font-size:0.85rem;color:#475569;text-decoration:none;">Cartographie Leaflet</a>
            <a href="{{ url('/cartographie-mapbox') }}" style="font-size:0.85rem;color:#2563eb;text-decoration:none;font-weight:600;">Cartographie Mapbox</a>
            <button id="btnPrintMap" type="button" style="padding:6px 10px;background:#2563eb;border:none;border-radius:8px;cursor:pointer;color:#fff;font-size:0.75rem;font-weight:600;">🖨️ Imprimer</button>
        </nav>
    </header>

    <div id="layout">
        <aside id="panel">
            <h2 style="margin:0 0 0.75rem 0;font-size:0.95rem;">Filtres cartographiques</h2>

            <div class="alert alert-danger" id="mapError" style="display:none;">
                La carte Mapbox n'a pas pu se charger. Verifiez la validite de MAPBOX_TOKEN.
            </div>

            <div class="count-box">
                <div class="count-num" id="countSites">{{ $totalSites }}</div>
                <div class="count-label">sites géolocalisés affichés</div>
            </div>

            @if (empty($mapboxToken))
                <div class="alert alert-warning">
                    MAPBOX_TOKEN est vide. La carte peut ne pas se charger.<br>
                    Configurez la variable dans le fichier .env.
                </div>
            @endif

            <label class="label" for="searchSite">Rechercher un site</label>
            <input class="input" id="searchSite" type="text" placeholder="Nom du site...">

            <label class="label" for="provinceId">Province</label>
            <select class="select" id="provinceId">
                <option value="">-- Toutes --</option>
                @foreach($provinces as $province)
                    <option value="{{ $province->id }}">{{ $province->name }}</option>
                @endforeach
            </select>

            <label class="label" for="territoireId">Territoire</label>
            <select class="select" id="territoireId">
                <option value="">-- Tous --</option>
                @foreach($territoires as $territoire)
                    <option value="{{ $territoire->id }}" data-province-id="{{ $territoire->province_id }}">{{ $territoire->name }}</option>
                @endforeach
            </select>

            <label class="label" for="zoneSante">Zone de santé</label>
            <select class="select" id="zoneSante">
                <option value="">-- Toutes --</option>
            </select>

            <label class="label" for="siteId">Site</label>
            <select class="select" id="siteId">
                <option value="">-- Tous --</option>
            </select>

            <label class="label" for="categorieSiteId">Catégorie de site</label>
            <select class="select" id="categorieSiteId">
                <option value="">-- Toutes --</option>
            </select>

            <button class="btn btn-primary" id="btnApply" type="button">Appliquer les filtres</button>
            <button class="btn btn-secondary" id="btnReset" type="button">Réinitialiser</button>

            <div class="list-header">
                <span class="list-header-title">Sites affichés</span>
                <span class="list-count-badge" id="listCountBadge">0</span>
            </div>
            <div id="sitesListWrap"></div>
        </aside>

        <div id="mapWrap">
            <div id="map"></div>
            <div id="printDetailsMap" class="print-details"></div>
            <div class="visible-sites-panel" id="visibleSitesPanelMapbox">
                <div class="visible-sites-head">
                    <span>Sites visibles sur la carte</span>
                    <span class="visible-sites-count" id="visibleSitesCountMapbox">0</span>
                </div>
                <div class="visible-sites-body">
                    <table class="visible-sites-table">
                        <thead>
                            <tr>
                                <th>Site</th>
                                <th>Province</th>
                                <th>Territoire</th>
                                <th>Catégorie</th>
                            </tr>
                        </thead>
                        <tbody id="visibleSitesTableBodyMapbox"></tbody>
                    </table>
                    <div id="visibleSitesEmptyMapbox" class="visible-sites-empty" style="display:none;">Aucun site visible dans l'emprise actuelle.</div>
                </div>
            </div>
            <div id="layerControl">
                <div class="basemap-section">
                    <div class="layer-title">Fond de carte</div>
                    <div class="basemap-btns">
                        <button class="basemap-btn active" data-style="blank" onclick="setBasemap('blank', this)">∅ Sans fond</button>
                        <button class="basemap-btn" data-style="streets" onclick="setBasemap('streets', this)">🗺️ Rues</button>
                        <button class="basemap-btn" data-style="satellite" onclick="setBasemap('satellite', this)">🛰️ Satellite</button>
                        <button class="basemap-btn" data-style="outdoors" onclick="setBasemap('outdoors', this)">⛰️ Relief</button>
                        <button class="basemap-btn" data-style="dark" onclick="setBasemap('dark', this)">🌙 Sombre</button>
                        <button class="basemap-btn" data-style="light" onclick="setBasemap('light', this)">☀️ Clair</button>
                    </div>
                </div>
                <label class="layer-item" style="display:none"><input type="checkbox" id="layerClusters" checked> Clusters</label>
                <label class="layer-item" style="display:none"><input type="checkbox" id="layerPoints" checked> Points</label>
                <label class="layer-item" style="display:none"><input type="checkbox" id="layerPolygonsPdi" checked> <span class="layer-dot" style="background:#f59e0b"></span> Polygones PDI</label>
                <label class="layer-item" style="display:none"><input type="checkbox" id="layerPolygonsSousGestion" checked> <span class="layer-dot" style="background:#2563eb"></span> Polygones sous gestion</label>
                <label class="layer-item" style="display:none"><input type="checkbox" id="layerPolygonsHorsGestion" checked> <span class="layer-dot" style="background:#7c3aed"></span> Polygones hors gestion</label>

                <div class="admin-section">
                    <div class="layer-title">Limites administratives</div>
                    <label class="layer-item"><input type="checkbox" id="layerAdmin0"> <span class="layer-line" style="background:#1e293b"></span> Pays</label>
                    <label class="layer-item"><input type="checkbox" id="layerAdmin1" checked> <span class="layer-line" style="background:#dc2626"></span> Provinces</label>
                    <label class="layer-item"><input type="checkbox" id="layerAdmin2"> <span class="layer-line" style="background:#d97706"></span> Territoires</label>
                    <label class="layer-item"><input type="checkbox" id="layerAdmin3"> <span class="layer-line" style="background:#7c3aed"></span> Zones de santé</label>
                </div>

                <div class="focus-legend">
                    <div class="focus-legend-title">Niveau de focus</div>
                    <div class="layer-item"><span class="layer-dot" style="background:#2563eb"></span> Province</div>
                    <div class="layer-item"><span class="layer-dot" style="background:#7c3aed"></span> Territoire</div>
                    <div class="layer-item"><span class="layer-dot" style="background:#0ea5e9"></span> Zone de santé</div>
                    <div class="layer-item"><span class="layer-dot" style="background:#f59e0b"></span> Site</div>
                </div>
            </div>
        </div>
    </div>

    {{-- LISTE DES SITES POUR L'IMPRESSION --}}
    <div id="print-sites-list-mapbox">
        <h2>Liste des sites concernés</h2>
        <table>
            <thead><tr><th>#</th><th>Nom du site</th><th>Province</th><th>Territoire</th><th>Zone de santé</th><th>Catégorie</th><th>PDI</th><th>Ménages</th></tr></thead>
            <tbody id="print-sites-tbody-mapbox"></tbody>
        </table>
    </div>

    <script src="https://api.mapbox.com/mapbox-gl-js/v3.4.0/mapbox-gl.js"></script>
    <script>
        (function init() {
            var mapboxToken = @json($mapboxToken ?? '');
            var initialCenter = [25.5, -2.5];
            var initialZoom = 5.8;

            var territoireSelect = document.getElementById('territoireId');
            var provinceSelect = document.getElementById('provinceId');
            var categorieSelect = document.getElementById('categorieSiteId');
            var zoneSanteSelect = document.getElementById('zoneSante');
            var siteSelect = document.getElementById('siteId');
            var searchInput = document.getElementById('searchSite');
            var countSites = document.getElementById('countSites');
            var listCountBadge = document.getElementById('listCountBadge');
            var sitesListWrap = document.getElementById('sitesListWrap');
            var mapError = document.getElementById('mapError');
            var layerClustersCheckbox = document.getElementById('layerClusters');
            var layerPointsCheckbox = document.getElementById('layerPoints');
            var layerPolygonsPdiCheckbox = document.getElementById('layerPolygonsPdi');
            var layerPolygonsSousGestionCheckbox = document.getElementById('layerPolygonsSousGestion');
            var layerPolygonsHorsGestionCheckbox = document.getElementById('layerPolygonsHorsGestion');

            var polygonCategoryLayers = {
                pdi: { fill: 'site-polygons-pdi-fill', line: 'site-polygons-pdi-line', value: 'PDIs EN COMMUNAUTÉS HÔTES' },
                sousGestion: { fill: 'site-polygons-sous-gestion-fill', line: 'site-polygons-sous-gestion-line', value: 'SITES SOUS GESTION' },
                horsGestion: { fill: 'site-polygons-hors-gestion-fill', line: 'site-polygons-hors-gestion-line', value: 'SITES HORS GESTION' }
            };

            if (!mapboxToken) {
                document.getElementById('map').innerHTML = '<div style="padding:1rem;color:#9a3412;font-size:0.9rem;">Token Mapbox manquant. Définissez MAPBOX_TOKEN dans .env puis rechargez la page.</div>';
                return;
            }

            mapboxgl.accessToken = mapboxToken;
            var blankBasemapStyle = {
                version: 8,
                glyphs: 'mapbox://fonts/mapbox/{fontstack}/{range}.pbf',
                sources: {},
                layers: [
                    {
                        id: 'blank-background',
                        type: 'background',
                        paint: {
                            'background-color': '#ffffff'
                        }
                    }
                ]
            };
            var map = new mapboxgl.Map({
                container: 'map',
                style: blankBasemapStyle,
                center: initialCenter,
                zoom: initialZoom
            });

            map.addControl(new mapboxgl.NavigationControl(), 'bottom-right');
            var popup = new mapboxgl.Popup({ closeButton: false, closeOnClick: false });

            var sourceReady = false;
            var allSites = [];
            var currentFilteredSites = [];
            var activeSiteCard = null;

            function populateTerritoires(items) {
                territoireSelect.innerHTML = '<option value="">-- Tous --</option>';
                (Array.isArray(items) ? items : []).forEach(function(item) {
                    var opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.name;
                    territoireSelect.appendChild(opt);
                });
            }

            function fetchTerritoires() {
                if (!provinceSelect.value) {
                    populateTerritoires(@json($territoires->map(fn($territoire) => ['id' => $territoire->id, 'name' => $territoire->name, 'province_id' => $territoire->province_id])->values()));
                    territoireSelect.value = '';
                    return Promise.resolve();
                }

                territoireSelect.innerHTML = '<option value="">Chargement...</option>';

                return fetch('/api/geographic/territoires?province_id=' + encodeURIComponent(provinceSelect.value))
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        populateTerritoires(data);
                    })
                    .catch(function(err) {
                        console.error(err);
                        populateTerritoires([]);
                    });
            }

            provinceSelect.addEventListener('change', function() {
                territoireSelect.value = '';
                zoneSanteSelect.value = '';
                siteSelect.value = '';
                fetchTerritoires().then(function() {
                    fetchSites();
                });
            });

            function buildUrl() {
                var params = new URLSearchParams();
                if (provinceSelect.value) params.set('province_id', provinceSelect.value);
                if (territoireSelect.value) params.set('territoire_id', territoireSelect.value);
                if (categorieSelect.value) params.set('categorie_site_id', categorieSelect.value);
                if (zoneSanteSelect.value) params.set('zone_sante', zoneSanteSelect.value);
                if (siteSelect.value) params.set('site_id', siteSelect.value);
                return '/api/geographic/sites-coordinates?' + params.toString();
            }

            function resetSiteOptions() {
                siteSelect.innerHTML = '<option value="">-- Tous --</option>';
            }

            function resetZoneOptions() {
                zoneSanteSelect.innerHTML = '<option value="">-- Toutes --</option>';
            }

            function populateZoneAndSiteOptions(sites) {
                var previousZone = zoneSanteSelect.value;
                var previousSite = siteSelect.value;

                var zoneSet = new Set();
                var siteRows = [];

                (Array.isArray(sites) ? sites : []).forEach(function(site) {
                    var zone = String(site.zone_sante || '').trim();
                    if (zone) zoneSet.add(zone);

                    if (site.id) {
                        siteRows.push({
                            id: String(site.id),
                            name: String(site.nom || ('Site #' + site.id))
                        });
                    }
                });

                resetZoneOptions();
                Array.from(zoneSet)
                    .sort(function(a, b) { return a.localeCompare(b, 'fr'); })
                    .forEach(function(zone) {
                        var opt = document.createElement('option');
                        opt.value = zone;
                        opt.textContent = zone;
                        zoneSanteSelect.appendChild(opt);
                    });

                resetSiteOptions();
                siteRows
                    .sort(function(a, b) { return a.name.localeCompare(b.name, 'fr'); })
                    .forEach(function(item) {
                        var opt = document.createElement('option');
                        opt.value = item.id;
                        opt.textContent = item.name;
                        siteSelect.appendChild(opt);
                    });

                if (previousZone && Array.from(zoneSanteSelect.options).some(function(o) { return o.value === previousZone; })) {
                    zoneSanteSelect.value = previousZone;
                }

                if (previousSite && Array.from(siteSelect.options).some(function(o) { return o.value === previousSite; })) {
                    siteSelect.value = previousSite;
                }
            }

            function toFeature(site) {
                var latitude = parseFloat(site.latitude);
                var longitude = parseFloat(site.longitude);
                var categoryName = site.categorie_site ? site.categorie_site.name : '-';
                var markerIcon = '📍';

                if (categoryName === 'SITES SOUS GESTION') {
                    markerIcon = '🏕️';
                } else if (categoryName === 'SITES HORS GESTION') {
                    markerIcon = '📌';
                } else if (categoryName === 'PDIs EN COMMUNAUTÉS HÔTES') {
                    markerIcon = '🏠';
                }

                return {
                    type: 'Feature',
                    geometry: {
                        type: 'Point',
                        coordinates: [longitude, latitude]
                    },
                    properties: {
                        id: site.id,
                        name: site.nom || '-',
                        code: site.code_site || '-',
                        province: site.province || '-',
                        territoire: site.territoire || '-',
                        zone_sante: site.zone_sante || '-',
                        category: categoryName,
                        marker_icon: markerIcon,
                        menages: site.menages || 0,
                        individus: site.individus || 0
                    }
                };
            }

            function parseGeojsonData(geojsonData) {
                if (!geojsonData) return null;

                if (typeof geojsonData === 'string') {
                    try {
                        return JSON.parse(geojsonData);
                    } catch (error) {
                        return null;
                    }
                }

                if (typeof geojsonData === 'object') {
                    return geojsonData;
                }

                return null;
            }

            function extractPolygonGeometries(geojson) {
                if (!geojson || typeof geojson !== 'object') return [];

                if (geojson.type === 'Polygon' || geojson.type === 'MultiPolygon') {
                    return [geojson];
                }

                if (geojson.type === 'Feature' && geojson.geometry) {
                    return extractPolygonGeometries(geojson.geometry);
                }

                if (geojson.type === 'FeatureCollection' && Array.isArray(geojson.features)) {
                    return geojson.features.flatMap(function(feature) {
                        return extractPolygonGeometries(feature);
                    });
                }

                return [];
            }

            function buildPolygonFeatures(sites) {
                var polygonFeatures = [];

                (Array.isArray(sites) ? sites : []).forEach(function(site) {
                    var geojson = parseGeojsonData(site.geojson_data);
                    if (!geojson) return;

                    var polygons = extractPolygonGeometries(geojson);
                    if (!polygons.length) return;

                    var siteFeature = toFeature(site);
                    polygons.forEach(function(geometry) {
                        polygonFeatures.push({
                            type: 'Feature',
                            geometry: geometry,
                            properties: Object.assign({}, siteFeature.properties)
                        });
                    });
                });

                return polygonFeatures;
            }

            function extendBoundsWithCoordinates(bounds, coordinates) {
                if (!Array.isArray(coordinates) || !coordinates.length) return;

                if (typeof coordinates[0] === 'number' && typeof coordinates[1] === 'number') {
                    bounds.extend([coordinates[0], coordinates[1]]);
                    return;
                }

                coordinates.forEach(function(child) {
                    extendBoundsWithCoordinates(bounds, child);
                });
            }

            function getSitePolygonBounds(site) {
                var geojson = parseGeojsonData(site.geojson_data);
                if (!geojson) return null;

                var polygons = extractPolygonGeometries(geojson);
                if (!polygons.length) return null;

                var bounds = new mapboxgl.LngLatBounds();
                polygons.forEach(function(geometry) {
                    extendBoundsWithCoordinates(bounds, geometry.coordinates);
                });

                if (!bounds.isEmpty()) {
                    return bounds;
                }

                return null;
            }

            function getGeometryBounds(geometry) {
                if (!geometry || !geometry.coordinates) return null;

                var bounds = new mapboxgl.LngLatBounds();
                extendBoundsWithCoordinates(bounds, geometry.coordinates);

                if (!bounds.isEmpty()) {
                    return bounds;
                }

                return null;
            }

            function buildBoundsPolygonFromPoints(features) {
                if (!Array.isArray(features) || !features.length) return null;

                var bounds = new mapboxgl.LngLatBounds();
                features.forEach(function(feature) {
                    if (feature && feature.geometry && Array.isArray(feature.geometry.coordinates)) {
                        bounds.extend(feature.geometry.coordinates);
                    }
                });

                if (bounds.isEmpty()) return null;

                var sw = bounds.getSouthWest();
                var ne = bounds.getNorthEast();
                var minLng = sw.lng;
                var minLat = sw.lat;
                var maxLng = ne.lng;
                var maxLat = ne.lat;

                // Evite un polygone dégénéré quand les points sont quasi identiques.
                if (Math.abs(maxLng - minLng) < 0.001) {
                    minLng -= 0.003;
                    maxLng += 0.003;
                }
                if (Math.abs(maxLat - minLat) < 0.001) {
                    minLat -= 0.003;
                    maxLat += 0.003;
                }

                return {
                    type: 'Feature',
                    geometry: {
                        type: 'Polygon',
                        coordinates: [[
                            [minLng, minLat],
                            [maxLng, minLat],
                            [maxLng, maxLat],
                            [minLng, maxLat],
                            [minLng, minLat]
                        ]]
                    },
                    properties: {
                        label: 'focus-area'
                    }
                };
            }

            function getFocusLevel() {
                if (siteSelect.value) return 'site';
                if (zoneSanteSelect.value) return 'zone';
                if (territoireSelect.value) return 'territoire';
                if (provinceSelect.value) return 'province';
                return null;
            }

            function getFocusStyle(level) {
                var styles = {
                    province: { fill: '#2563eb', line: '#1d4ed8', opacity: 0.11, width: 2.2 },
                    territoire: { fill: '#7c3aed', line: '#6d28d9', opacity: 0.13, width: 2.4 },
                    zone: { fill: '#0ea5e9', line: '#0369a1', opacity: 0.15, width: 2.6 },
                    site: { fill: '#f59e0b', line: '#b45309', opacity: 0.18, width: 2.8 }
                };

                return styles[level] || { fill: '#14b8a6', line: '#0f766e', opacity: 0.13, width: 2.2 };
            }

            function buildFocusAreaFeatures(filteredSites, filteredFeatures, polygonFeatures) {
                if (!Array.isArray(filteredSites) || !filteredSites.length) {
                    return [];
                }

                var hasContextFilter = !!(
                    provinceSelect.value
                    || territoireSelect.value
                    || zoneSanteSelect.value
                    || siteSelect.value
                );

                if (!hasContextFilter) {
                    return [];
                }

                var level = getFocusLevel();
                var style = getFocusStyle(level);

                if (Array.isArray(polygonFeatures) && polygonFeatures.length) {
                    return polygonFeatures.map(function(f) {
                        return {
                            type: 'Feature',
                            geometry: f.geometry,
                            properties: {
                                label: 'focus-area',
                                focus_level: level || 'default',
                                fill_color: style.fill,
                                line_color: style.line,
                                fill_opacity: style.opacity,
                                line_width: style.width
                            }
                        };
                    });
                }

                var bboxFeature = buildBoundsPolygonFromPoints(filteredFeatures);
                if (bboxFeature) {
                    bboxFeature.properties = {
                        label: 'focus-area',
                        focus_level: level || 'default',
                        fill_color: style.fill,
                        line_color: style.line,
                        fill_opacity: style.opacity,
                        line_width: style.width
                    };
                }
                return bboxFeature ? [bboxFeature] : [];
            }

            function esc(value) {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/\"/g, '&quot;');
            }

            function fmt(value) {
                var n = Number(value || 0);
                return n.toLocaleString('fr-FR');
            }

            function makePopupHtml(feature) {
                var p = feature.properties;
                var profileUrl = '/profil-site/' + encodeURIComponent(String(p.id || ''));
                return '<div style="font-family:Inter,sans-serif;font-size:12px;line-height:1.4;">'
                    + '<strong style="font-size:13px;">' + esc(p.name) + '</strong><br>'
                    + '<span>' + esc(p.province) + ' / ' + esc(p.territoire) + '</span><br>'
                    + '<span>Zone: ' + esc(p.zone_sante) + '</span><br>'
                    + '<span>Catégorie: ' + esc(p.category) + '</span><br>'
                    + '<span>Ménages: ' + fmt(p.menages) + ' · Individus: ' + fmt(p.individus) + '</span><br>'
                    + '<a href="' + profileUrl + '" target="_blank" rel="noopener" onclick="event.stopPropagation()" onmousedown="event.stopPropagation()" style="display:inline-block;margin-top:6px;padding:4px 8px;background:#2563eb;color:#fff;border-radius:6px;text-decoration:none;font-size:11px;font-weight:600;">Voir le profil du site</a>'
                    + '</div>';
            }

            function applyClientSearch(sites) {
                var q = (searchInput.value || '').trim().toLowerCase();
                if (!q) return sites;
                return sites.filter(function(site) {
                    return String(site.nom || '').toLowerCase().includes(q)
                        || String(site.code_site || '').toLowerCase().includes(q)
                        || String(site.province || '').toLowerCase().includes(q)
                        || String(site.territoire || '').toLowerCase().includes(q)
                        || String(site.zone_sante || '').toLowerCase().includes(q);
                });
            }

            function renderSitesList(sites, features) {
                sitesListWrap.innerHTML = '';
                activeSiteCard = null;

                if (!features.length) {
                    sitesListWrap.innerHTML = '<div class="site-empty">Aucun site trouvé.</div>';
                    return;
                }

                features.forEach(function(feature, index) {
                    var site = sites[index];
                    var p = feature.properties;
                    var card = document.createElement('div');
                    card.className = 'site-card';
                    card.innerHTML =
                        '<div class="site-name">' + esc(p.name) + '</div>'
                        + '<div class="site-geo">' + esc(p.province) + ' / ' + esc(p.territoire) + ' / ' + esc(p.zone_sante) + '</div>'
                        + '<div class="site-meta">'
                        + '<span>Catégorie: ' + esc(p.category) + '</span>'
                        + '<span>Ménages: ' + fmt(p.menages) + '</span>'
                        + '<span>Individus: ' + fmt(p.individus) + '</span>'
                        + (site && site.geojson_data ? '<span>GeoJSON</span>' : '')
                        + '</div>';

                    card.addEventListener('click', function() {
                        if (activeSiteCard) activeSiteCard.classList.remove('active');
                        activeSiteCard = card;
                        activeSiteCard.classList.add('active');

                        var polygonBounds = site ? getSitePolygonBounds(site) : null;
                        if (polygonBounds && !polygonBounds.isEmpty()) {
                            map.fitBounds(polygonBounds, { padding: 50, duration: 500, maxZoom: 14 });
                        } else {
                            map.easeTo({ center: feature.geometry.coordinates, zoom: 13 });
                        }

                        popup
                            .setLngLat(feature.geometry.coordinates)
                            .setHTML(makePopupHtml(feature))
                            .addTo(map);
                    });

                    sitesListWrap.appendChild(card);
                });
            }

            function renderFeatures() {
                var filteredSites = applyClientSearch(allSites);
                currentFilteredSites = filteredSites;
                var filteredFeatures = filteredSites.map(toFeature);
                var polygonFeatures = buildPolygonFeatures(filteredSites);
                var focusAreaFeatures = buildFocusAreaFeatures(filteredSites, filteredFeatures, polygonFeatures);

                countSites.textContent = filteredFeatures.length;
                listCountBadge.textContent = filteredFeatures.length;
                renderSitesList(filteredSites, filteredFeatures);

                var fc = { type: 'FeatureCollection', features: filteredFeatures };
                var polygonFc = { type: 'FeatureCollection', features: polygonFeatures };
                var focusFc = { type: 'FeatureCollection', features: focusAreaFeatures };
                if (!sourceReady) return;

                map.getSource('sites').setData(fc);
                map.getSource('site-polygons').setData(polygonFc);
                map.getSource('focus-area').setData(focusFc);

                if (filteredFeatures.length || polygonFeatures.length) {
                    var bounds = new mapboxgl.LngLatBounds();
                    filteredFeatures.forEach(function(f) { bounds.extend(f.geometry.coordinates); });
                    polygonFeatures.forEach(function(f) {
                        extendBoundsWithCoordinates(bounds, f.geometry.coordinates);
                    });

                    if (!bounds.isEmpty()) {
                        map.fitBounds(bounds, { padding: 40, duration: 450, maxZoom: 13 });
                    }
                }
            }

            function fetchSites() {
                fetch(buildUrl())
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        populateZoneAndSiteOptions(data);
                        allSites = Array.isArray(data) ? data : [];
                        renderFeatures();
                    })
                    .catch(function(err) {
                        console.error(err);
                    });
            }

            function fetchCategories() {
                fetch('/api/geographic/categories-sites')
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        (Array.isArray(data) ? data : []).forEach(function(item) {
                            var opt = document.createElement('option');
                            opt.value = item.id;
                            opt.textContent = item.name;
                            categorieSelect.appendChild(opt);
                        });
                    })
                    .catch(function(err) {
                        console.error(err);
                    });
            }

            function setLayerVisibility(layerId, visible) {
                if (!map.getLayer(layerId)) return;
                map.setLayoutProperty(layerId, 'visibility', visible ? 'visible' : 'none');
            }

            function getSelectedText(select, fallback) {
                if (!select || !select.value) return fallback;
                return select.options[select.selectedIndex] ? select.options[select.selectedIndex].textContent.trim() : fallback;
            }

            function getVisibleAdminLayerNames() {
                var names = [];
                if (document.getElementById('layerAdmin1').checked) names.push('Provinces');
                if (document.getElementById('layerAdmin2').checked) names.push('Territoires');
                if (document.getElementById('layerAdmin3').checked) names.push('Zones de sante');
                if (document.getElementById('layerAdmin0').checked) names.push('Pays');
                return names.length ? names.join(', ') : 'Aucune';
            }

            function updatePrintDetails() {
                var printBox = document.getElementById('printDetailsMap');
                if (!printBox) return;

                var activeBasemap = document.querySelector('.basemap-btn.active');
                var lines = [];
                lines.push('<div class="print-card-title">Cartographie des sites - Mapbox</div>');
                lines.push('<div class="print-card-meta">Imprime le ' + new Date().toLocaleString('fr-FR') + '</div>');
                lines.push('<div class="print-card-line"><strong>Sites affiches :</strong> ' + esc(countSites.textContent.trim() || '0') + '</div>');
                lines.push('<div class="print-card-line"><strong>Recherche :</strong> ' + esc((searchInput.value || '').trim() || 'Aucune') + '</div>');
                lines.push('<div class="print-card-line"><strong>Province :</strong> ' + esc(getSelectedText(provinceSelect, 'Toutes')) + '</div>');
                lines.push('<div class="print-card-line"><strong>Territoire :</strong> ' + esc(getSelectedText(territoireSelect, 'Tous')) + '</div>');
                lines.push('<div class="print-card-line"><strong>Zone de sante :</strong> ' + esc(getSelectedText(zoneSanteSelect, 'Toutes')) + '</div>');
                lines.push('<div class="print-card-line"><strong>Site :</strong> ' + esc(getSelectedText(siteSelect, 'Tous')) + '</div>');
                lines.push('<div class="print-card-line"><strong>Categorie :</strong> ' + esc(getSelectedText(categorieSelect, 'Toutes')) + '</div>');
                lines.push('<div class="print-card-line"><strong>Fond :</strong> ' + esc(activeBasemap ? activeBasemap.textContent.trim() : 'Inconnu') + '</div>');
                lines.push('<div class="print-card-line"><strong>Limites visibles :</strong> ' + esc(getVisibleAdminLayerNames()) + '</div>');
                printBox.innerHTML = lines.join('');

                // Remplir la liste des sites
                var tbody = document.getElementById('print-sites-tbody-mapbox');
                if (tbody) {
                    tbody.innerHTML = '';
                    var sites = currentFilteredSites;
                    if (!sites || !sites.length) {
                        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#94a3b8">Aucun site</td></tr>';
                    } else {
                        sites.forEach(function(s, i) {
                            var tr = document.createElement('tr');
                            tr.innerHTML =
                                '<td>' + (i + 1) + '</td>'
                                + '<td>' + esc(s.nom || '-') + '</td>'
                                + '<td>' + esc(s.province || '-') + '</td>'
                                + '<td>' + esc(s.territoire || '-') + '</td>'
                                + '<td>' + esc(s.commune || s.zone_sante || '-') + '</td>'
                                + '<td>' + esc((s.categorie_site && s.categorie_site.name) || '-') + '</td>'
                                + '<td>' + (s.individus ? Number(s.individus).toLocaleString('fr-FR') : '-') + '</td>'
                                + '<td>' + (s.menages ? Number(s.menages).toLocaleString('fr-FR') : '-') + '</td>';
                            tbody.appendChild(tr);
                        });
                    }
                }
            }

            function bindLayerControls() {
                layerClustersCheckbox.addEventListener('change', function() {
                    setLayerVisibility('clusters', layerClustersCheckbox.checked);
                    setLayerVisibility('cluster-count', layerClustersCheckbox.checked);
                });

                layerPointsCheckbox.addEventListener('change', function() {
                    setLayerVisibility('unclustered-point', layerPointsCheckbox.checked);
                    setLayerVisibility('unclustered-point-icon', layerPointsCheckbox.checked);
                });

                layerPolygonsPdiCheckbox.addEventListener('change', function() {
                    var cfg = polygonCategoryLayers.pdi;
                    setLayerVisibility(cfg.fill, layerPolygonsPdiCheckbox.checked);
                    setLayerVisibility(cfg.line, layerPolygonsPdiCheckbox.checked);
                });

                layerPolygonsSousGestionCheckbox.addEventListener('change', function() {
                    var cfg = polygonCategoryLayers.sousGestion;
                    setLayerVisibility(cfg.fill, layerPolygonsSousGestionCheckbox.checked);
                    setLayerVisibility(cfg.line, layerPolygonsSousGestionCheckbox.checked);
                });

                layerPolygonsHorsGestionCheckbox.addEventListener('change', function() {
                    var cfg = polygonCategoryLayers.horsGestion;
                    setLayerVisibility(cfg.fill, layerPolygonsHorsGestionCheckbox.checked);
                    setLayerVisibility(cfg.line, layerPolygonsHorsGestionCheckbox.checked);
                });

                ['admin0','admin1','admin2','admin3'].forEach(function(lvl) {
                    var cb = document.getElementById('layerAdmin' + lvl.replace('admin',''));
                    if (!cb) return;
                    cb.addEventListener('change', function() {
                        setLayerVisibility('admin-fill-' + lvl, cb.checked);
                        setLayerVisibility('admin-line-' + lvl, cb.checked);
                        if (lvl === 'admin1') setLayerVisibility('admin1-labels', cb.checked);
                        if (lvl === 'admin2') setLayerVisibility('admin2-labels', cb.checked);
                    });
                });
            }

            map.on('error', function(event) {
                if (event && event.error) {
                    console.error(event.error);
                    mapError.style.display = 'block';
                }
            });

            var basemapStyles = {
                'blank':     blankBasemapStyle,
                'streets':   'mapbox://styles/mapbox/streets-v12',
                'satellite': 'mapbox://styles/mapbox/satellite-streets-v12',
                'outdoors':  'mapbox://styles/mapbox/outdoors-v12',
                'dark':      'mapbox://styles/mapbox/dark-v11',
                'light':     'mapbox://styles/mapbox/light-v11'
            };

            function addSourcesAndLayers() {
                map.addSource('sites', {
                    type: 'geojson',
                    data: { type: 'FeatureCollection', features: [] },
                    cluster: true,
                    clusterMaxZoom: 14,
                    clusterRadius: 44
                });

                map.addSource('site-polygons', {
                    type: 'geojson',
                    data: { type: 'FeatureCollection', features: [] }
                });

                map.addSource('focus-area', {
                    type: 'geojson',
                    data: { type: 'FeatureCollection', features: [] }
                });

                map.addSource('cod-admin0', { type: 'geojson', data: '/geojson/cod_admin0.geojson' });
                map.addSource('cod-admin1', { type: 'geojson', data: '/geojson/cod_admin1.geojson' });
                map.addSource('cod-admin2', { type: 'geojson', data: '/geojson/cod_admin2.geojson' });
                map.addSource('cod-admin3', { type: 'geojson', data: '/geojson/cod_admin3.geojson' });

                // Admin boundaries layers (added first so they appear below sites)
                [
                    { id: 'admin3', src: 'cod-admin3', color: '#7c3aed', width: 0.6, opacity: 0.7, fill: 0.02, visible: false },
                    { id: 'admin2', src: 'cod-admin2', color: '#d97706', width: 1,   opacity: 0.8, fill: 0.03, visible: false },
                    { id: 'admin1', src: 'cod-admin1', color: '#dc2626', width: 1.8, opacity: 0.9, fill: 0.04, visible: true  },
                    { id: 'admin0', src: 'cod-admin0', color: '#1e293b', width: 2.5, opacity: 1,   fill: 0,    visible: false }
                ].forEach(function(cfg) {
                    map.addLayer({
                        id: 'admin-fill-' + cfg.id,
                        type: 'fill',
                        source: cfg.src,
                        paint: { 'fill-color': cfg.color, 'fill-opacity': cfg.fill },
                        layout: { visibility: cfg.visible ? 'visible' : 'none' }
                    });
                    map.addLayer({
                        id: 'admin-line-' + cfg.id,
                        type: 'line',
                        source: cfg.src,
                        paint: { 'line-color': cfg.color, 'line-width': cfg.width, 'line-opacity': cfg.opacity },
                        layout: { visibility: cfg.visible ? 'visible' : 'none' }
                    });
                });

                // Admin label layers
                map.addLayer({
                    id: 'admin1-labels',
                    type: 'symbol',
                    source: 'cod-admin1',
                    layout: {
                        'text-field': ['get', 'adm1_name'],
                        'text-size': 11,
                        'text-font': ['Open Sans Bold', 'Arial Unicode MS Bold'],
                        'text-max-width': 8,
                        visibility: 'visible'
                    },
                    paint: {
                        'text-color': '#7f1d1d',
                        'text-halo-color': '#fff',
                        'text-halo-width': 1.5,
                        'text-opacity': 0.9
                    }
                });

                map.addLayer({
                    id: 'admin2-labels',
                    type: 'symbol',
                    source: 'cod-admin2',
                    minzoom: 7.5,
                    layout: {
                        'text-field': ['get', 'adm2_name'],
                        'text-size': [
                            'interpolate', ['linear'], ['zoom'],
                            7.5, 9,
                            10, 11,
                            12, 12.5
                        ],
                        'text-font': ['Open Sans Semibold', 'Arial Unicode MS Regular'],
                        'text-max-width': 7,
                        visibility: 'none'
                    },
                    paint: {
                        'text-color': '#9a3412',
                        'text-halo-color': '#fff7ed',
                        'text-halo-width': 1.2,
                        'text-opacity': 0.88
                    }
                });

                map.addLayer({
                    id: 'focus-area-fill',
                    type: 'fill',
                    source: 'focus-area',
                    paint: {
                        'fill-color': ['coalesce', ['get', 'fill_color'], '#14b8a6'],
                        'fill-opacity': ['coalesce', ['get', 'fill_opacity'], 0.13]
                    }
                });

                map.addLayer({
                    id: 'focus-area-line',
                    type: 'line',
                    source: 'focus-area',
                    paint: {
                        'line-color': ['coalesce', ['get', 'line_color'], '#0f766e'],
                        'line-width': ['coalesce', ['get', 'line_width'], 2.2],
                        'line-opacity': 0.9
                    }
                });

                map.addLayer({
                    id: polygonCategoryLayers.pdi.fill,
                    type: 'fill',
                    source: 'site-polygons',
                    filter: ['==', ['get', 'category'], polygonCategoryLayers.pdi.value],
                    paint: {
                        'fill-color': '#f59e0b',
                        'fill-opacity': 0.18
                    }
                });

                map.addLayer({
                    id: polygonCategoryLayers.pdi.line,
                    type: 'line',
                    source: 'site-polygons',
                    filter: ['==', ['get', 'category'], polygonCategoryLayers.pdi.value],
                    paint: {
                        'line-color': '#d97706',
                        'line-width': 2,
                        'line-opacity': 0.9
                    }
                });

                map.addLayer({
                    id: polygonCategoryLayers.sousGestion.fill,
                    type: 'fill',
                    source: 'site-polygons',
                    filter: ['==', ['get', 'category'], polygonCategoryLayers.sousGestion.value],
                    paint: {
                        'fill-color': '#2563eb',
                        'fill-opacity': 0.18
                    }
                });

                map.addLayer({
                    id: polygonCategoryLayers.sousGestion.line,
                    type: 'line',
                    source: 'site-polygons',
                    filter: ['==', ['get', 'category'], polygonCategoryLayers.sousGestion.value],
                    paint: {
                        'line-color': '#1d4ed8',
                        'line-width': 2,
                        'line-opacity': 0.9
                    }
                });

                map.addLayer({
                    id: polygonCategoryLayers.horsGestion.fill,
                    type: 'fill',
                    source: 'site-polygons',
                    filter: ['==', ['get', 'category'], polygonCategoryLayers.horsGestion.value],
                    paint: {
                        'fill-color': '#7c3aed',
                        'fill-opacity': 0.18
                    }
                });

                map.addLayer({
                    id: polygonCategoryLayers.horsGestion.line,
                    type: 'line',
                    source: 'site-polygons',
                    filter: ['==', ['get', 'category'], polygonCategoryLayers.horsGestion.value],
                    paint: {
                        'line-color': '#6d28d9',
                        'line-width': 2,
                        'line-opacity': 0.9
                    }
                });

                map.addLayer({
                    id: 'clusters',
                    type: 'circle',
                    source: 'sites',
                    filter: ['has', 'point_count'],
                    paint: {
                        'circle-color': '#2563eb',
                        'circle-radius': [
                            'step', ['get', 'point_count'],
                            18, 20, 22, 80, 28
                        ],
                        'circle-opacity': 0.85
                    }
                });

                map.addLayer({
                    id: 'cluster-count',
                    type: 'symbol',
                    source: 'sites',
                    filter: ['has', 'point_count'],
                    layout: {
                        'text-field': ['get', 'point_count_abbreviated'],
                        'text-size': 12
                    },
                    paint: { 'text-color': '#ffffff' }
                });

                map.addLayer({
                    id: 'unclustered-point',
                    type: 'circle',
                    source: 'sites',
                    filter: ['!', ['has', 'point_count']],
                    paint: {
                        'circle-color': '#0ea5e9',
                        'circle-radius': 6,
                        'circle-stroke-width': 1,
                        'circle-stroke-color': '#ffffff'
                    }
                });

                map.addLayer({
                    id: 'unclustered-point-icon',
                    type: 'symbol',
                    source: 'sites',
                    filter: ['!', ['has', 'point_count']],
                    layout: {
                        'text-field': ['get', 'marker_icon'],
                        'text-size': 12,
                        'text-allow-overlap': true,
                        'text-ignore-placement': true
                    },
                    paint: {
                        'text-color': '#0f172a',
                        'text-halo-color': '#ffffff',
                        'text-halo-width': 1.2
                    }
                });

                map.on('click', 'clusters', function(e) {
                    var features = map.queryRenderedFeatures(e.point, { layers: ['clusters'] });
                    var clusterId = features[0].properties.cluster_id;
                    map.getSource('sites').getClusterExpansionZoom(clusterId, function(error, zoom) {
                        if (error) return;
                        map.easeTo({ center: features[0].geometry.coordinates, zoom: zoom });
                    });
                });

                map.on('mouseenter', 'clusters', function() { map.getCanvas().style.cursor = 'pointer'; });
                map.on('mouseleave', 'clusters', function() { map.getCanvas().style.cursor = ''; });

                map.on('mouseenter', 'unclustered-point', function() {
                    map.getCanvas().style.cursor = 'pointer';
                });

                map.on('mouseleave', 'unclustered-point', function() {
                    map.getCanvas().style.cursor = '';
                });

                Object.values(polygonCategoryLayers).forEach(function(layerCfg) {
                    map.on('mouseenter', layerCfg.fill, function() {
                        map.getCanvas().style.cursor = 'pointer';
                    });

                    map.on('mouseleave', layerCfg.fill, function() {
                        map.getCanvas().style.cursor = '';
                    });

                    map.on('click', layerCfg.fill, function(e) {
                        var polyBounds = getGeometryBounds(e.features[0] && e.features[0].geometry);
                        if (polyBounds && !polyBounds.isEmpty()) {
                            map.fitBounds(polyBounds, { padding: 50, duration: 500, maxZoom: 14 });
                        }

                        popup
                            .setLngLat(e.lngLat)
                            .setHTML(makePopupHtml(e.features[0]))
                            .addTo(map);
                    });
                });

                map.on('click', 'unclustered-point', function(e) {
                    popup
                        .setLngLat(e.features[0].geometry.coordinates)
                        .setHTML(makePopupHtml(e.features[0]))
                        .addTo(map);
                });

                // Admin boundary click popups
                var adminLabelFields = { admin0: 'adm0_name', admin1: 'adm1_name', admin2: 'adm2_name', admin3: 'adm3_name' };
                var adminLevelNames  = { admin0: 'Pays', admin1: 'Province', admin2: 'Territoire', admin3: 'Zone de santé' };
                Object.keys(adminLabelFields).forEach(function(lvl) {
                    var fillId = 'admin-fill-' + lvl;
                    map.on('mouseenter', fillId, function() { map.getCanvas().style.cursor = 'pointer'; });
                    map.on('mouseleave', fillId, function() { map.getCanvas().style.cursor = ''; });
                    map.on('click', fillId, function(e) {
                        var adminBounds = getGeometryBounds(e.features[0] && e.features[0].geometry);
                        if (adminBounds && !adminBounds.isEmpty()) {
                            map.fitBounds(adminBounds, { padding: 55, duration: 520, maxZoom: 12 });
                        }

                        var props = e.features[0].properties;
                        var name = props[adminLabelFields[lvl]] || props.name || '—';
                        var html = '<div style="font-size:0.82rem;min-width:120px">'
                            + '<div style="font-weight:700;color:#1e293b;margin-bottom:0.2rem">' + name + '</div>'
                            + '<div style="color:#64748b;font-size:0.74rem">' + adminLevelNames[lvl] + '</div>'
                            + '</div>';
                        popup.setLngLat(e.lngLat).setHTML(html).addTo(map);
                    });
                });
            }

            function syncLayerVisibility() {
                setLayerVisibility('clusters', layerClustersCheckbox.checked);
                setLayerVisibility('cluster-count', layerClustersCheckbox.checked);
                setLayerVisibility('unclustered-point', layerPointsCheckbox.checked);
                setLayerVisibility('unclustered-point-icon', layerPointsCheckbox.checked);
                setLayerVisibility(polygonCategoryLayers.pdi.fill, layerPolygonsPdiCheckbox.checked);
                setLayerVisibility(polygonCategoryLayers.pdi.line, layerPolygonsPdiCheckbox.checked);
                setLayerVisibility(polygonCategoryLayers.sousGestion.fill, layerPolygonsSousGestionCheckbox.checked);
                setLayerVisibility(polygonCategoryLayers.sousGestion.line, layerPolygonsSousGestionCheckbox.checked);
                setLayerVisibility(polygonCategoryLayers.horsGestion.fill, layerPolygonsHorsGestionCheckbox.checked);
                setLayerVisibility(polygonCategoryLayers.horsGestion.line, layerPolygonsHorsGestionCheckbox.checked);
                ['admin0','admin1','admin2','admin3'].forEach(function(lvl) {
                    var num = lvl.replace('admin','');
                    var cb = document.getElementById('layerAdmin' + num);
                    if (!cb) return;
                    setLayerVisibility('admin-fill-' + lvl, cb.checked);
                    setLayerVisibility('admin-line-' + lvl, cb.checked);
                    if (lvl === 'admin1') setLayerVisibility('admin1-labels', cb.checked);
                    if (lvl === 'admin2') setLayerVisibility('admin2-labels', cb.checked);
                });
            }

            window.setBasemap = function(styleKey, btn) {
                sourceReady = false;
                document.querySelectorAll('.basemap-btn').forEach(function(b) { b.classList.remove('active'); });
                if (btn) btn.classList.add('active');
                map.setStyle(basemapStyles[styleKey] || basemapStyles['blank']);
                map.once('style.load', function() {
                    addSourcesAndLayers();
                    syncLayerVisibility();
                    sourceReady = true;
                    renderFeatures();
                });
            };

            map.on('load', function() {
                addSourcesAndLayers();
                bindLayerControls();
                sourceReady = true;
                renderFeatures();
            });

            document.getElementById('btnApply').addEventListener('click', fetchSites);
            territoireSelect.addEventListener('change', function() {
                zoneSanteSelect.value = '';
                siteSelect.value = '';
                fetchSites();
            });
            zoneSanteSelect.addEventListener('change', function() {
                siteSelect.value = '';
                fetchSites();
            });
            siteSelect.addEventListener('change', fetchSites);
            categorieSelect.addEventListener('change', fetchSites);
            document.getElementById('btnReset').addEventListener('click', function() {
                provinceSelect.value = '';
                populateTerritoires(@json($territoires->map(fn($territoire) => ['id' => $territoire->id, 'name' => $territoire->name, 'province_id' => $territoire->province_id])->values()));
                territoireSelect.value = '';
                categorieSelect.value = '';
                zoneSanteSelect.value = '';
                siteSelect.value = '';
                searchInput.value = '';
                fetchSites();
            });

            searchInput.addEventListener('input', function() {
                renderFeatures();
            });

            document.getElementById('btnPrintMap').addEventListener('click', function() {
                updatePrintDetails();
                map.resize();
                setTimeout(function() { window.print(); }, 120);
            });

            window.addEventListener('beforeprint', function() {
                updatePrintDetails();
                map.resize();
            });

            window.addEventListener('afterprint', function() {
                setTimeout(function() { map.resize(); }, 120);
            });

            fetchCategories();
            fetchSites();
        })();
    </script>
</body>
</html>
