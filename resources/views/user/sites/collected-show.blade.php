@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Détail site collecté</h1>
            <p class="text-sm text-gray-600">Visualisation des données synchronisées et de la géographie.</p>
        </div>
        <a href="{{ route('user.sites.collected.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100">Retour</a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="mb-3 text-base font-semibold text-gray-900">Informations</h2>
            <dl class="grid grid-cols-1 gap-2 text-sm">
                <div><span class="font-semibold text-gray-700">Site:</span> {{ $siteGeography->site->nom ?? 'Site supprimé' }} ({{ $siteGeography->site->code_site ?? '-' }})</div>
                <div><span class="font-semibold text-gray-700">Type:</span> {{ $siteGeography->geometry_type ?: '-' }}</div>
                <div><span class="font-semibold text-gray-700">Point catégorie:</span> {{ $siteGeography->point_category ?: '-' }}</div>
                <div><span class="font-semibold text-gray-700">Polygone catégorie:</span> {{ $siteGeography->polygon_category ?: '-' }}</div>
                <div><span class="font-semibold text-gray-700">Nom bloc:</span> {{ $siteGeography->polygon_block_name ?: '-' }}</div>
                <div><span class="font-semibold text-gray-700">Précision:</span> {{ $siteGeography->accuracy_meters ?? '-' }} m</div>
                <div><span class="font-semibold text-gray-700">Latitude:</span> {{ $siteGeography->latitude ?? '-' }}</div>
                <div><span class="font-semibold text-gray-700">Longitude:</span> {{ $siteGeography->longitude ?? '-' }}</div>
                <div><span class="font-semibold text-gray-700">Collecté le:</span> {{ optional($siteGeography->collected_at)->format('Y-m-d H:i') ?: '-' }}</div>
                <div><span class="font-semibold text-gray-700">Synchronisé:</span> {{ optional($siteGeography->submission?->synced_at)->format('Y-m-d H:i') ?: '-' }}</div>
            </dl>
            <div class="mt-4 flex gap-2">
                <a href="{{ route('user.sites.collected.edit', $siteGeography) }}" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">Modifier</a>
                <form method="POST" action="{{ route('user.sites.collected.destroy', $siteGeography) }}" onsubmit="return confirm('Supprimer cette géographie synchronisée ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Supprimer</button>
                </form>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="mb-3 text-base font-semibold text-gray-900">Carte</h2>
            <div id="collected-map" class="h-80 w-full rounded-lg border border-gray-200"></div>
        </div>
    </div>
</div>

<script>
    (() => {
        const mapElement = document.getElementById('collected-map');
        if (!mapElement || typeof L === 'undefined') {
            return;
        }

        const geojsonData = @json($siteGeography->geojson_data);
        const lat = Number(@json($siteGeography->latitude));
        const lon = Number(@json($siteGeography->longitude));
        const map = L.map(mapElement).setView([Number.isFinite(lat) ? lat : -0.8611, Number.isFinite(lon) ? lon : 29.2333], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        let hasLayer = false;
        if (geojsonData && typeof geojsonData === 'object') {
            const layer = L.geoJSON(geojsonData).addTo(map);
            const bounds = layer.getBounds();
            if (bounds.isValid()) {
                map.fitBounds(bounds.pad(0.2));
            }
            hasLayer = true;
        }

        if (!hasLayer && Number.isFinite(lat) && Number.isFinite(lon)) {
            L.marker([lat, lon]).addTo(map);
            map.setView([lat, lon], 15);
        }
    })();
</script>
@endsection
