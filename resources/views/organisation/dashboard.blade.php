@extends('layouts.app')

@section('title', 'Tableau de bord – Mon organisation')
@section('subtitle', 'Vue d\'ensemble des projets et financements')

@section('content')
{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

{{-- Print styles --}}
<style>
@page { size: A4 portrait; margin: 1.5cm; }
@media print {
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* Conserver le rendu écran et masquer seulement les contrôles d'action */
    .no-print { display: none !important; }
}
</style>

<div class="space-y-6">

    {{-- Bouton imprimer --}}
    <div class="flex justify-end no-print">
        <button onclick="window.print()"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Imprimer
        </button>
    </div>

    {{-- ── KPI Cards ──────────────────────────────────────── --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 flex flex-col gap-1">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Projets</span>
            <span class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ $totalProjects }}</span>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 flex flex-col gap-1">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Financement total</span>
            <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                {{ number_format($totalFunding, 0, ',', ' ') }} <span class="text-sm font-normal">USD</span>
            </span>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 flex flex-col gap-1">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Consommé</span>
            <span class="text-2xl font-bold text-orange-500 dark:text-orange-400">
                {{ number_format($totalConsumed, 0, ',', ' ') }} <span class="text-sm font-normal">USD</span>
            </span>
            <span class="text-xs text-gray-400">{{ $consumptionRate }}% du budget</span>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 flex flex-col gap-1">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Activités</span>
            <span class="text-3xl font-bold text-sky-600 dark:text-sky-400">{{ $totalActivities }}</span>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 flex flex-col gap-1">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bénéficiaires</span>
            <span class="text-3xl font-bold text-rose-600 dark:text-rose-400">{{ number_format($totalBenef, 0, ',', ' ') }}</span>
        </div>
    </div>

    {{-- ── Row 2 : Map + Bénéficiaires ────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Carte projets par province --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow p-4">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">Répartition des projets par province</h3>
            <div id="org-map" class="w-full rounded-lg" style="height:380px;"></div>
            @if($projectsByProvince->isEmpty())
                <p class="mt-2 text-xs text-gray-400 text-center">Aucune zone d'exécution renseignée.</p>
            @endif
        </div>

        {{-- Graphique bénéficiaires --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 flex flex-col">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">Bénéficiaires par catégorie</h3>
            @if($totalBenef > 0)
                <div class="flex-1 flex items-center justify-center">
                    <canvas id="chart-beneficiaires" style="max-height:280px;"></canvas>
                </div>
            @else
                <p class="text-xs text-gray-400 text-center mt-8">Aucun bénéficiaire enregistré.</p>
            @endif
        </div>
    </div>

    {{-- ── Row 3 : Bailleurs + Évolution ───────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Projets par bailleur --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">Projets par bailleur</h3>
            @if(count($projectsByDonor) > 0)
                <canvas id="chart-bailleurs" style="max-height:280px;"></canvas>
            @else
                <p class="text-xs text-gray-400 text-center mt-8">Aucun bailleur renseigné.</p>
            @endif
        </div>

        {{-- Évolution consommation vs activités --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">Évolution consommation financement vs activités réalisées</h3>
            @if(count($evolutionLabels) > 0)
                <canvas id="chart-evolution" style="max-height:280px;"></canvas>
            @else
                <p class="text-xs text-gray-400 text-center mt-8">Aucune activité avec date de rapportage.</p>
            @endif
        </div>
    </div>

    {{-- ── Tableau récap projets par province ─────────────── --}}
    @if($projectsByProvince->isNotEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">Détail par province</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Province</th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nb projets</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($projectsByProvince->sortByDesc('nb_projets') as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                        <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $row['province_name'] }}</td>
                        <td class="px-4 py-2 text-center font-semibold text-indigo-600 dark:text-indigo-400">{{ $row['nb_projets'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>

@php
    $evolutionChartData = [
        'labels' => $evolutionLabels,
        'cumCost' => $evolutionCumCost,
        'nbActs' => $evolutionNbActs,
    ];
@endphp

{{-- ── JSON data for JS ────────────────────────────────────── --}}
<script type="application/json" id="map-data-json">@json($projectsByProvince->values())</script>
<script type="application/json" id="benef-data-json">@json($benef)</script>
<script type="application/json" id="donors-data-json">@json(array_values($projectsByDonor))</script>
<script type="application/json" id="evolution-data-json">@json($evolutionChartData)</script>

<script>
(function () {
    // ── helpers ────────────────────────────────────────────────────────
    function parseJson(id) {
        try { return JSON.parse(document.getElementById(id).textContent); }
        catch(e) { return null; }
    }

    const isDark = () => document.documentElement.classList.contains('dark');
    const gridColor = () => isDark() ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';
    const labelColor = () => isDark() ? '#cbd5e1' : '#374151';

    // ── 1) Carte Leaflet ───────────────────────────────────────────────
    const mapData = parseJson('map-data-json') || [];
    if (mapData.length) {
        const map = L.map('org-map').setView([-4.0, 24.0], 5);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 12,
        }).addTo(map);

        const maxNb = Math.max(...mapData.map(d => d.nb_projets), 1);

        mapData.forEach(function(d) {
            if (!d.center_lat || !d.center_lon) return;
            const radius = 12 + (d.nb_projets / maxNb) * 28;
            const color = d.nb_projets >= 3 ? '#4f46e5'
                        : d.nb_projets === 2 ? '#0ea5e9'
                        : '#10b981';
            L.circleMarker([d.center_lat, d.center_lon], {
                radius: radius,
                fillColor: color,
                color: '#fff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.75,
            }).bindPopup(
                '<strong>' + d.province_name + '</strong><br>' +
                d.nb_projets + ' projet' + (d.nb_projets > 1 ? 's' : '')
            ).addTo(map);
        });
    } else {
        // Empty map placeholder
        if (document.getElementById('org-map')) {
            const map = L.map('org-map').setView([-4.0, 24.0], 5);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
            }).addTo(map);
        }
    }

    // ── 2) Bénéficiaires – Doughnut ─────────────────────────────────────
    const benefData = parseJson('benef-data-json');
    if (benefData && document.getElementById('chart-beneficiaires')) {
        const labels  = ['Filles 0-17', 'Femmes 18-59', 'Femmes 60+', 'Garçons 0-17', 'Hommes 18-59', 'Hommes 60+'];
        const values  = [
            benefData.filles_0_17, benefData.femmes_18_59, benefData.femmes_60plus,
            benefData.garcons_0_17, benefData.hommes_18_59, benefData.hommes_60plus,
        ];
        const colors = ['#f472b6','#ec4899','#be185d','#60a5fa','#3b82f6','#1d4ed8'];

        new Chart(document.getElementById('chart-beneficiaires'), {
            type: 'doughnut',
            data: { labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 2 }] },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { color: labelColor(), font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            label: ctx => ' ' + ctx.label + ': ' + ctx.parsed.toLocaleString('fr-FR'),
                        },
                    },
                },
            },
        });
    }

    // ── 3) Bailleurs – Bar horizontal ───────────────────────────────────
    const donorsData = parseJson('donors-data-json');
    if (donorsData && donorsData.length && document.getElementById('chart-bailleurs')) {
        const labels = donorsData.map(d => d.donor);
        const values = donorsData.map(d => d.count);

        new Chart(document.getElementById('chart-bailleurs'), {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Nombre de projets',
                    data: values,
                    backgroundColor: 'rgba(99,102,241,0.75)',
                    borderColor: '#4f46e5',
                    borderWidth: 1,
                    borderRadius: 4,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    x: {
                        ticks: { color: labelColor(), stepSize: 1 },
                        grid:  { color: gridColor() },
                    },
                    y: { ticks: { color: labelColor() }, grid: { display: false } },
                },
            },
        });
    }

    // ── 4) Évolution – Line (cumul financement) + Bar (nb activités) ────
    const evoData = parseJson('evolution-data-json');
    if (evoData && evoData.labels.length && document.getElementById('chart-evolution')) {
        new Chart(document.getElementById('chart-evolution'), {
            type: 'bar',
            data: {
                labels: evoData.labels,
                datasets: [
                    {
                        type: 'line',
                        label: 'Consommation cumulée (USD)',
                        data: evoData.cumCost,
                        borderColor: '#f97316',
                        backgroundColor: 'rgba(249,115,22,0.15)',
                        tension: 0.3,
                        yAxisID: 'yLeft',
                        fill: true,
                        pointRadius: 4,
                    },
                    {
                        type: 'bar',
                        label: 'Activités réalisées',
                        data: evoData.nbActs,
                        backgroundColor: 'rgba(14,165,233,0.6)',
                        borderColor: '#0ea5e9',
                        borderWidth: 1,
                        borderRadius: 3,
                        yAxisID: 'yRight',
                    },
                ],
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { labels: { color: labelColor(), font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            label: ctx => {
                                if (ctx.dataset.yAxisID === 'yLeft') {
                                    return ' ' + ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString('fr-FR') + ' USD';
                                }
                                return ' ' + ctx.dataset.label + ': ' + ctx.parsed.y;
                            },
                        },
                    },
                },
                scales: {
                    yLeft: {
                        type: 'linear', position: 'left',
                        ticks: { color: labelColor(), callback: v => v.toLocaleString('fr-FR') },
                        grid:  { color: gridColor() },
                        title: { display: true, text: 'USD (cumulé)', color: labelColor(), font: { size: 11 } },
                    },
                    yRight: {
                        type: 'linear', position: 'right',
                        ticks: { color: labelColor(), stepSize: 1 },
                        grid:  { display: false },
                        title: { display: true, text: 'Nb activités', color: labelColor(), font: { size: 11 } },
                    },
                    x: { ticks: { color: labelColor() }, grid: { color: gridColor() } },
                },
            },
        });
    }
})();
</script>
@endsection
