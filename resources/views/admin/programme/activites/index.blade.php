@extends('layouts.app')

@section('title', 'Activités')

@section('content')
<div class="space-y-6">

    {{-- ── En-tête ─────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Activités</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ $activities->total() }} activité(s) dans le cadre de programmation
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.programme.indicateurs.index') }}" class="filter-button flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Indicateurs
            </a>
            <a href="{{ route('admin.programme.import.show') }}" class="filter-button flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Import Excel
            </a>
            <a href="{{ route('admin.programme.activites.create') }}" class="primary-button flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouvelle activité
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Indicateur</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Axe</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Chef de projet</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Dates prévues</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">S. activités</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Statut</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($activities as $activity)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-4 py-3 text-sm font-mono text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $activity->reference }}</td>
                            <td class="px-4 py-3 text-sm font-mono text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $activity->code }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $activity->label }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if($activity->indicator)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                        {{ $activity->indicator->code }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ $activity->program_axis ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $activity->project_lead ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                @if($activity->planned_start_date || $activity->planned_end_date)
                                    {{ optional($activity->planned_start_date)->format('d/m/Y') ?? '?' }}
                                    →
                                    {{ optional($activity->planned_end_date)->format('d/m/Y') ?? '?' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('admin.programme.sous-activites.index', ['activite' => $activity->id]) }}"
                                   class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 hover:opacity-80">
                                    {{ $activity->sub_activities_count }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($activity->status)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ in_array(strtolower($activity->status), ['en cours', 'actif']) ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                        {{ in_array(strtolower($activity->status), ['planifie', 'planifié']) ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                        {{ in_array(strtolower($activity->status), ['termine', 'terminé']) ? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' : '' }}
                                    ">{{ $activity->status }}</span>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <a href="{{ route('admin.programme.activites.edit', $activity) }}"
                                   class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Modifier
                                </a>
                                <form method="POST" action="{{ route('admin.programme.activites.destroy', $activity) }}"
                                      class="inline" onsubmit="return confirm('Supprimer cette activité ?')">
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
                            <td colspan="9" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                Aucune activité trouvée.
                                <a href="{{ route('admin.programme.import.show') }}" class="text-emerald-600 hover:underline ml-1">Importer depuis Excel</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($activities->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $activities->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
