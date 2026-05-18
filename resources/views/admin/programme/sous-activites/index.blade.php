@extends('layouts.app')

@section('title', 'Sous-activités')

@section('content')
<div class="space-y-6">

    {{-- ── En-tête ─────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Sous-activités</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ $subActivities->total() }} sous-activité(s) dans le cadre de programmation
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.programme.activites.index') }}" class="filter-button flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h7" />
                </svg>
                Activités
            </a>
            <a href="{{ route('admin.programme.import.show') }}" class="filter-button flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Import Excel
            </a>
            <a href="{{ route('admin.programme.sous-activites.create') }}" class="primary-button flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouvelle sous-activité
            </a>
        </div>
    </div>

    {{-- ── Alertes ─────────────────────────────────────────────────── --}}
    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 flex">
            <svg class="h-5 w-5 text-green-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <p class="ml-3 text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 flex">
            <svg class="h-5 w-5 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <p class="ml-3 text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
        </div>
    @endif

    {{-- ── Tableau ──────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Référence</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Libellé</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Activité parente</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Site / Province</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Territoire / Zone</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Dates prévues</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Statut</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($subActivities as $sub)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-4 py-3 text-sm font-mono text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $sub->reference }}</td>
                            <td class="px-4 py-3 text-sm font-mono text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $sub->code }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $sub->label }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if($sub->activity)
                                    <div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                            {{ $sub->activity->code }}
                                        </span>
                                        @if($sub->activity->indicator)
                                            <br>
                                            <span class="text-xs text-gray-400 font-mono">{{ $sub->activity->indicator->code }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                @if($sub->site_name || $sub->province)
                                    @if($sub->site_name)
                                        <div class="font-medium">{{ $sub->site_name }}</div>
                                    @endif
                                    @if($sub->province)
                                        <div class="text-xs text-gray-400">{{ $sub->province }}</div>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                @if($sub->territoire || $sub->health_zone)
                                    @if($sub->territoire)
                                        <div>{{ $sub->territoire }}</div>
                                    @endif
                                    @if($sub->health_zone)
                                        <div class="text-xs text-gray-400">{{ $sub->health_zone }}</div>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                @if($sub->planned_start_date || $sub->planned_end_date)
                                    {{ optional($sub->planned_start_date)->format('d/m/Y') ?? '?' }}
                                    →
                                    {{ optional($sub->planned_end_date)->format('d/m/Y') ?? '?' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($sub->status)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ in_array(strtolower($sub->status), ['en cours', 'actif']) ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                        {{ in_array(strtolower($sub->status), ['planifie', 'planifié']) ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                        {{ in_array(strtolower($sub->status), ['termine', 'terminé']) ? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' : '' }}
                                    ">{{ $sub->status }}</span>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <a href="{{ route('admin.programme.sous-activites.edit', $sub) }}"
                                   class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Modifier
                                </a>
                                <form method="POST" action="{{ route('admin.programme.sous-activites.destroy', $sub) }}"
                                      class="inline" onsubmit="return confirm('Supprimer cette sous-activité ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-600 hover:text-red-800 dark:text-red-400">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        Supprimer
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                Aucune sous-activité trouvée.
                                <a href="{{ route('admin.programme.import.show') }}" class="text-emerald-600 hover:underline ml-1">Importer depuis Excel</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($subActivities->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $subActivities->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
