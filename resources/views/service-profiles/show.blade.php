@extends('layouts.app')

@section('content')
<div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- En-tête avec actions -->
        <div class="mb-4 flex justify-between items-start">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Profil de Services</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ $serviceProfile->site->nom }} - Collecté le {{ $serviceProfile->date_collecte->format('d/m/Y') }}
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('service-profiles.index') }}" 
                   class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg transition-colors">
                    Retour
                </a>
                
                @if($serviceProfile->statut === 'brouillon' && $serviceProfile->collecteur_id === auth()->id())
                <a href="{{ route('service-profiles.edit', $serviceProfile) }}" 
                   class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Modifier
                </a>
                
                <form method="POST" action="{{ route('service-profiles.submit', $serviceProfile) }}" class="inline">
                    @csrf
                    <button type="submit" 
                            onclick="return confirm('Êtes-vous sûr de vouloir soumettre ce profil pour validation ?')"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Soumettre pour validation
                    </button>
                </form>
                @endif

                @if($serviceProfile->statut === 'soumis' && auth()->user()->role === 'super_admin')
                <form method="POST" action="{{ route('service-profiles.approve', $serviceProfile) }}" class="inline">
                    @csrf
                    <button type="submit" 
                            onclick="return confirm('Confirmer la validation de ce profil ?')"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Valider
                    </button>
                </form>
                
                <button type="button" 
                        onclick="document.getElementById('rejectModal').classList.remove('hidden')"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Rejeter
                </button>
                @endif
            </div>
        </div>

        <!-- Messages flash -->
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

        <!-- Statut et métadonnées -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Statut</p>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $serviceProfile->getStatusBadgeClass() }}">
                        {{ $serviceProfile->getStatusLabel() }}
                    </span>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Site</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $serviceProfile->site->nom }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $serviceProfile->site->commune->nom ?? '' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Collecteur</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $serviceProfile->collecteur->name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $serviceProfile->collecteur->email }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Date de collecte</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $serviceProfile->date_collecte->format('d/m/Y') }}</p>
                </div>
            </div>
        </div>

        <!-- Vue d'ensemble des services -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Vue d'ensemble</h3>
            <div class="flex flex-wrap gap-3">
                @if($serviceProfile->sante_disponible)
                <div class="flex items-center gap-2 px-4 py-2 bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    <span class="font-medium">Santé</span>
                </div>
                @endif
                
                @if($serviceProfile->education_disponible)
                <div class="flex items-center gap-2 px-4 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                    </svg>
                    <span class="font-medium">Éducation</span>
                </div>
                @endif
                
                @if($serviceProfile->wash_disponible)
                <div class="flex items-center gap-2 px-4 py-2 bg-cyan-50 dark:bg-cyan-900/20 text-cyan-800 dark:text-cyan-300 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                    <span class="font-medium">WASH</span>
                </div>
                @endif
                
                @if($serviceProfile->environnement_disponible)
                <div class="flex items-center gap-2 px-4 py-2 bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium">Environnement</span>
                </div>
                @endif
                
                @if($serviceProfile->abri_ame_disponible)
                <div class="flex items-center gap-2 px-4 py-2 bg-yellow-50 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-300 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span class="font-medium">Abri et AME</span>
                </div>
                @endif
                
                @if($serviceProfile->gestion_disponible)
                <div class="flex items-center gap-2 px-4 py-2 bg-purple-50 dark:bg-purple-900/20 text-purple-800 dark:text-purple-300 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span class="font-medium">Gestion et Coordination</span>
                </div>
                @endif
            </div>
            
            @if(!$serviceProfile->hasAnyService())
            <p class="text-gray-500 dark:text-gray-400 italic">Aucun service disponible</p>
            @endif
        </div>

        <!-- Détails par secteur -->
        <div class="space-y-6">
            <!-- Santé -->
            @if($serviceProfile->sante_disponible)
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    Santé
                </h3>
                <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @if($serviceProfile->sante_structures_fonctionnelles)
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Structures fonctionnelles</dt>
                        <dd class="text-lg font-semibold text-gray-900 dark:text-white">{{ $serviceProfile->sante_structures_fonctionnelles }}</dd>
                    </div>
                    @endif
                    @if($serviceProfile->sante_personnel_medical)
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Personnel médical</dt>
                        <dd class="text-lg font-semibold text-gray-900 dark:text-white">{{ $serviceProfile->sante_personnel_medical }}</dd>
                    </div>
                    @endif
                    @if($serviceProfile->sante_consultations_mois)
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Consultations/mois</dt>
                        <dd class="text-lg font-semibold text-gray-900 dark:text-white">{{ $serviceProfile->sante_consultations_mois }}</dd>
                    </div>
                    @endif
                </dl>
                @if($serviceProfile->sante_services_offerts && count($serviceProfile->sante_services_offerts) > 0)
                <div class="mt-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Services offerts</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($serviceProfile->sante_services_offerts as $service)
                        <span class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 text-sm rounded-full">{{ $service }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
                @if($serviceProfile->sante_observations)
                <div class="mt-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Observations</p>
                    <p class="text-sm text-gray-900 dark:text-white">{{ $serviceProfile->sante_observations }}</p>
                </div>
                @endif
            </div>
            @endif

            <!-- Éducation -->
            @if($serviceProfile->education_disponible)
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                    </svg>
                    Éducation
                </h3>
                <dl class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    @if($serviceProfile->education_ecoles_fonctionnelles)
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Écoles</dt>
                        <dd class="text-lg font-semibold text-gray-900 dark:text-white">{{ $serviceProfile->education_ecoles_fonctionnelles }}</dd>
                    </div>
                    @endif
                    @if($serviceProfile->education_enseignants)
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Enseignants</dt>
                        <dd class="text-lg font-semibold text-gray-900 dark:text-white">{{ $serviceProfile->education_enseignants }}</dd>
                    </div>
                    @endif
                    @if($serviceProfile->education_eleves_inscrits)
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Élèves</dt>
                        <dd class="text-lg font-semibold text-gray-900 dark:text-white">{{ $serviceProfile->education_eleves_inscrits }}</dd>
                    </div>
                    @endif
                    @if($serviceProfile->education_salles_classe)
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Salles de classe</dt>
                        <dd class="text-lg font-semibold text-gray-900 dark:text-white">{{ $serviceProfile->education_salles_classe }}</dd>
                    </div>
                    @endif
                </dl>
                @if($serviceProfile->education_niveaux_offerts && count($serviceProfile->education_niveaux_offerts) > 0)
                <div class="mt-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Niveaux offerts</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($serviceProfile->education_niveaux_offerts as $niveau)
                        <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 text-sm rounded-full">{{ $niveau }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
                @if($serviceProfile->education_observations)
                <div class="mt-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Observations</p>
                    <p class="text-sm text-gray-900 dark:text-white">{{ $serviceProfile->education_observations }}</p>
                </div>
                @endif
            </div>
            @endif

            <!-- WASH -->
            @if($serviceProfile->wash_disponible)
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                    WASH (Eau, Assainissement & Hygiène)
                </h3>
                <dl class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    @if($serviceProfile->wash_points_eau)
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Points d'eau</dt>
                        <dd class="text-lg font-semibold text-gray-900 dark:text-white">{{ $serviceProfile->wash_points_eau }}</dd>
                    </div>
                    @endif
                    @if($serviceProfile->wash_litres_par_personne)
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Litres/personne/jour</dt>
                        <dd class="text-lg font-semibold text-gray-900 dark:text-white">{{ $serviceProfile->wash_litres_par_personne }}</dd>
                    </div>
                    @endif
                    @if($serviceProfile->wash_latrines)
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Latrines</dt>
                        <dd class="text-lg font-semibold text-gray-900 dark:text-white">{{ $serviceProfile->wash_latrines }}</dd>
                    </div>
                    @endif
                    @if($serviceProfile->wash_douches)
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Douches</dt>
                        <dd class="text-lg font-semibold text-gray-900 dark:text-white">{{ $serviceProfile->wash_douches }}</dd>
                    </div>
                    @endif
                </dl>
                @if($serviceProfile->wash_gestion_dechets)
                <div class="mt-4">
                    <span class="inline-flex items-center px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 text-sm rounded-full">
                        ✓ Gestion des déchets en place
                    </span>
                </div>
                @endif
                @if($serviceProfile->wash_observations)
                <div class="mt-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Observations</p>
                    <p class="text-sm text-gray-900 dark:text-white">{{ $serviceProfile->wash_observations }}</p>
                </div>
                @endif
            </div>
            @endif

            <!-- Environnement -->
            @if($serviceProfile->environnement_disponible)
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Environnement
                </h3>
                <div class="space-y-4">
                    <div class="flex flex-wrap gap-2">
                        @if($serviceProfile->environnement_gestion_dechets)
                        <span class="inline-flex items-center px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 text-sm rounded-full">✓ Gestion des déchets</span>
                        @endif
                        @if($serviceProfile->environnement_drainage)
                        <span class="inline-flex items-center px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 text-sm rounded-full">✓ Drainage</span>
                        @endif
                        @if($serviceProfile->environnement_espaces_verts)
                        <span class="inline-flex items-center px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 text-sm rounded-full">✓ Espaces verts</span>
                        @endif
                    </div>
                    @if($serviceProfile->environnement_risques && count($serviceProfile->environnement_risques) > 0)
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Risques identifiés</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($serviceProfile->environnement_risques as $risque)
                            <span class="px-3 py-1 bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300 text-sm rounded-full">⚠ {{ $risque }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    @if($serviceProfile->environnement_observations)
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Observations</p>
                        <p class="text-sm text-gray-900 dark:text-white">{{ $serviceProfile->environnement_observations }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Abri et AME -->
            @if($serviceProfile->abri_ame_disponible)
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Abris et Articles Ménagers Essentiels
                </h3>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if($serviceProfile->abri_logements_fonctionnels)
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Logements fonctionnels</dt>
                        <dd class="text-lg font-semibold text-gray-900 dark:text-white">{{ $serviceProfile->abri_logements_fonctionnels }}</dd>
                    </div>
                    @endif
                    @if($serviceProfile->abri_menages_ame)
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Ménages ayant reçu AME</dt>
                        <dd class="text-lg font-semibold text-gray-900 dark:text-white">{{ $serviceProfile->abri_menages_ame }}</dd>
                    </div>
                    @endif
                </dl>
                @if($serviceProfile->abri_types && count($serviceProfile->abri_types) > 0)
                <div class="mt-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Types d'abris</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($serviceProfile->abri_types as $type)
                        <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 text-sm rounded-full">{{ $type }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
                @if($serviceProfile->abri_ame_distribues && count($serviceProfile->abri_ame_distribues) > 0)
                <div class="mt-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Articles distribués</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($serviceProfile->abri_ame_distribues as $article)
                        <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 text-sm rounded-full">{{ $article }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
                @if($serviceProfile->abri_observations)
                <div class="mt-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Observations</p>
                    <p class="text-sm text-gray-900 dark:text-white">{{ $serviceProfile->abri_observations }}</p>
                </div>
                @endif
            </div>
            @endif

            <!-- Gestion et Coordination -->
            @if($serviceProfile->gestion_disponible)
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Gestion et Coordination du Site
                </h3>
                <div class="space-y-4">
                    <div class="flex flex-wrap gap-2">
                        @if($serviceProfile->gestion_comite_site)
                        <span class="inline-flex items-center px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 text-sm rounded-full">✓ Comité de site actif</span>
                        @endif
                        @if($serviceProfile->gestion_mecanisme_plainte)
                        <span class="inline-flex items-center px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 text-sm rounded-full">✓ Mécanisme de plainte</span>
                        @endif
                    </div>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @if($serviceProfile->gestion_membres_comite)
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Membres du comité</dt>
                            <dd class="text-lg font-semibold text-gray-900 dark:text-white">{{ $serviceProfile->gestion_membres_comite }}</dd>
                        </div>
                        @endif
                        @if($serviceProfile->gestion_reunions_mois)
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Réunions/mois</dt>
                            <dd class="text-lg font-semibold text-gray-900 dark:text-white">{{ $serviceProfile->gestion_reunions_mois }}</dd>
                        </div>
                        @endif
                    </dl>
                    @if($serviceProfile->gestion_partenaires && count($serviceProfile->gestion_partenaires) > 0)
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Partenaires actifs</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($serviceProfile->gestion_partenaires as $partenaire)
                            <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 text-sm rounded-full">{{ $partenaire }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    @if($serviceProfile->gestion_observations)
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Observations</p>
                        <p class="text-sm text-gray-900 dark:text-white">{{ $serviceProfile->gestion_observations }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Notes générales -->
        @if($serviceProfile->notes_generales)
        <div class="mt-6 bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Notes générales</h3>
            <p class="text-sm text-gray-900 dark:text-white whitespace-pre-line">{{ $serviceProfile->notes_generales }}</p>
        </div>
        @endif
    </div>
</div>

<!-- Modal de rejet (super admin) -->
@if(auth()->user()->role === 'super_admin')
<div id="rejectModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white mb-4">Rejeter le profil</h3>
            <form method="POST" action="{{ route('service-profiles.reject', $serviceProfile) }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Raison du rejet</label>
                    <textarea name="raison_rejet" 
                              rows="4" 
                              required
                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" 
                            onclick="document.getElementById('rejectModal').classList.add('hidden')"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg">
                        Annuler
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg">
                        Rejeter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
