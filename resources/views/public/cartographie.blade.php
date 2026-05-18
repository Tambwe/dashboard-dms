<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cartographie des sites - DMS CCCM</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <x-vite-manifest-loader :assets="['resources/css/app.css', 'resources/js/app.js']" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" crossorigin=""/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" crossorigin=""/>
    <style>
        *{box-sizing:border-box}
        html,body{height:100%;margin:0;padding:0;font-family:'Inter',sans-serif}

        /* ── Navbar ───────────────────────────────────────────────── */
        #carto-navbar{position:fixed;top:0;left:0;right:0;height:56px;z-index:1000;background:rgba(255,255,255,0.97);border-bottom:1px solid #e5e7eb;backdrop-filter:blur(8px);display:flex;align-items:center;padding:0 1.25rem;justify-content:space-between}
        .dark #carto-navbar{background:rgba(17,24,39,0.97);border-color:#374151}

        /* ── Layout ───────────────────────────────────────────────── */
        #carto-layout{position:fixed;top:56px;left:0;right:0;bottom:0;display:flex;overflow:hidden}

        /* ── Panneau lateral ──────────────────────────────────────── */
        #filter-panel{width:320px;flex-shrink:0;background:#f8fafc;border-right:1px solid #e2e8f0;display:flex;flex-direction:column;overflow:hidden;transition:width 0.25s ease}
        .dark #filter-panel{background:#111827;border-color:#1f2937}
        #filter-panel.collapsed{width:0}

        #panel-header{padding:0 1rem;height:48px;display:flex;align-items:center;border-bottom:1px solid #e2e8f0;flex-shrink:0;background:#fff}
        .dark #panel-header{background:#1f2937;border-color:#374151}
        #panel-header-title{font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b}
        .dark #panel-header-title{color:#94a3b8}

        #panel-counter{padding:0.75rem 1rem;background:#eff6ff;border-bottom:1px solid #dbeafe;display:flex;align-items:center;gap:0.75rem;flex-shrink:0}
        .dark #panel-counter{background:rgba(37,99,235,0.12);border-color:rgba(37,99,235,0.2)}
        #sites-count{font-size:1.75rem;font-weight:800;color:#1d4ed8;line-height:1}
        .dark #sites-count{color:#93c5fd}
        #panel-counter-lbl{font-size:0.72rem;color:#3b82f6;font-weight:500;line-height:1.4}
        .dark #panel-counter-lbl{color:#60a5fa}

        #panel-filters{padding:0.75rem 1rem;border-bottom:1px solid #e2e8f0;flex-shrink:0;display:flex;flex-direction:column;gap:0.6rem;background:#fff}
        .dark #panel-filters{background:#1f2937;border-color:#374151}
        .flabel{display:block;font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#94a3b8;margin-bottom:3px}
        .fselect,.finput{width:100%;font-size:0.78rem;border:1px solid #e2e8f0;border-radius:0.5rem;padding:5px 9px;background:#f8fafc;color:#0f172a;outline:none;font-family:'Inter',sans-serif}
        .fselect:focus,.finput:focus{border-color:#3b82f6;box-shadow:0 0 0 2px rgba(59,130,246,0.18);background:#fff}
        .dark .fselect,.dark .finput{background:#374151;border-color:#4b5563;color:#f1f5f9}
        .dark .fselect:focus,.dark .finput:focus{border-color:#3b82f6;background:#1e293b}
        #filter-row{display:flex;gap:6px}
        .btn-apply{flex:1;padding:6px;background:#2563eb;color:#fff;font-size:0.78rem;font-weight:600;border:none;border-radius:0.5rem;cursor:pointer;font-family:'Inter',sans-serif}
        .btn-apply:hover{background:#1d4ed8}
        .btn-reset{padding:6px 10px;background:#f1f5f9;color:#475569;font-size:0.9rem;border:1px solid #e2e8f0;border-radius:0.5rem;cursor:pointer}
        .btn-reset:hover{background:#e2e8f0}
        .dark .btn-reset{background:#374151;color:#94a3b8;border-color:#4b5563}

        #list-header{padding:8px 1rem 6px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;background:#f8fafc;border-bottom:1px solid #e2e8f0}
        .dark #list-header{background:#111827;border-color:#1f2937}
        #list-header-title{font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#94a3b8}
        #list-count-badge{font-size:0.65rem;font-weight:600;background:#e0e7ff;color:#4338ca;padding:1px 7px;border-radius:9999px}
        .dark #list-count-badge{background:rgba(99,102,241,0.2);color:#a5b4fc}

        /* ── Liste des sites ──────────────────────────────────────── */
        #sites-list-wrap{flex:1;overflow-y:auto;padding:0.5rem 0.6rem;display:flex;flex-direction:column;gap:4px}
        #sites-list-wrap::-webkit-scrollbar{width:5px}
        #sites-list-wrap::-webkit-scrollbar-track{background:transparent}
        #sites-list-wrap::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:9999px}
        .dark #sites-list-wrap::-webkit-scrollbar-thumb{background:#374151}

        .site-card{display:block;width:100%;text-align:left;padding:8px 10px;border-radius:8px;border:1px solid transparent;background:#fff;cursor:pointer;transition:all 0.12s;box-shadow:0 1px 2px rgba(0,0,0,0.04)}
        .site-card:hover{border-color:#bfdbfe;background:#eff6ff;box-shadow:0 2px 6px rgba(37,99,235,0.12)}
        .site-card.active{border-color:#2563eb;background:#eff6ff;box-shadow:0 2px 8px rgba(37,99,235,0.18)}
        .dark .site-card{background:#1e293b;border-color:#1e293b}
        .dark .site-card:hover{border-color:#1d4ed8;background:#1e3a5f}
        .dark .site-card.active{border-color:#2563eb;background:#1e3a5f}

        .sc-top{display:flex;align-items:flex-start;justify-content:space-between;gap:6px;margin-bottom:3px}
        .sc-name{font-size:0.78rem;font-weight:600;color:#0f172a;line-height:1.3;flex:1}
        .dark .sc-name{color:#f1f5f9}
        .sc-badge{font-size:0.6rem;font-weight:600;padding:1px 6px;border-radius:9999px;background:#e0f2fe;color:#0369a1;flex-shrink:0;margin-top:1px;white-space:nowrap;max-width:90px;overflow:hidden;text-overflow:ellipsis}
        .dark .sc-badge{background:rgba(14,165,233,0.15);color:#38bdf8}
        .sc-geo{font-size:0.68rem;color:#64748b;margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .dark .sc-geo{color:#94a3b8}
        .sc-stats{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
        .sc-stat{font-size:0.65rem;color:#94a3b8;display:flex;align-items:center;gap:2px}
        .dark .sc-stat{color:#64748b}
        .sc-stat strong{color:#475569;font-weight:600}
        .dark .sc-stat strong{color:#94a3b8}
        .sc-poly{font-size:0.58rem;font-weight:600;color:#7c3aed;background:#ede9fe;padding:1px 5px;border-radius:4px;margin-left:auto;flex-shrink:0}
        .dark .sc-poly{color:#c4b5fd;background:rgba(124,58,237,0.15)}

        #panel-footer{padding:8px 1rem;border-top:1px solid #e2e8f0;flex-shrink:0;display:flex;align-items:center;gap:12px;background:#fff}
        .dark #panel-footer{background:#1f2937;border-color:#374151}
        .legend-item{display:flex;align-items:center;gap:5px;font-size:0.65rem;color:#94a3b8}
        .leg-dot{width:8px;height:8px;border-radius:50%;background:#3b82f6;flex-shrink:0}
        .leg-cluster{width:18px;height:18px;border-radius:50%;background:#2563eb;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:8px;font-weight:700;flex-shrink:0}
        .leg-poly{width:14px;height:10px;border-radius:2px;background:rgba(124,58,237,0.18);border:2px solid #7c3aed;flex-shrink:0}

        /* ── Carte ────────────────────────────────────────────────── */
        #map-container{flex:1;position:relative}
        #map{position:absolute;inset:0}

        /* ── Layers control ──────────────────────────────────────────*/
        .leaflet-control-layers{border-radius:10px!important;border:1px solid #e2e8f0!important;box-shadow:0 2px 10px rgba(0,0,0,0.1)!important;font-family:'Inter',sans-serif!important;font-size:0.75rem!important}
        .leaflet-control-layers-expanded{padding:8px 10px!important;min-width:160px}
        .leaflet-control-layers label{display:flex;align-items:center;gap:7px;padding:4px 2px;cursor:pointer;color:#374151;font-size:0.75rem}
        .leaflet-control-layers label:hover{color:#2563eb}
        .leaflet-control-layers input[type=radio]{accent-color:#2563eb;width:14px;height:14px}

        #btn-toggle{position:absolute;top:10px;left:10px;z-index:500;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:7px 8px;cursor:pointer;box-shadow:0 1px 4px rgba(0,0,0,0.1);color:#475569;display:flex;align-items:center}
        #btn-toggle:hover{background:#f8fafc}
        .dark #btn-toggle{background:#1f2937;border-color:#374151;color:#94a3b8}

        .visible-sites-panel{position:absolute;left:10px;right:10px;bottom:10px;z-index:480;background:rgba(255,255,255,0.95);border:1px solid #dbe3ef;border-radius:10px;box-shadow:0 10px 24px rgba(15,23,42,0.12);backdrop-filter:blur(6px);overflow:hidden;max-height:180px}
        .visible-sites-head{display:flex;align-items:center;justify-content:space-between;padding:8px 10px;border-bottom:1px solid #e2e8f0;background:#f8fafc;font-size:0.72rem;color:#334155;font-weight:700;text-transform:uppercase;letter-spacing:0.04em}
        .visible-sites-count{font-size:0.68rem;color:#1d4ed8;background:#dbeafe;border-radius:999px;padding:2px 8px;font-weight:700}
        .visible-sites-body{max-height:132px;overflow:auto}
        .visible-sites-table{width:100%;border-collapse:collapse;font-size:0.72rem;color:#334155}
        .visible-sites-table th,.visible-sites-table td{padding:6px 8px;border-bottom:1px solid #eef2f7;text-align:left;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .visible-sites-table th{position:sticky;top:0;background:#f8fafc;color:#64748b;font-size:0.66rem;text-transform:uppercase;letter-spacing:0.04em}
        .visible-sites-empty{padding:12px 10px;color:#94a3b8;font-size:0.72rem;font-style:italic}
        .dark .visible-sites-panel{background:rgba(15,23,42,0.92);border-color:#334155}
        .dark .visible-sites-head,.dark .visible-sites-table th{background:#1e293b;color:#cbd5e1;border-color:#334155}
        .dark .visible-sites-table td{color:#e2e8f0;border-color:#334155}
        .dark .visible-sites-count{background:#1d4ed8;color:#dbeafe}

        /* ── Popups ───────────────────────────────────────────────── */
        .leaflet-popup-content-wrapper{border-radius:10px!important;padding:0!important;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.15)!important}
        .leaflet-popup-content{margin:0!important}
        .popup-inner{padding:12px 14px;min-width:210px}
        .popup-title{font-weight:700;font-size:0.88rem;color:#0f172a;margin-bottom:4px;line-height:1.3}
        .popup-geo{font-size:0.72rem;color:#64748b;margin-bottom:4px}
        .popup-stats{font-size:0.72rem;color:#475569;margin-bottom:6px}
        .popup-cat{display:inline-block;padding:2px 8px;border-radius:9999px;font-size:0.65rem;font-weight:600;background:#e0f2fe;color:#0369a1;margin-bottom:8px}
        .popup-link{display:block;text-align:center;padding:6px 12px;background:#2563eb;color:#fff;border-radius:6px;font-size:0.72rem;font-weight:600;text-decoration:none}
        .popup-link:hover{background:#1d4ed8}

        .territory-label{
            background:transparent;
            border:none;
            box-shadow:none;
            color:#9a3412;
            font-size:0.68rem;
            font-weight:700;
            text-shadow:0 0 8px rgba(255,247,237,0.95), 0 0 3px rgba(255,255,255,0.95);
        }
        .territory-label:before{display:none}

        .list-msg{padding:20px 12px;text-align:center;font-size:0.75rem;color:#94a3b8;font-style:italic}

        .print-details{display:none}
        .print-card-title{font-size:14px;font-weight:800;color:#0f172a;margin-bottom:6px}
        .print-card-meta{font-size:11px;color:#475569;margin-bottom:8px}
        .print-card-line{font-size:11px;color:#334155;line-height:1.45;margin-bottom:4px}
        .print-card-line strong{color:#0f172a}
        #print-sites-list{display:none}

        @page { size: A4 landscape; margin: 1cm; }
        @media print {
            html,body{height:auto!important;background:#fff!important}
            #carto-navbar,#filter-panel,#btn-toggle{display:none!important}
            #carto-layout{position:static!important;top:0!important;left:0!important;right:0!important;bottom:auto!important;height:auto!important;display:block!important}
            #map-container{position:relative!important;height:100vh!important;width:100%!important}
            #map{position:static!important;height:100vh!important;width:100%!important}
            .leaflet-control-container,.leaflet-control-attribution{display:none!important}
            .visible-sites-panel{display:none!important}
            .print-details{display:block!important;position:absolute!important;top:14px!important;left:14px!important;z-index:9999!important;max-width:360px!important;background:rgba(255,255,255,0.94)!important;border:1px solid #cbd5e1!important;border-radius:10px!important;box-shadow:0 8px 24px rgba(15,23,42,0.12)!important;padding:12px 14px!important}
            #print-sites-list{display:block!important;page-break-before:always;padding:1cm 0 0 0}
            #print-sites-list h2{font-size:14px;font-weight:800;color:#0f172a;margin:0 0 8px 0;border-bottom:2px solid #2563eb;padding-bottom:6px}
            #print-sites-list table{width:100%;border-collapse:collapse;font-size:10px}
            #print-sites-list th{background:#1d4ed8;color:#fff;padding:5px 8px;text-align:left;font-weight:700}
            #print-sites-list td{padding:4px 8px;border-bottom:1px solid #e2e8f0;color:#0f172a}
            #print-sites-list tr:nth-child(even) td{background:#f0f7ff}
        }
    </style>
</head>
<body>

{{-- NAVBAR --}}
<header id="carto-navbar">
    <a href="{{ url('/dashboard') }}" style="display:flex;align-items:center;gap:10px;text-decoration:none">
        <img src="{{ asset('images/logo-dms-cccm.avif') }}" alt="Logo" style="height:32px;width:auto">
        <span style="font-weight:700;font-size:1rem;color:#0f172a" class="dark:text-white">DMS CCCM</span>
    </a>
    <nav style="display:flex;align-items:center;gap:1.25rem">
        <a href="{{ url('/dashboard') }}"            style="font-size:0.8rem;color:#64748b;text-decoration:none;font-weight:500" class="hidden md:block">Accueil</a>
        <a href="{{ url('/about') }}"                     style="font-size:0.8rem;color:#64748b;text-decoration:none;font-weight:500" class="hidden md:block">A propos</a>
        <a href="{{ url('/profil-site') }}"  style="font-size:0.8rem;color:#64748b;text-decoration:none;font-weight:500" class="hidden md:block">Profil des sites</a>
        <a href="{{ url('/cartographie') }}" style="font-size:0.8rem;color:#2563eb;text-decoration:none;font-weight:600;border-bottom:2px solid #2563eb;padding-bottom:1px" class="hidden md:block">Cartographie</a>
        <a href="{{ url('/cartographie-mapbox') }}" style="font-size:0.8rem;color:#64748b;text-decoration:none;font-weight:500" class="hidden md:block">Cartographie Mapbox</a>
        <button id="btn-print" type="button" style="padding:6px 10px;background:#2563eb;border:none;border-radius:8px;cursor:pointer;color:#fff;display:flex;align-items:center;font-size:0.75rem;font-weight:600" title="Imprimer la carte">🖨️ Imprimer</button>
        <button onclick="toggleDarkMode()" style="padding:6px;background:#f1f5f9;border:none;border-radius:8px;cursor:pointer;color:#475569;display:flex;align-items:center" title="Mode sombre">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        </button>
        <a href="{{ route('login') }}" style="padding:6px 14px;background:#2563eb;color:#fff;border-radius:8px;font-size:0.8rem;font-weight:600;text-decoration:none">Se connecter</a>
    </nav>
</header>

{{-- LAYOUT --}}
<div id="carto-layout">

    {{-- PANNEAU --}}
    <aside id="filter-panel">

        <div id="panel-header">
            <span id="panel-header-title">&#128506;&nbsp; Filtres &amp; Sites</span>
        </div>

        <div id="panel-counter">
            <div id="sites-count">{{ $totalSites }}</div>
            <div id="panel-counter-lbl">sites<br>g&eacute;olocalis&eacute;s</div>
        </div>

        <div id="panel-filters">
            <div>
                <label class="flabel" for="search-input">Rechercher un site</label>
                <input id="search-input" class="finput" type="text" placeholder="Nom du site&#8230;">
            </div>
            <div>
                <label class="flabel" for="filter-province">Province</label>
                <select id="filter-province" class="fselect">
                    <option value="">&#8212; Toutes &#8212;</option>
                    @foreach($provinces as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="flabel" for="filter-territoire">Territoire</label>
                <select id="filter-territoire" class="fselect">
                    <option value="">&#8212; Tous &#8212;</option>
                </select>
            </div>
            <div>
                <label class="flabel" for="filter-zone">Zone de sant&eacute;</label>
                <select id="filter-zone" class="fselect" disabled>
                    <option value="">&#8212; Toutes &#8212;</option>
                </select>
            </div>
            <div>
                <label class="flabel" for="filter-categorie">Cat&eacute;gorie de site</label>
                <select id="filter-categorie" class="fselect">
                    <option value="">&#8212; Toutes &#8212;</option>
                </select>
            </div>
            <div id="filter-row">
                <button id="btn-apply" class="btn-apply">&#128269; Appliquer</button>
                <button id="btn-reset" class="btn-reset" title="R&eacute;initialiser les filtres">&#8635;</button>
            </div>
        </div>

        <div id="list-header">
            <span id="list-header-title">Sites affich&eacute;s</span>
            <span id="list-count-badge">0</span>
        </div>

        <div id="sites-list-wrap"></div>

        <div id="panel-footer">
            <div class="legend-item"><span class="leg-dot"></span>Marqueur</div>
            <div class="legend-item"><span class="leg-cluster">N</span>Cluster</div>
            <div class="legend-item"><span class="leg-poly"></span>Polygone</div>
        </div>
    </aside>

    {{-- CARTE --}}
    <div id="map-container">
        <div id="map"></div>
        <div id="print-details" class="print-details"></div>
        <div class="visible-sites-panel" id="visibleSitesPanel">
            <div class="visible-sites-head">
                <span>Sites visibles sur la carte</span>
                <span class="visible-sites-count" id="visibleSitesCount">0</span>
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
                    <tbody id="visibleSitesTableBody"></tbody>
                </table>
                <div id="visibleSitesEmpty" class="visible-sites-empty" style="display:none;">Aucun site visible dans l'emprise actuelle.</div>
            </div>
        </div>
        <button id="btn-toggle" title="Afficher / masquer le panneau">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
    </div>
</div>

{{-- LISTE DES SITES POUR L'IMPRESSION --}}
<div id="print-sites-list">
    <h2>Liste des sites concernés</h2>
    <table>
        <thead><tr><th>#</th><th>Nom du site</th><th>Province</th><th>Territoire</th><th>Zone de santé</th><th>Catégorie</th><th>PDI</th><th>Ménages</th></tr></thead>
        <tbody id="print-sites-tbody"></tbody>
    </table>
</div>

<script>
(function(){
    var s=localStorage.getItem('theme');
    if(s==='dark'||(!s&&window.matchMedia('(prefers-color-scheme:dark)').matches)){document.documentElement.classList.add('dark');}
})();
function toggleDarkMode(){
    var d=document.documentElement.classList.toggle('dark');
    localStorage.setItem('theme',d?'dark':'light');
}
</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    var map = L.map('map', { zoomControl: false }).setView([-2.5, 25.5], 6);
    L.control.zoom({ position: 'bottomright' }).addTo(map);

    // ── Fonds de carte ──────────────────────────────────────────────
    var emptyBaseLayer = L.layerGroup();
    var baseLayers = {
        '&#8709; Sans fond': emptyBaseLayer,
        '&#x1F5FA;&#xFE0F; OpenStreetMap': L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19
        }),
        '&#x1F6F0;&#xFE0F; Satellite': L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
            maxZoom: 19
        }),
        '&#x26F0;&#xFE0F; Terrain': L.tileLayer('https://stamen-tiles-{s}.a.ssl.fastly.net/terrain/{z}/{x}/{y}{r}.png', {
            attribution: 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a> &mdash; Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            subdomains: 'abcd', maxZoom: 18
        }),
        '&#x1F4CD; Topo': L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
            attribution: 'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)',
            maxZoom: 17
        }),
        '&#x1F319; Sombre': L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd', maxZoom: 19
        })
    };
    baseLayers['&#8709; Sans fond'].addTo(map);
    var markerLayer = (typeof L.markerClusterGroup === 'function')
        ? L.markerClusterGroup({ maxClusterRadius: 50, showCoverageOnHover: false })
        : L.layerGroup();
    var polygonLayer = L.layerGroup();
    var adminConfigs = {
        admin0: { file: '/geojson/cod_admin0.geojson', color: '#1e293b', weight: 2.4, fillOpacity: 0.01, label: 'Pays', field: 'adm0_name' },
        admin1: { file: '/geojson/cod_admin1.geojson', color: '#dc2626', weight: 1.8, fillOpacity: 0.04, label: 'Province', field: 'adm1_name' },
        admin2: { file: '/geojson/cod_admin2.geojson', color: '#d97706', weight: 1.1, fillOpacity: 0.03, label: 'Territoire', field: 'adm2_name' },
        admin3: { file: '/geojson/cod_admin3.geojson', color: '#7c3aed', weight: 0.8, fillOpacity: 0.02, label: 'Zone de sante', field: 'adm3_name' }
    };
    var adminLayers = {
        admin0: L.layerGroup(),
        admin1: L.layerGroup(),
        admin2: L.layerGroup(),
        admin3: L.layerGroup()
    };
    var adminLoaded = {
        admin0: false,
        admin1: false,
        admin2: false,
        admin3: false
    };
    var territoryLabelLayers = [];
    map.addLayer(polygonLayer);
    map.addLayer(markerLayer);
    map.addLayer(adminLayers.admin1);

    L.control.layers(baseLayers, {
        '&#x1F534; Provinces': adminLayers.admin1,
        '&#x1F7E0; Territoires': adminLayers.admin2,
        '&#x1F7E3; Zones de sante': adminLayers.admin3,
        '&#x26AB; Pays': adminLayers.admin0
    }, { position: 'topright', collapsed: true }).addTo(map);
    // ────────────────────────────────────────────────────────────────

    function getAdminStyle(key) {
        var cfg = adminConfigs[key];
        return {
            color: cfg.color,
            weight: cfg.weight,
            opacity: 0.9,
            fillColor: cfg.color,
            fillOpacity: cfg.fillOpacity
        };
    }

    function makeAdminPopup(key, props) {
        var cfg = adminConfigs[key];
        var name = props[cfg.field] || props.name || '—';
        return '<div class="popup-inner">'
            + '<div class="popup-title">' + esc(name) + '</div>'
            + '<div class="popup-geo">Limite administrative : ' + esc(cfg.label) + '</div>'
            + '</div>';
    }

    function getSelectedLabel(selectId, emptyLabel) {
        var select = document.getElementById(selectId);
        if (!select || !select.value) return emptyLabel;
        return select.options[select.selectedIndex] ? select.options[select.selectedIndex].textContent.trim() : emptyLabel;
    }

    function getCurrentBaseLayerName() {
        return Object.keys(baseLayers).find(function(name) {
            return map.hasLayer(baseLayers[name]);
        }) || 'Inconnu';
    }

    function getVisibleAdminLayers() {
        var layers = [];
        if (map.hasLayer(adminLayers.admin1)) layers.push('Provinces');
        if (map.hasLayer(adminLayers.admin2)) layers.push('Territoires');
        if (map.hasLayer(adminLayers.admin3)) layers.push('Zones de sante');
        if (map.hasLayer(adminLayers.admin0)) layers.push('Pays');
        return layers.length ? layers.join(', ') : 'Aucune';
    }

    function updatePrintDetails() {
        var printBox = document.getElementById('print-details');
        if (!printBox) return;

        var lines = [];
        lines.push('<div class="print-card-title">Cartographie des sites - Leaflet</div>');
        lines.push('<div class="print-card-meta">Imprime le ' + new Date().toLocaleString('fr-FR') + '</div>');
        lines.push('<div class="print-card-line"><strong>Sites affiches :</strong> ' + esc(document.getElementById('sites-count').textContent.trim() || '0') + '</div>');
        lines.push('<div class="print-card-line"><strong>Recherche :</strong> ' + esc((document.getElementById('search-input').value || '').trim() || 'Aucune') + '</div>');
        lines.push('<div class="print-card-line"><strong>Province :</strong> ' + esc(getSelectedLabel('filter-province', 'Toutes')) + '</div>');
        lines.push('<div class="print-card-line"><strong>Territoire :</strong> ' + esc(getSelectedLabel('filter-territoire', 'Tous')) + '</div>');
        lines.push('<div class="print-card-line"><strong>Zone de sante :</strong> ' + esc(getSelectedLabel('filter-zone', 'Toutes')) + '</div>');
        lines.push('<div class="print-card-line"><strong>Categorie :</strong> ' + esc(getSelectedLabel('filter-categorie', 'Toutes')) + '</div>');
        lines.push('<div class="print-card-line"><strong>Fond :</strong> ' + esc(getCurrentBaseLayerName()) + '</div>');
        lines.push('<div class="print-card-line"><strong>Limites visibles :</strong> ' + esc(getVisibleAdminLayers()) + '</div>');
        printBox.innerHTML = lines.join('');

        // Remplir la liste des sites
        var tbody = document.getElementById('print-sites-tbody');
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

    function refreshTerritoryLabels() {
        var show = map.hasLayer(adminLayers.admin2) && map.getZoom() >= 8;
        territoryLabelLayers.forEach(function(layer) {
            if (!layer.getTooltip || !layer.getTooltip()) return;
            if (show) layer.openTooltip();
            else layer.closeTooltip();
        });
    }

    function loadAdminLayer(key) {
        if (adminLoaded[key]) return;
        adminLoaded[key] = true;

        fetch(adminConfigs[key].file)
            .then(function(r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function(data) {
                var geoLayer = L.geoJSON(data, {
                    style: function() {
                        return getAdminStyle(key);
                    },
                    onEachFeature: function(feature, layer) {
                        layer.bindPopup(makeAdminPopup(key, feature.properties || {}), { maxWidth: 260 });
                        if (key === 'admin2') {
                            var territoryName = (feature.properties && (feature.properties.adm2_name || feature.properties.name)) || '—';
                            layer.bindTooltip(esc(territoryName), {
                                permanent: true,
                                direction: 'center',
                                className: 'territory-label'
                            });
                            territoryLabelLayers.push(layer);
                        }
                        layer.on('mouseover', function() {
                            layer.setStyle({
                                fillOpacity: Math.max(adminConfigs[key].fillOpacity + 0.08, 0.1),
                                weight: adminConfigs[key].weight + 0.8
                            });
                        });
                        layer.on('mouseout', function() {
                            geoLayer.resetStyle(layer);
                        });
                        layer.on('click', function() {
                            var b = layer.getBounds && layer.getBounds();
                            if (b && b.isValid && b.isValid()) {
                                map.fitBounds(b, { padding: [45, 45], maxZoom: 12 });
                            }
                            layer.openPopup();
                        });
                    }
                });
                adminLayers[key].addLayer(geoLayer);
                if (key === 'admin2') refreshTerritoryLabels();
            })
            .catch(function(err) {
                console.error('Erreur chargement couche admin', key, err);
                adminLoaded[key] = false;
            });
    }

    map.on('overlayadd', function(evt) {
        Object.keys(adminLayers).forEach(function(key) {
            if (evt.layer === adminLayers[key]) loadAdminLayer(key);
        });
        refreshTerritoryLabels();
    });

    map.on('overlayremove', refreshTerritoryLabels);
    map.on('zoomend', refreshTerritoryLabels);

    loadAdminLayer('admin1');

    var allSites  = [];
    var activeCard = null;

    var allTerritoires = @json($territoires->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'province_id' => $t->province_id]));

    var selProv   = document.getElementById('filter-province');
    var selTerr   = document.getElementById('filter-territoire');
    var selZone   = document.getElementById('filter-zone');
    var selCat    = document.getElementById('filter-categorie');
    var searchInp = document.getElementById('search-input');
    var countEl   = document.getElementById('sites-count');
    var badgeEl   = document.getElementById('list-count-badge');
    var listWrap  = document.getElementById('sites-list-wrap');
    var visibleSitesBody = document.getElementById('visibleSitesTableBody');
    var visibleSitesCount = document.getElementById('visibleSitesCount');
    var visibleSitesEmpty = document.getElementById('visibleSitesEmpty');
    var currentFilteredSites = [];

    // Charger les categories
    fetch('/api/geographic/categories-sites')
        .then(function(r){ return r.json(); })
        .then(function(data){ data.forEach(function(c){ selCat.appendChild(new Option(c.name, c.id)); }); })
        .catch(function(){});

    function getParams() {
        var p = {};
        if (selProv.value) p.province_id       = selProv.value;
        if (selTerr.value) p.territoire_id     = selTerr.value;
        if (selZone.value) p.commune_id        = selZone.value;
        if (selCat.value)  p.categorie_site_id = selCat.value;
        return p;
    }

    function loadSites() {
        var params = getParams();
        var qs = Object.keys(params).length ? '?' + new URLSearchParams(params).toString() : '';
        listWrap.innerHTML = '<div class="list-msg">Chargement&#8230;</div>';
        activeCard = null;
        fetch('/api/geographic/sites-coordinates' + qs)
            .then(function(r){ return r.json(); })
            .then(function(data){
                allSites = Array.isArray(data) ? data : [];
                renderMarkers();
            })
            .catch(function(){
                listWrap.innerHTML = '<div class="list-msg" style="color:#ef4444">Erreur de chargement.</div>';
            });
    }

    function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function fmt(n){ return n ? Number(n).toLocaleString('fr-FR') : null; }

    function makePopup(site) {
        var ind = fmt(site.individus), men = fmt(site.menages);
        var cat = site.categorie_site && site.categorie_site.name ? site.categorie_site.name : null;
        var geo = (site.province || '') + (site.territoire ? ' \u203a ' + site.territoire : '');
        return '<div class="popup-inner">'
            + '<div class="popup-title">' + esc(site.nom) + '</div>'
            + (geo ? '<div class="popup-geo">\uD83D\uDCCD ' + esc(geo) + '</div>' : '')
            + ((ind || men) ? '<div class="popup-stats">'
                + (ind ? '\uD83D\uDC65 ' + ind + ' pers.' : '')
                + (ind && men ? ' &nbsp;&middot;&nbsp; ' : '')
                + (men ? '\uD83C\uDFE0 ' + men + ' m\u00e9n.' : '')
                + '</div>' : '')
            + (cat ? '<div><span class="popup-cat">' + esc(cat) + '</span></div>' : '')
            + '<a class="popup-link" href="/profil-site/' + site.id + '">Voir le profil &#8594;</a>'
            + '</div>';
    }

    function updateVisibleSitesTable() {
        if (!visibleSitesBody || !visibleSitesCount || !visibleSitesEmpty) return;

        var bounds = map.getBounds();
        var visible = currentFilteredSites.filter(function(site) {
            var lat = parseFloat(site.latitude);
            var lng = parseFloat(site.longitude);
            if (!lat || !lng) return false;
            return bounds.contains([lat, lng]);
        });

        visibleSitesCount.textContent = visible.length;
        visibleSitesBody.innerHTML = '';

        if (!visible.length) {
            visibleSitesEmpty.style.display = 'block';
            return;
        }

        visibleSitesEmpty.style.display = 'none';
        visible.forEach(function(site) {
            var tr = document.createElement('tr');
            tr.innerHTML = '<td>' + esc(site.nom || '-') + '</td>'
                + '<td>' + esc(site.province || '-') + '</td>'
                + '<td>' + esc(site.territoire || '-') + '</td>'
                + '<td>' + esc(site.categorie_site && site.categorie_site.name ? site.categorie_site.name : '-') + '</td>';
            visibleSitesBody.appendChild(tr);
        });
    }

    function renderMarkers() {
        markerLayer.clearLayers();
        polygonLayer.clearLayers();
        listWrap.innerHTML = '';
        activeCard = null;

        var q = searchInp.value.toLowerCase().trim();
        var list = q ? allSites.filter(function(s){ return s.nom.toLowerCase().includes(q); }) : allSites;
        currentFilteredSites = list;
        countEl.textContent = list.length;
        badgeEl.textContent = list.length;

        if (!list.length) {
            listWrap.innerHTML = '<div class="list-msg">Aucun site trouv\u00e9.</div>';
            updateVisibleSitesTable();
            return;
        }

        var bounds = [];

        list.forEach(function(site) {
            var lat = parseFloat(site.latitude);
            var lng = parseFloat(site.longitude);
            if (!lat || !lng) return;

            var popup   = makePopup(site);
            var cat     = site.categorie_site && site.categorie_site.name ? site.categorie_site.name : null;
            var ind     = fmt(site.individus);
            var men     = fmt(site.menages);
            var geo     = (site.province || '') + (site.territoire ? ' \u203a ' + site.territoire : '');
            var hasPoly = !!site.geojson_data;

            // Polygone GeoJSON
            var geoLayer = null;
            if (hasPoly) {
                try {
                    geoLayer = L.geoJSON(site.geojson_data, {
                        style: { color:'#7c3aed', weight:2, opacity:0.85, fillColor:'#8b5cf6', fillOpacity:0.15 }
                    });
                    geoLayer.bindPopup(popup, { maxWidth:290 });
                    geoLayer.on('mouseover', function(e){ e.target.setStyle({ fillOpacity:0.32, weight:3 }); });
                    geoLayer.on('mouseout',  function(e){ e.target.setStyle({ fillOpacity:0.15, weight:2 }); });
                    geoLayer.on('click', function() {
                        var b = geoLayer.getBounds && geoLayer.getBounds();
                        if (b && b.isValid && b.isValid()) {
                            map.fitBounds(b, { padding:[55,55], maxZoom:14 });
                        }
                    });
                    polygonLayer.addLayer(geoLayer);
                    try { var pb = geoLayer.getBounds(); if (pb.isValid()){ bounds.push(pb.getSouthWest(), pb.getNorthEast()); } } catch(e){}
                } catch(e){ geoLayer = null; }
            }

            // Marqueur
            var m = L.marker([lat, lng]);
            m.bindPopup(popup, { maxWidth:290 });
            markerLayer.addLayer(m);
            bounds.push([lat, lng]);

            // Carte dans la liste
            var card = document.createElement('button');
            card.className = 'site-card';
            card.innerHTML =
                '<div class="sc-top">'
                    + '<span class="sc-name">' + esc(site.nom) + '</span>'
                    + (cat ? '<span class="sc-badge">' + esc(cat) + '</span>' : '')
                + '</div>'
                + (geo ? '<div class="sc-geo">\uD83D\uDCCD ' + esc(geo) + '</div>' : '')
                + '<div class="sc-stats">'
                    + (ind ? '<span class="sc-stat">\uD83D\uDC65&nbsp;<strong>' + ind + '</strong></span>' : '')
                    + (men ? '<span class="sc-stat">\uD83C\uDFE0&nbsp;<strong>' + men + '</strong></span>' : '')
                    + (hasPoly ? '<span class="sc-poly">&#9724; Polygone</span>' : '')
                + '</div>';

            (function(lat, lng, m, gl, card) {
                card.addEventListener('click', function() {
                    if (activeCard) activeCard.classList.remove('active');
                    activeCard = card;
                    card.classList.add('active');
                    card.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                    if (gl) {
                        try { map.fitBounds(gl.getBounds(), { padding:[60,60], maxZoom:14 }); gl.openPopup(); return; } catch(e){}
                    }
                    map.setView([lat, lng], 13);
                    m.openPopup();
                });
            })(lat, lng, m, geoLayer, card);

            listWrap.appendChild(card);
        });

        if (bounds.length > 1) map.fitBounds(bounds, { padding:[40,40], maxZoom:10 });
        else if (bounds.length === 1) map.setView(bounds[0], 13);

        updateVisibleSitesTable();
    }

    function resetZones() {
        selZone.innerHTML = '<option value="">\u2014 Toutes \u2014</option>';
        selZone.disabled = true;
    }

    function loadZones(territoireId) {
        resetZones();
        if (!territoireId) return;
        fetch('/api/geographic/communes?territoire_id=' + territoireId)
            .then(function(r){ return r.json(); })
            .then(function(data){
                data.forEach(function(z){ selZone.appendChild(new Option(z.name, z.id)); });
                if (data.length) selZone.disabled = false;
            })
            .catch(function(){});
    }

    // Cascade province -> territoire
    selProv.addEventListener('change', function() {
        var pid = parseInt(this.value, 10);
        selTerr.innerHTML = '<option value="">\u2014 Tous \u2014</option>';
        resetZones();
        if (pid) {
            allTerritoires
                .filter(function(t){ return t.province_id === pid; })
                .forEach(function(t){ selTerr.appendChild(new Option(t.name, t.id)); });
        }
        loadSites();
    });

    // Cascade territoire -> zone de sante
    selTerr.addEventListener('change', function() {
        loadZones(this.value);
        loadSites();
    });
    selZone.addEventListener('change', loadSites);
    selCat.addEventListener('change',  loadSites);
    searchInp.addEventListener('input', renderMarkers);
    document.getElementById('btn-apply').addEventListener('click', loadSites);
    document.getElementById('btn-reset').addEventListener('click', function() {
        selProv.value = '';
        selTerr.innerHTML = '<option value="">\u2014 Tous \u2014</option>';
        resetZones();
        selCat.value = '';
        searchInp.value = '';
        loadSites();
    });
    document.getElementById('btn-toggle').addEventListener('click', function() {
        document.getElementById('filter-panel').classList.toggle('collapsed');
        setTimeout(function(){ map.invalidateSize(); }, 280);
    });

    map.on('moveend', updateVisibleSitesTable);
    map.on('zoomend', updateVisibleSitesTable);

    document.getElementById('btn-print').addEventListener('click', function() {
        updatePrintDetails();
        map.invalidateSize();
        setTimeout(function(){ window.print(); }, 120);
    });

    window.addEventListener('beforeprint', function() {
        updatePrintDetails();
        map.invalidateSize();
    });

    window.addEventListener('afterprint', function() {
        setTimeout(function(){ map.invalidateSize(); }, 120);
    });

    loadSites();
});
</script>
</body>
</html>
