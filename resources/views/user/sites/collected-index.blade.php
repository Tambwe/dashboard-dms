@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Sites collectés synchronisés</h1>
            <p class="text-sm text-gray-600">Données de collecte géographique synchronisées depuis mobile.</p>
            <div class="mt-3 inline-flex rounded-lg border border-gray-200 bg-white p-1 shadow-sm">
                <a href="{{ route('user.sites.index') }}" class="rounded-md px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100">
                    Mes sites
                </a>
                <a href="{{ route('user.sites.collected.index') }}" class="rounded-md bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white">
                    Sites collectés
                </a>
            </div>
        </div>
        <a href="{{ route('user.sites.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100">
            Retour à Mes Sites
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <form method="GET" class="mb-5 grid grid-cols-1 gap-3 md:grid-cols-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher site, code, catégorie..." class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <select name="geometry_type" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">Tous les types</option>
            <option value="point" @selected(request('geometry_type') === 'point')>Point</option>
            <option value="polygon" @selected(request('geometry_type') === 'polygon')>Polygone</option>
        </select>
        <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">Filtrer</button>
        <a href="{{ route('user.sites.collected.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-center text-sm font-semibold text-gray-700 hover:bg-gray-100">Réinitialiser</a>
    </form>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Site</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Type</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Catégorie</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Collecté le</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($geographies as $item)
                    <tr>
                        <td class="px-4 py-3 text-gray-900">
                            <div class="font-semibold">{{ $item->site->nom ?? 'Site supprimé' }}</div>
                            <div class="text-xs text-gray-500">{{ $item->site->code_site ?? '-' }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $item->geometry_type ?: '-' }}</td>
                        <td class="px-4 py-3 text-gray-700">
                            @if($item->geometry_type === 'point')
                                {{ $item->point_category ?: '-' }}
                            @else
                                {{ $item->polygon_category ?: '-' }}{{ $item->polygon_block_name ? ' ('.$item->polygon_block_name.')' : '' }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ optional($item->collected_at)->format('Y-m-d H:i') ?: '-' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('user.sites.collected.show', $item) }}" class="rounded-md border border-gray-300 px-2 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-100">Voir</a>
                                <a href="{{ route('user.sites.collected.edit', $item) }}" class="rounded-md border border-primary-200 px-2 py-1 text-xs font-semibold text-primary-700 hover:bg-primary-50">Modifier</a>
                                <form method="POST" action="{{ route('user.sites.collected.destroy', $item) }}" onsubmit="return confirm('Supprimer cette géographie synchronisée ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-md border border-red-200 px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-50">Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">Aucune donnée synchronisée trouvée.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $geographies->links() }}
    </div>
</div>
@endsection
