@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- En-tête avec statistiques -->
    <div class="mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Enregistrement Niveau 2 - Membres des Ménages</h1>
                <p class="text-gray-600 mt-2">Gestion détaillée de tous les membres des ménages</p>
            </div>
            <a href="{{ route('households.index') }}" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-md flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Voir Niveau 1
            </a>
        </div>

        <!-- Cartes de statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-sm font-medium uppercase">Ménages Niveau 2</p>
                        <p class="text-3xl font-bold mt-2">{{ $totalMenages }}</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm font-medium uppercase">Total Membres</p>
                        <p class="text-3xl font-bold mt-2">{{ $totalMembres }}</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium uppercase">Moyenne par Ménage</p>
                        <p class="text-3xl font-bold mt-2">{{ $totalMenages > 0 ? round($totalMembres / $totalMenages, 1) : 0 }}</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-indigo-100 text-sm font-medium uppercase">Données Biométriques</p>
                        <p class="text-3xl font-bold mt-2">{{ $totalMembres }}</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres de recherche -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" action="{{ route('households.level2.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Site</label>
                <select name="site_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Tous les sites</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" {{ request('site_id') == $site->id ? 'selected' : '' }}>
                            {{ $site->nom }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                <select name="statut" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Tous les statuts</option>
                    <option value="en_cours" {{ request('statut') == 'en_cours' ? 'selected' : '' }}>En cours</option>
                    <option value="complet" {{ request('statut') == 'complet' ? 'selected' : '' }}>Complet</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                    placeholder="Nom du chef ou numéro..." 
                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Filtrer
                </button>
                <a href="{{ route('households.level2.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </a>
            </div>
        </form>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
            {{ session('success') }}
        </div>
    @endif

    <!-- Liste des ménages avec leurs membres -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        @if($households->isEmpty())
            <div class="text-center py-12">
                <svg class="w-24 h-24 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <p class="text-gray-600 text-lg mb-4">Aucun ménage de Niveau 2 trouvé</p>
                <a href="{{ route('households.index') }}" class="text-blue-600 hover:text-blue-800">
                    → Passer des ménages au Niveau 2
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-purple-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                Ménage
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                Site
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                Membres
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                Complétude
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                Statut
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($households as $household)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        @if($household->chef_photo)
                                            <img src="{{ asset('storage/' . $household->chef_photo) }}" 
                                                alt="Photo" class="w-12 h-12 rounded-full object-cover mr-3">
                                        @else
                                            <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center mr-3">
                                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="font-medium text-gray-900">{{ $household->chef_nom_complet }}</div>
                                            <div class="text-sm text-gray-500">{{ $household->numero_menage }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $household->site->nom ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        <span class="font-semibold text-gray-900">{{ $household->members->count() }}</span>
                                        <span class="text-gray-500 mx-1">/</span>
                                        <span class="text-gray-600">{{ $household->nombre_total_personnes }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $progress = $household->nombre_total_personnes > 0 
                                            ? round(($household->members->count() / $household->nombre_total_personnes) * 100) 
                                            : 0;
                                        $isComplete = $household->members->count() >= $household->nombre_total_personnes;
                                    @endphp
                                    <div class="flex items-center">
                                        <div class="flex-1 bg-gray-200 rounded-full h-2 mr-2">
                                            <div class="h-2 rounded-full {{ $isComplete ? 'bg-green-600' : 'bg-yellow-500' }}" 
                                                style="width: {{ min($progress, 100) }}%"></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-700">{{ $progress }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full 
                                        {{ $household->statut === 'complet' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $household->statut === 'complet' ? 'Complet' : 'En cours' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('households.show', $household) }}" 
                                            class="text-blue-600 hover:text-blue-800" title="Voir détails">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        <a href="{{ route('households.members.create', $household) }}" 
                                            class="text-green-600 hover:text-green-800" title="Ajouter membre">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                            </svg>
                                        </a>
                                        <a href="{{ route('households.edit', $household) }}" 
                                            class="text-orange-600 hover:text-orange-800" title="Modifier">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $households->links() }}
            </div>
        @endif
    </div>

    <!-- Info Box -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700">
                    <strong>Niveau 2 :</strong> Enregistrement détaillé de chaque membre du ménage avec biométrie complète (photo + empreinte digitale).
                    Pour passer un ménage au Niveau 2, accédez à la liste des <a href="{{ route('households.index') }}" class="font-semibold underline">ménages Niveau 1</a>.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
