@extends('layouts.app')

@section('content')
<div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- En-tête -->
        <div class="mb-4">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                {{ isset($serviceProfile) ? 'Modifier le profil de services' : 'Nouvelle collecte de profil de services' }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Collectez les données sur tous les services disponibles dans le site
            </p>
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

        @if ($errors->any())
        <div class="mb-3 rounded bg-red-50 dark:bg-red-900/20 p-3 border border-red-200 dark:border-red-800">
            <p class="text-sm font-medium text-red-800 dark:text-red-200 mb-2">Erreurs de validation :</p>
            <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-300">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ isset($serviceProfile) ? route('service-profiles.update', $serviceProfile) : route('service-profiles.store') }}" class="space-y-6">
            @csrf
            @if(isset($serviceProfile))
                @method('PUT')
            @endif

            <!-- Informations générales -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informations générales</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Site -->
                    <div>
                        <label for="site_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Site <span class="text-red-500">*</span>
                        </label>
                        <select id="site_id" name="site_id" required
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                            <option value="">Sélectionner un site...</option>
                            @foreach($sites as $site)
                            <option value="{{ $site->id }}" 
                                    {{ (old('site_id', $selectedSite->id ?? $serviceProfile->site_id ?? '') == $site->id) ? 'selected' : '' }}>
                                {{ $site->nom }}{{ $site->date_fermeture ? ' [Site fermé]' : '' }} - {{ $site->commune->nom ?? '' }} ({{ $site->organisation->nom ?? 'Sans organisation' }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date de collecte -->
                    <div>
                        <label for="date_collecte" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Date de collecte <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               id="date_collecte" 
                               name="date_collecte" 
                               value="{{ old('date_collecte', isset($serviceProfile) ? $serviceProfile->date_collecte->format('Y-m-d') : date('Y-m-d')) }}"
                               required
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
            </div>

            <!-- SANTÉ -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex items-center mb-4">
                    <input type="checkbox" 
                           id="sante_disponible" 
                           name="sante_disponible" 
                           value="1"
                           {{ old('sante_disponible', $serviceProfile->sante_disponible ?? false) ? 'checked' : '' }}
                           class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                           onchange="toggleSection('sante')">
                    <label for="sante_disponible" class="ml-2 text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-6 h-6 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                        Services de Santé disponibles
                    </label>
                </div>

                <div id="sante-section" class="grid grid-cols-1 md:grid-cols-2 gap-6" style="display: {{ old('sante_disponible', $serviceProfile->sante_disponible ?? false) ? 'grid' : 'none' }}">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Structures fonctionnelles
                        </label>
                        <input type="number" 
                               name="sante_structures_fonctionnelles" 
                               value="{{ old('sante_structures_fonctionnelles', $serviceProfile->sante_structures_fonctionnelles ?? '') }}"
                               min="0"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Personnel médical
                        </label>
                        <input type="number" 
                               name="sante_personnel_medical" 
                               value="{{ old('sante_personnel_medical', $serviceProfile->sante_personnel_medical ?? '') }}"
                               min="0"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Consultations par mois
                        </label>
                        <input type="number" 
                               name="sante_consultations_mois" 
                               value="{{ old('sante_consultations_mois', $serviceProfile->sante_consultations_mois ?? '') }}"
                               min="0"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Services offerts
                        </label>
                        <div class="space-y-2">
                            @php
                            $services = ['Consultation générale', 'Vaccination', 'Soins prénatals', 'Planning familial', 'Nutrition', 'Laboratoire'];
                            $selectedServices = old('sante_services_offerts', $serviceProfile->sante_services_offerts ?? []);
                            @endphp
                            @foreach($services as $service)
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="sante_services_offerts[]" 
                                       value="{{ $service }}"
                                       {{ in_array($service, $selectedServices) ? 'checked' : '' }}
                                       class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $service }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Observations
                        </label>
                        <textarea name="sante_observations" 
                                  rows="3"
                                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">{{ old('sante_observations', $serviceProfile->sante_observations ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- ÉDUCATION -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex items-center mb-4">
                    <input type="checkbox" 
                           id="education_disponible" 
                           name="education_disponible" 
                           value="1"
                           {{ old('education_disponible', $serviceProfile->education_disponible ?? false) ? 'checked' : '' }}
                           class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                           onchange="toggleSection('education')">
                    <label for="education_disponible" class="ml-2 text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/>
                        </svg>
                        Services d'Éducation disponibles
                    </label>
                </div>

                <div id="education-section" class="grid grid-cols-1 md:grid-cols-2 gap-6" style="display: {{ old('education_disponible', $serviceProfile->education_disponible ?? false) ? 'grid' : 'none' }}">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Écoles fonctionnelles
                        </label>
                        <input type="number" 
                               name="education_ecoles_fonctionnelles" 
                               value="{{ old('education_ecoles_fonctionnelles', $serviceProfile->education_ecoles_fonctionnelles ?? '') }}"
                               min="0"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nombre d'enseignants
                        </label>
                        <input type="number" 
                               name="education_enseignants" 
                               value="{{ old('education_enseignants', $serviceProfile->education_enseignants ?? '') }}"
                               min="0"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Élèves inscrits
                        </label>
                        <input type="number" 
                               name="education_eleves_inscrits" 
                               value="{{ old('education_eleves_inscrits', $serviceProfile->education_eleves_inscrits ?? '') }}"
                               min="0"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Salles de classe
                        </label>
                        <input type="number" 
                               name="education_salles_classe" 
                               value="{{ old('education_salles_classe', $serviceProfile->education_salles_classe ?? '') }}"
                               min="0"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Niveaux offerts
                        </label>
                        <div class="space-y-2">
                            @php
                            $niveaux = ['Préscolaire', 'Primaire', 'Secondaire', 'Professionnel'];
                            $selectedNiveaux = old('education_niveaux_offerts', $serviceProfile->education_niveaux_offerts ?? []);
                            @endphp
                            @foreach($niveaux as $niveau)
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="education_niveaux_offerts[]" 
                                       value="{{ $niveau }}"
                                       {{ in_array($niveau, $selectedNiveaux) ? 'checked' : '' }}
                                       class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $niveau }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Observations
                        </label>
                        <textarea name="education_observations" 
                                  rows="3"
                                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">{{ old('education_observations', $serviceProfile->education_observations ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- WASH (Water, Sanitation and Hygiene) -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex items-center mb-4">
                    <input type="checkbox" 
                           id="wash_disponible" 
                           name="wash_disponible" 
                           value="1"
                           {{ old('wash_disponible', $serviceProfile->wash_disponible ?? false) ? 'checked' : '' }}
                           class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                           onchange="toggleSection('wash')">
                    <label for="wash_disponible" class="ml-2 text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-6 h-6 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                        Services WASH (Eau, Assainissement & Hygiène) disponibles
                    </label>
                </div>

                <div id="wash-section" class="grid grid-cols-1 md:grid-cols-2 gap-6" style="display: {{ old('wash_disponible', $serviceProfile->wash_disponible ?? false) ? 'grid' : 'none' }}">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Points d'eau
                        </label>
                        <input type="number" 
                               name="wash_points_eau" 
                               value="{{ old('wash_points_eau', $serviceProfile->wash_points_eau ?? '') }}"
                               min="0"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Litres par personne/jour
                        </label>
                        <input type="number" 
                               name="wash_litres_par_personne" 
                               value="{{ old('wash_litres_par_personne', $serviceProfile->wash_litres_par_personne ?? '') }}"
                               min="0"
                               step="0.1"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Latrines
                        </label>
                        <input type="number" 
                               name="wash_latrines" 
                               value="{{ old('wash_latrines', $serviceProfile->wash_latrines ?? '') }}"
                               min="0"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Douches
                        </label>
                        <input type="number" 
                               name="wash_douches" 
                               value="{{ old('wash_douches', $serviceProfile->wash_douches ?? '') }}"
                               min="0"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div class="md:col-span-2">
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="wash_gestion_dechets" 
                                   value="1"
                                   {{ old('wash_gestion_dechets', $serviceProfile->wash_gestion_dechets ?? false) ? 'checked' : '' }}
                                   class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Système de gestion des déchets en place</span>
                        </label>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Observations
                        </label>
                        <textarea name="wash_observations" 
                                  rows="3"
                                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">{{ old('wash_observations', $serviceProfile->wash_observations ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- ENVIRONNEMENT -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex items-center mb-4">
                    <input type="checkbox" 
                           id="environnement_disponible" 
                           name="environnement_disponible" 
                           value="1"
                           {{ old('environnement_disponible', $serviceProfile->environnement_disponible ?? false) ? 'checked' : '' }}
                           class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                           onchange="toggleSection('environnement')">
                    <label for="environnement_disponible" class="ml-2 text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-6 h-6 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Gestion de l'Environnement
                    </label>
                </div>

                <div id="environnement-section" class="space-y-4" style="display: {{ old('environnement_disponible', $serviceProfile->environnement_disponible ?? false) ? 'block' : 'none' }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="environnement_gestion_dechets" 
                                   value="1"
                                   {{ old('environnement_gestion_dechets', $serviceProfile->environnement_gestion_dechets ?? false) ? 'checked' : '' }}
                                   class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Gestion des déchets solides</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="environnement_drainage" 
                                   value="1"
                                   {{ old('environnement_drainage', $serviceProfile->environnement_drainage ?? false) ? 'checked' : '' }}
                                   class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Système de drainage</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="environnement_espaces_verts" 
                                   value="1"
                                   {{ old('environnement_espaces_verts', $serviceProfile->environnement_espaces_verts ?? false) ? 'checked' : '' }}
                                   class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Espaces verts disponibles</span>
                        </label>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Risques environnementaux identifiés
                        </label>
                        <div class="space-y-2">
                            @php
                            $risques = ['Inondation', 'Érosion', 'Éboulement', 'Pollution de l\'eau', 'Déforestation', 'Accumulation de déchets'];
                            $selectedRisques = old('environnement_risques', $serviceProfile->environnement_risques ?? []);
                            @endphp
                            @foreach($risques as $risque)
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="environnement_risques[]" 
                                       value="{{ $risque }}"
                                       {{ in_array($risque, $selectedRisques) ? 'checked' : '' }}
                                       class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $risque }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Observations
                        </label>
                        <textarea name="environnement_observations" 
                                  rows="3"
                                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">{{ old('environnement_observations', $serviceProfile->environnement_observations ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- ABRI ET AME (Articles Ménagers Essentiels) -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex items-center mb-4">
                    <input type="checkbox" 
                           id="abri_ame_disponible" 
                           name="abri_ame_disponible" 
                           value="1"
                           {{ old('abri_ame_disponible', $serviceProfile->abri_ame_disponible ?? false) ? 'checked' : '' }}
                           class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                           onchange="toggleSection('abri_ame')">
                    <label for="abri_ame_disponible" class="ml-2 text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-6 h-6 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Abris et Articles Ménagers Essentiels (AME)
                    </label>
                </div>

                <div id="abri_ame-section" class="grid grid-cols-1 md:grid-cols-2 gap-6" style="display: {{ old('abri_ame_disponible', $serviceProfile->abri_ame_disponible ?? false) ? 'grid' : 'none' }}">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Logements fonctionnels
                        </label>
                        <input type="number" 
                               name="abri_logements_fonctionnels" 
                               value="{{ old('abri_logements_fonctionnels', $serviceProfile->abri_logements_fonctionnels ?? '') }}"
                               min="0"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Ménages ayant reçu des AME
                        </label>
                        <input type="number" 
                               name="abri_menages_ame" 
                               value="{{ old('abri_menages_ame', $serviceProfile->abri_menages_ame ?? '') }}"
                               min="0"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Types d'abris
                        </label>
                        <div class="space-y-2">
                            @php
                            $types = ['Tente', 'Habitation durable', 'Abri temporaire', 'Maison d\'accueil'];
                            $selectedTypes = old('abri_types', $serviceProfile->abri_types ?? []);
                            @endphp
                            @foreach($types as $type)
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="abri_types[]" 
                                       value="{{ $type }}"
                                       {{ in_array($type, $selectedTypes) ? 'checked' : '' }}
                                       class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $type }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Articles distribués
                        </label>
                        <div class="space-y-2">
                            @php
                            $articles = ['Couvertures', 'Ustensiles de cuisine', 'Nattes de couchage', 'Moustiquaires', 'Jerrycans'];
                            $selectedArticles = old('abri_ame_distribues', $serviceProfile->abri_ame_distribues ?? []);
                            @endphp
                            @foreach($articles as $article)
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="abri_ame_distribues[]" 
                                       value="{{ $article }}"
                                       {{ in_array($article, $selectedArticles) ? 'checked' : '' }}
                                       class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $article }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Observations
                        </label>
                        <textarea name="abri_observations" 
                                  rows="3"
                                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">{{ old('abri_observations', $serviceProfile->abri_observations ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- GESTION ET COORDINATION DU SITE -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex items-center mb-4">
                    <input type="checkbox" 
                           id="gestion_disponible" 
                           name="gestion_disponible" 
                           value="1"
                           {{ old('gestion_disponible', $serviceProfile->gestion_disponible ?? false) ? 'checked' : '' }}
                           class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                           onchange="toggleSection('gestion')">
                    <label for="gestion_disponible" class="ml-2 text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-6 h-6 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Gestion et Coordination du Site
                    </label>
                </div>

                <div id="gestion-section" class="space-y-6" style="display: {{ old('gestion_disponible', $serviceProfile->gestion_disponible ?? false) ? 'block' : 'none' }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="gestion_comite_site" 
                                   value="1"
                                   {{ old('gestion_comite_site', $serviceProfile->gestion_comite_site ?? false) ? 'checked' : '' }}
                                   class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Comité de site actif</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="gestion_mecanisme_plainte" 
                                   value="1"
                                   {{ old('gestion_mecanisme_plainte', $serviceProfile->gestion_mecanisme_plainte ?? false) ? 'checked' : '' }}
                                   class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Mécanisme de plainte fonctionnel</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Membres du comité
                            </label>
                            <input type="number" 
                                   name="gestion_membres_comite" 
                                   value="{{ old('gestion_membres_comite', $serviceProfile->gestion_membres_comite ?? '') }}"
                                   min="0"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Réunions par mois
                            </label>
                            <input type="number" 
                                   name="gestion_reunions_mois" 
                                   value="{{ old('gestion_reunions_mois', $serviceProfile->gestion_reunions_mois ?? '') }}"
                                   min="0"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Partenaires actifs sur le site
                        </label>
                        <div class="space-y-2">
                            @php
                            $partenaires = ['HCR', 'UNICEF', 'PAM', 'OMS', 'OCHA', 'ONG locales', 'ONG internationales', 'Gouvernement'];
                            $selectedPartenaires = old('gestion_partenaires', $serviceProfile->gestion_partenaires ?? []);
                            @endphp
                            @foreach($partenaires as $partenaire)
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="gestion_partenaires[]" 
                                       value="{{ $partenaire }}"
                                       {{ in_array($partenaire, $selectedPartenaires) ? 'checked' : '' }}
                                       class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $partenaire }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Observations
                        </label>
                        <textarea name="gestion_observations" 
                                  rows="3"
                                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">{{ old('gestion_observations', $serviceProfile->gestion_observations ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Notes générales -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Notes générales</h3>
                <textarea name="notes_generales" 
                          rows="5"
                          placeholder="Ajoutez ici toute information complémentaire importante..."
                          class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">{{ old('notes_generales', $serviceProfile->notes_generales ?? '') }}</textarea>
            </div>

            <!-- Boutons d'action -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('service-profiles.index') }}" 
                   class="px-6 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium rounded-lg transition-colors">
                    Annuler
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors">
                    {{ isset($serviceProfile) ? 'Mettre à jour' : 'Enregistrer' }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSection(sectionName) {
    const checkbox = document.getElementById(sectionName + '_disponible');
    const section = document.getElementById(sectionName + '-section');
    
    if (checkbox.checked) {
        section.style.display = sectionName === 'environnement' || sectionName === 'gestion' ? 'block' : 'grid';
    } else {
        section.style.display = 'none';
    }
}
</script>
@endsection
