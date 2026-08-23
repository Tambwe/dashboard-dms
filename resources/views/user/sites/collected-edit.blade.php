@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Modifier site collecté</h1>
            <p class="text-sm text-gray-600">Mettez à jour les informations synchronisées et la géographie.</p>
        </div>
        <a href="{{ route('user.sites.collected.show', $siteGeography) }}" class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100">Annuler</a>
    </div>

    <form method="POST" action="{{ route('user.sites.collected.update', $siteGeography) }}" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-5">
        @csrf
        @method('PUT')

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Date collecte</label>
                <input type="datetime-local" name="collected_at" value="{{ old('collected_at', optional($siteGeography->collected_at)->format('Y-m-d\TH:i')) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @error('collected_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Précision (m)</label>
                <input type="number" step="0.01" min="0" name="accuracy_meters" value="{{ old('accuracy_meters', $siteGeography->accuracy_meters) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @error('accuracy_meters') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Catégorie point</label>
                <input type="text" name="point_category" value="{{ old('point_category', $siteGeography->point_category) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @error('point_category') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Précision catégorie point (autre)</label>
                <input type="text" name="point_category_other" value="{{ old('point_category_other', $siteGeography->point_category_other) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @error('point_category_other') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Catégorie polygone</label>
                <input type="text" name="polygon_category" value="{{ old('polygon_category', $siteGeography->polygon_category) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @error('polygon_category') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Nom du bloc</label>
                <input type="text" name="polygon_block_name" value="{{ old('polygon_block_name', $siteGeography->polygon_block_name) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @error('polygon_block_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-semibold text-gray-700">GeoJSON</label>
            <textarea name="geojson_data" rows="14" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-xs">{{ old('geojson_data', json_encode($siteGeography->geojson_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) }}</textarea>
            @error('geojson_data') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-primary-600 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700">Enregistrer</button>
            <a href="{{ route('user.sites.collected.show', $siteGeography) }}" class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100">Annuler</a>
        </div>
    </form>
</div>
@endsection
