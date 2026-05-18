@extends('layouts.app')

@section('title', 'Indicateurs')

@section('content')
<div class="space-y-6">

    {{-- ── En-tête ─────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Indicateurs</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ $indicators->total() }} indicateur(s) dans le cadre de programmation
            </p>
        </div>
        <a href="{{ route('admin.programme.import.show') }}" class="filter-button flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            Import Excel
        </a>
        <a href="{{ route('admin.programme.indicateurs.create') }}" class="primary-button flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nouvel indicateur
        </a>
    </div>

    {{-- ── Tableau ──────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Référence</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Libellé</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Unité</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fréquence</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Responsable</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Activités</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actif</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($indicators as $indicator)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-4 py-3 text-sm font-mono text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $indicator->reference }}</td>
                            <td class="px-4 py-3 text-sm font-mono text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $indicator->code }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $indicator->label }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ $indicator->unit ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ $indicator->frequency ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $indicator->owner ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('admin.programme.activites.index', ['indicateur' => $indicator->id]) }}"
                                   class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 hover:opacity-80">
                                    {{ $indicator->activities_count }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($indicator->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">Actif</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">Inactif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <a href="{{ route('admin.programme.indicateurs.edit', $indicator) }}"
                                   class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Modifier
                                </a>
                                <form method="POST" action="{{ route('admin.programme.indicateurs.destroy', $indicator) }}"
                                      class="inline" onsubmit="return confirm('Supprimer cet indicateur ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        Supprimer
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                Aucun indicateur trouvé.
                                <a href="{{ route('admin.programme.import.show') }}" class="text-emerald-600 hover:underline ml-1">Importer depuis Excel</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($indicators->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $indicators->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
