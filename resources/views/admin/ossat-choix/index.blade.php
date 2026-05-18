@extends('layouts.app')

@section('content')
<div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        {{-- En-tête --}}
        <div class="mb-4 flex flex-wrap justify-between items-center gap-3">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Listes de référence OSSAT</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    Gérez les valeurs des listes déroulantes des formulaires OSSAT.
                </p>
            </div>
            <a href="{{ route('admin.ossat-choix.create') }}"
               class="inline-flex items-center px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Nouveau choix
            </a>
        </div>

        {{-- Flash --}}
        @if(session('success'))
        <div class="mb-3 rounded bg-green-50 dark:bg-green-900/20 p-3 border border-green-200 dark:border-green-800">
            <p class="text-sm text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
        @endif
        @if(session('error'))
        <div class="mb-3 rounded bg-red-50 dark:bg-red-900/20 p-3 border border-red-200 dark:border-red-800">
            <p class="text-sm text-red-800 dark:text-red-200">{{ session('error') }}</p>
        </div>
        @endif

        {{-- Filtres --}}
        <form method="GET" class="mb-4 flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-48">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Groupe</label>
                <select name="groupe"
                        class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm text-sm focus:ring-primary-500 focus:border-primary-500">
                    <option value="">— Tous les groupes —</option>
                    @foreach($groupes as $g)
                    <option value="{{ $g }}" {{ request('groupe') === $g ? 'selected' : '' }}>{{ $g }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-48">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Recherche</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Valeur ou libellé…"
                       class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm text-sm focus:ring-primary-500 focus:border-primary-500">
            </div>
            <div class="flex gap-2">
                <button type="submit"
                        class="px-3 py-1.5 bg-gray-700 hover:bg-gray-800 text-white text-sm rounded-lg">
                    Filtrer
                </button>
                <a href="{{ route('admin.ossat-choix.index') }}"
                   class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm rounded-lg">
                    Réinitialiser
                </a>
            </div>
        </form>

        {{-- Tableau --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Groupe</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valeur</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Libellé affiché</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ordre</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Statut</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($choix as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-4 py-2 whitespace-nowrap">
                                <a href="{{ route('admin.ossat-choix.index', ['groupe' => $item->groupe]) }}"
                                   class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-semibold bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/50">
                                    {{ $item->groupe }}
                                </a>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-800 dark:text-gray-200 whitespace-nowrap">
                                {{ $item->valeur }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                {{ $item->libelle ?? '—' }}
                            </td>
                            <td class="px-4 py-2 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ $item->ordre }}
                            </td>
                            <td class="px-4 py-2 text-center">
                                <form method="POST" action="{{ route('admin.ossat-choix.toggle', $item) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold cursor-pointer transition-colors
                                                {{ $item->actif
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 hover:bg-green-200'
                                                    : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-200' }}"
                                            title="{{ $item->actif ? 'Désactiver' : 'Activer' }}">
                                        {{ $item->actif ? 'Actif' : 'Inactif' }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-4 py-2 text-right whitespace-nowrap">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.ossat-choix.edit', $item) }}"
                                       class="text-xs px-2 py-1 rounded bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/40 text-blue-700 dark:text-blue-300 font-medium transition-colors">
                                        Modifier
                                    </a>
                                    <form method="POST" action="{{ route('admin.ossat-choix.destroy', $item) }}"
                                          onsubmit="return confirm('Supprimer ce choix ?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="text-xs px-2 py-1 rounded bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/40 text-red-700 dark:text-red-300 font-medium transition-colors">
                                            Supprimer
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                                Aucun choix trouvé.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($choix->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $choix->links() }}
            </div>
            @endif
        </div>

        {{-- Résumé par groupe --}}
        @if(!request('groupe') && !request('search'))
        <div class="mt-6">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">
                Résumé par groupe
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6 gap-3">
                @foreach($groupes as $g)
                @php
                    $c = $choix->getCollection()->where('groupe', $g)->count();
                @endphp
                <a href="{{ route('admin.ossat-choix.index', ['groupe' => $g]) }}"
                   class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 hover:border-indigo-400 dark:hover:border-indigo-600 transition-colors bg-white dark:bg-gray-800">
                    <div class="text-xs font-mono text-indigo-600 dark:text-indigo-400 truncate mb-1">{{ $g }}</div>
                    <div class="text-lg font-bold text-gray-800 dark:text-gray-100">
                        {{ \App\Models\OssatChoix::where('groupe', $g)->count() }}
                    </div>
                    <div class="text-xs text-gray-400">valeurs</div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
