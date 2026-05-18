@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Rapports OSSAT</h1>
            <p class="text-gray-500 mt-1">Suivi des Sites d'Accueil Temporaire – RDC</p>
        </div>
        <a href="{{ route('ossat.create') }}"
            class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nouveau rapport
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded mb-4">
        {{ session('success') }}
    </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Site</th>
                    <th class="px-4 py-3 text-left">Province</th>
                    <th class="px-4 py-3 text-left">Type</th>
                    <th class="px-4 py-3 text-left">Statut site</th>
                    <th class="px-4 py-3 text-left">Validation</th>
                    <th class="px-4 py-3 text-left">Date</th>
                    <th class="px-4 py-3 text-left">Enquêteur</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($reports as $report)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $report->site_nom ?? '–' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $report->province->name ?? '–' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $report->type_installation ?? '–' }}</td>
                    <td class="px-4 py-3">
                        @php
                            $statClasses = ['fonctionnel'=>'bg-green-100 text-green-700', 'non_fonctionnel'=>'bg-red-100 text-red-700', 'en_attente'=>'bg-yellow-100 text-yellow-700'];
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statClasses[$report->statut] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst(str_replace('_', ' ', $report->statut ?? '–')) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $valClasses = ['brouillon'=>'bg-gray-100 text-gray-600','soumis'=>'bg-blue-100 text-blue-700','valide'=>'bg-green-100 text-green-700','rejete'=>'bg-red-100 text-red-700'];
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $valClasses[$report->statut_validation] ?? '' }}">
                            {{ ucfirst($report->statut_validation) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $report->today?->format('d/m/Y') ?? $report->created_at->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $report->enumerator_name ?? '–' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('ossat.show', $report) }}" class="text-blue-600 hover:text-blue-800 text-xs">Voir</a>
                            <a href="{{ route('ossat.edit', $report) }}" class="text-yellow-600 hover:text-yellow-800 text-xs">Éditer</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">Aucun rapport. Créez le premier.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3">
            {{ $reports->links() }}
        </div>
    </div>
</div>
@endsection
