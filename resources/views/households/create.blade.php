@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-6xl">
    <div class="mb-6">
        <a href="{{ route('households.index') }}" class="text-blue-600 hover:text-blue-800 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour à la liste
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-md p-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Enregistrement de Ménage - Niveau 1</h1>
            <p class="text-gray-600 mt-2">Enregistrez les informations du chef de ménage et le nombre de personnes</p>
        </div>

        <form method="POST" action="{{ route('households.store') }}" id="householdForm">
            @csrf

            <!-- Site -->
            <div class="mb-8 p-6 bg-gray-50 rounded-lg">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Localisation</h2>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Site *</label>
                        <select name="site_id" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('site_id') border-red-500 @enderror">
                            <option value="">Sélectionnez un site</option>
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}" {{ old('site_id') == $site->id ? 'selected' : '' }}>
                                    {{ $site->nom }}{{ $site->date_fermeture ? ' [Site fermé]' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('site_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Informations du Chef de Ménage -->
            <div class="mb-8 p-6 bg-blue-50 rounded-lg">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Informations du Chef de Ménage</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nom *</label>
                        <input type="text" name="chef_nom" value="{{ old('chef_nom') }}" required 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('chef_nom') border-red-500 @enderror">
                        @error('chef_nom')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Postnom</label>
                        <input type="text" name="chef_postnom" value="{{ old('chef_postnom') }}" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Prénom</label>
                        <input type="text" name="chef_prenom" value="{{ old('chef_prenom') }}" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sexe *</label>
                        <select name="chef_sexe" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Sélectionner</option>
                            <option value="M" {{ old('chef_sexe') == 'M' ? 'selected' : '' }}>Masculin</option>
                            <option value="F" {{ old('chef_sexe') == 'F' ? 'selected' : '' }}>Féminin</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date de naissance</label>
                        <input type="date" name="chef_date_naissance" value="{{ old('chef_date_naissance') }}" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Âge</label>
                        <input type="number" name="chef_age" value="{{ old('chef_age') }}" min="0" max="150"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">État civil</label>
                        <select name="chef_etat_civil" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Sélectionner</option>
                            <option value="Célibataire" {{ old('chef_etat_civil') == 'Célibataire' ? 'selected' : '' }}>Célibataire</option>
                            <option value="Marié(e)" {{ old('chef_etat_civil') == 'Marié(e)' ? 'selected' : '' }}>Marié(e)</option>
                            <option value="Divorcé(e)" {{ old('chef_etat_civil') == 'Divorcé(e)' ? 'selected' : '' }}>Divorcé(e)</option>
                            <option value="Veuf/Veuve" {{ old('chef_etat_civil') == 'Veuf/Veuve' ? 'selected' : '' }}>Veuf/Veuve</option>
                            <option value="Union libre" {{ old('chef_etat_civil') == 'Union libre' ? 'selected' : '' }}>Union libre</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                        <input type="text" name="chef_telephone" value="{{ old('chef_telephone') }}" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" name="chef_email" value="{{ old('chef_email') }}" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Lieu de naissance</label>
                        <input type="text" name="chef_lieu_naissance" value="{{ old('chef_lieu_naissance') }}" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nationalité</label>
                        <input type="text" name="chef_nationalite" value="{{ old('chef_nationalite', 'Congolaise') }}" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type de document</label>
                        <select name="chef_type_document" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Sélectionner</option>
                            <option value="Carte électorale">Carte électorale</option>
                            <option value="Passeport">Passeport</option>
                            <option value="Permis de conduire">Permis de conduire</option>
                            <option value="Attestation">Attestation</option>
                            <option value="Aucun">Aucun</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Numéro du document</label>
                        <input type="text" name="chef_numero_document" value="{{ old('chef_numero_document') }}" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Photo et Empreinte -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Capture Photo -->
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6">
                        <label class="block text-sm font-medium text-gray-700 mb-4">Photo du Chef *</label>
                        <div id="camera-container" class="mb-4">
                            <video id="video" width="100%" height="240" autoplay class="bg-gray-900 rounded mb-2"></video>
                            <canvas id="canvas" class="hidden"></canvas>
                            <div id="photo-preview" class="hidden">
                                <img id="captured-image" src="" alt="Photo capturée" class="w-full rounded mb-2">
                            </div>
                        </div>
                        <input type="hidden" name="chef_photo" id="chef_photo">
                        <div class="flex gap-2">
                            <button type="button" id="start-camera" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Démarrer Caméra
                            </button>
                            <button type="button" id="capture-photo" class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm hidden">
                                Capturer
                            </button>
                            <button type="button" id="retake-photo" class="flex-1 bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md text-sm hidden">
                                Reprendre
                            </button>
                        </div>
                    </div>

                    <!-- Capture Empreintes (3 doigts) -->
                    <div class="border-2 border-dashed border-purple-300 rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <label class="block text-sm font-medium text-gray-700">Empreintes Digitales</label>
                            <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded">optionnel — 3 poses</span>
                        </div>

                        {{-- Alerte doublon empreinte (non-bloquante) --}}
                        <div id="duplicate-alert" class="hidden mb-4 p-3 bg-orange-50 border-2 border-orange-400 rounded-lg">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-start gap-2">
                                    <svg class="w-5 h-5 text-orange-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    <div>
                                        <p class="text-orange-700 font-bold text-sm">⚠ Empreinte déjà enregistrée</p>
                                        <p id="duplicate-detail" class="text-orange-700 text-xs mt-1 font-medium"></p>
                                        <p class="text-orange-600 text-xs mt-1">Vous pouvez continuer l'enregistrement, mais vérifiez l'identité de la personne.</p>
                                    </div>
                                </div>
                                <button type="button" onclick="document.getElementById('duplicate-alert').classList.add('hidden')"
                                    class="text-orange-400 hover:text-orange-600 text-lg font-bold leading-none flex-shrink-0" title="Fermer">✕</button>
                            </div>
                        </div>

                        {{-- 3 poses --}}
                        <div class="space-y-3">

                            {{-- Pose 1 : 4 doigts gauches --}}
                            <div class="bg-purple-50 border border-purple-200 rounded-lg p-3" id="fp-pose-1">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-purple-600 text-white text-xs font-bold">1</span>
                                        <span class="text-sm font-medium text-purple-800">4 doigts gauches (sans pouce)</span>
                                    </div>
                                    <span id="fp-badge-1" class="hidden text-xs px-2 py-0.5 rounded-full font-medium"></span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div id="fp-preview-1" class="flex gap-1">
                                        <div class="w-10 h-12 bg-gray-200 rounded border border-gray-300 flex items-center justify-center"><span class="text-gray-400 text-xs">↑</span></div>
                                        <div class="w-10 h-12 bg-gray-200 rounded border border-gray-300 flex items-center justify-center"><span class="text-gray-400 text-xs">↑</span></div>
                                        <div class="w-10 h-12 bg-gray-200 rounded border border-gray-300 flex items-center justify-center"><span class="text-gray-400 text-xs">↑</span></div>
                                        <div class="w-10 h-12 bg-gray-200 rounded border border-gray-300 flex items-center justify-center"><span class="text-gray-400 text-xs">↑</span></div>
                                    </div>
                                    <div class="flex-1">
                                        <p id="fp-status-1" class="text-xs text-gray-500 mb-1.5">Non capturée</p>
                                        <button type="button" id="fp-btn-1" data-pose="1"
                                            class="fp-pose-btn w-full bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded text-xs font-medium transition-colors">
                                            Capturer pose 1
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="chef_empreinte" id="fp-input-1">
                            </div>

                            {{-- Pose 2 : 2 pouces --}}
                            <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-3" id="fp-pose-2">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-indigo-600 text-white text-xs font-bold">2</span>
                                        <span class="text-sm font-medium text-indigo-800">2 pouces (gauche + droite)</span>
                                    </div>
                                    <span id="fp-badge-2" class="hidden text-xs px-2 py-0.5 rounded-full font-medium"></span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div id="fp-preview-2" class="flex gap-1">
                                        <div class="w-12 h-12 bg-gray-200 rounded border border-gray-300 flex items-center justify-center"><span class="text-gray-400 text-xs">↑</span></div>
                                        <div class="w-12 h-12 bg-gray-200 rounded border border-gray-300 flex items-center justify-center"><span class="text-gray-400 text-xs">↑</span></div>
                                    </div>
                                    <div class="flex-1">
                                        <p id="fp-status-2" class="text-xs text-gray-500 mb-1.5">Non capturée</p>
                                        <button type="button" id="fp-btn-2" data-pose="2"
                                            class="fp-pose-btn w-full bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded text-xs font-medium transition-colors">
                                            Capturer pose 2
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="chef_empreinte_2" id="fp-input-2">
                            </div>

                            {{-- Pose 3 : 4 doigts droits --}}
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3" id="fp-pose-3">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-blue-600 text-white text-xs font-bold">3</span>
                                        <span class="text-sm font-medium text-blue-800">4 doigts droits (sans pouce)</span>
                                    </div>
                                    <span id="fp-badge-3" class="hidden text-xs px-2 py-0.5 rounded-full font-medium"></span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div id="fp-preview-3" class="flex gap-1">
                                        <div class="w-10 h-12 bg-gray-200 rounded border border-gray-300 flex items-center justify-center"><span class="text-gray-400 text-xs">↑</span></div>
                                        <div class="w-10 h-12 bg-gray-200 rounded border border-gray-300 flex items-center justify-center"><span class="text-gray-400 text-xs">↑</span></div>
                                        <div class="w-10 h-12 bg-gray-200 rounded border border-gray-300 flex items-center justify-center"><span class="text-gray-400 text-xs">↑</span></div>
                                        <div class="w-10 h-12 bg-gray-200 rounded border border-gray-300 flex items-center justify-center"><span class="text-gray-400 text-xs">↑</span></div>
                                    </div>
                                    <div class="flex-1">
                                        <p id="fp-status-3" class="text-xs text-gray-500 mb-1.5">Non capturée</p>
                                        <button type="button" id="fp-btn-3" data-pose="3"
                                            class="fp-pose-btn w-full bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-xs font-medium transition-colors">
                                            Capturer pose 3
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="chef_empreinte_3" id="fp-input-3">
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Origine -->
            <div class="mb-8 p-6 bg-yellow-50 rounded-lg">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Origine et Déplacement</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Province d'origine</label>
                        <select name="province_origine_id" id="province_origine_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Sélectionner</option>
                            @foreach($provinces as $province)
                                <option value="{{ $province->id }}">{{ $province->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Territoire d'origine</label>
                        <select name="territoire_origine_id" id="territoire_origine_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Sélectionner d'abord la province</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Commune/Secteur d'origine</label>
                        <input type="text" name="commune_origine" value="{{ old('commune_origine') }}" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Village/Quartier d'origine</label>
                        <input type="text" name="village_origine" value="{{ old('village_origine') }}" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Raison du déplacement</label>
                        <textarea name="raison_deplacement" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('raison_deplacement') }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date d'arrivée sur le site</label>
                        <input type="date" name="date_arrivee_site" value="{{ old('date_arrivee_site') }}" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>

            <!-- Composition du Ménage (Niveau 1) -->
            <div class="mb-8 p-6 bg-green-50 rounded-lg">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Composition du Ménage</h2>
                <p class="text-sm text-gray-600 mb-4">Indiquez le nombre de personnes dans chaque catégorie (y compris le chef de ménage)</p>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hommes (18+) *</label>
                        <input type="number" name="nombre_hommes" value="{{ old('nombre_hommes', 0) }}" min="0" required
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Femmes (18+) *</label>
                        <input type="number" name="nombre_femmes" value="{{ old('nombre_femmes', 0) }}" min="0" required
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Garçons (&lt;18) *</label>
                        <input type="number" name="nombre_garcons" value="{{ old('nombre_garcons', 0) }}" min="0" required
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Filles (&lt;18) *</label>
                        <input type="number" name="nombre_filles" value="{{ old('nombre_filles', 0) }}" min="0" required
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div class="bg-white p-4 rounded border border-gray-200">
                    <p class="text-sm font-medium text-gray-700">Total des personnes: <span id="total-personnes" class="text-2xl font-bold text-green-600">0</span></p>
                </div>
            </div>

            <!-- Vulnérabilités -->
            <div class="mb-8 p-6 bg-red-50 rounded-lg">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Vulnérabilités</h2>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Femmes enceintes</label>
                        <input type="number" name="nombre_femmes_enceintes" value="{{ old('nombre_femmes_enceintes', 0) }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Femmes allaitantes</label>
                        <input type="number" name="nombre_femmes_allaitantes" value="{{ old('nombre_femmes_allaitantes', 0) }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Personnes handicapées</label>
                        <input type="number" name="nombre_personnes_handicapees" value="{{ old('nombre_personnes_handicapees', 0) }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Personnes âgées (60+)</label>
                        <input type="number" name="nombre_personnes_agees" value="{{ old('nombre_personnes_agees', 0) }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Enfants orphelins</label>
                        <input type="number" name="nombre_enfants_orphelins" value="{{ old('nombre_enfants_orphelins', 0) }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Enfants séparés</label>
                        <input type="number" name="nombre_enfants_separes" value="{{ old('nombre_enfants_separes', 0) }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Malades chroniques</label>
                        <input type="number" name="nombre_malades_chroniques" value="{{ old('nombre_malades_chroniques', 0) }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>

            <!-- Conditions de Vie -->
            <div class="mb-8 p-6 bg-purple-50 rounded-lg">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Conditions de Vie</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type d'abri</label>
                        <select name="type_abri" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Sélectionner</option>
                            <option value="Tente">Tente</option>
                            <option value="Bâche">Bâche</option>
                            <option value="Maison en dur">Maison en dur</option>
                            <option value="Abri de fortune">Abri de fortune</option>
                            <option value="Famille d'accueil">Famille d'accueil</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="flex items-center">
                        <input type="checkbox" name="acces_eau_potable" id="acces_eau_potable" value="1" {{ old('acces_eau_potable') ? 'checked' : '' }}
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="acces_eau_potable" class="ml-2 block text-sm text-gray-700">Accès à l'eau potable</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="acces_latrines" id="acces_latrines" value="1" {{ old('acces_latrines') ? 'checked' : '' }}
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="acces_latrines" class="ml-2 block text-sm text-gray-700">Accès aux latrines</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="acces_electricite" id="acces_electricite" value="1" {{ old('acces_electricite') ? 'checked' : '' }}
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="acces_electricite" class="ml-2 block text-sm text-gray-700">Accès à l'électricité</label>
                    </div>
                </div>
            </div>

            <!-- Assistance Reçue -->
            <div class="mb-8 p-6 bg-indigo-50 rounded-lg">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Assistance Reçue</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="flex items-center">
                        <input type="checkbox" name="recu_kits_nfi" id="recu_kits_nfi" value="1" {{ old('recu_kits_nfi') ? 'checked' : '' }}
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="recu_kits_nfi" class="ml-2 block text-sm text-gray-700">Kits NFI reçus</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="recu_assistance_alimentaire" id="recu_assistance_alimentaire" value="1" {{ old('recu_assistance_alimentaire') ? 'checked' : '' }}
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="recu_assistance_alimentaire" class="ml-2 block text-sm text-gray-700">Assistance alimentaire reçue</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="recu_soins_sante" id="recu_soins_sante" value="1" {{ old('recu_soins_sante') ? 'checked' : '' }}
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="recu_soins_sante" class="ml-2 block text-sm text-gray-700">Soins de santé reçus</label>
                    </div>
                </div>
            </div>

            <!-- Observations -->
            <div class="mb-8 p-6 bg-gray-50 rounded-lg">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Observations</h2>
                <textarea name="observations" rows="4" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('observations') }}</textarea>
            </div>

            <!-- Boutons d'action -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('households.index') }}" class="px-6 py-3 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Annuler
                </a>
                <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-md">
                    Enregistrer le Ménage (Niveau 1)
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let stream = null;
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const startCameraBtn = document.getElementById('start-camera');
    const capturePhotoBtn = document.getElementById('capture-photo');
    const retakePhotoBtn = document.getElementById('retake-photo');
    const photoPreview = document.getElementById('photo-preview');
    const capturedImage = document.getElementById('captured-image');
    const photoInput = document.getElementById('chef_photo');

    // Calculer le total des personnes
    const personInputs = document.querySelectorAll('input[name^="nombre_"]');
    const totalElement = document.getElementById('total-personnes');
    
    personInputs.forEach(input => {
        if(['nombre_hommes', 'nombre_femmes', 'nombre_garcons', 'nombre_filles'].includes(input.name)) {
            input.addEventListener('input', updateTotal);
        }
    });

    function updateTotal() {
        const hommes = parseInt(document.querySelector('input[name="nombre_hommes"]').value) || 0;
        const femmes = parseInt(document.querySelector('input[name="nombre_femmes"]').value) || 0;
        const garcons = parseInt(document.querySelector('input[name="nombre_garcons"]').value) || 0;
        const filles = parseInt(document.querySelector('input[name="nombre_filles"]').value) || 0;
        totalElement.textContent = hommes + femmes + garcons + filles;
    }

    // Caméra
    startCameraBtn.addEventListener('click', async function() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: true });
            video.srcObject = stream;
            video.classList.remove('hidden');
            startCameraBtn.classList.add('hidden');
            capturePhotoBtn.classList.remove('hidden');
        } catch(err) {
            let msg = 'Erreur d\'accès à la caméra.';
            if (err.name === 'NotReadableError' || err.message === 'Could not start video source') {
                msg = 'La caméra est déjà utilisée par une autre application (Teams, Zoom, navigateur...).\nFermez l\'application qui utilise la caméra, puis réessayez.';
            } else if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                msg = 'Accès à la caméra refusé.\nAutorisez l\'accès à la caméra dans les paramètres du navigateur.';
            } else if (err.name === 'NotFoundError') {
                msg = 'Aucune caméra détectée sur cet appareil.';
            } else {
                msg = 'Erreur d\'accès à la caméra : ' + err.message;
            }
            alert(msg);
        }
    });

    capturePhotoBtn.addEventListener('click', function() {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        
        const imageData = canvas.toDataURL('image/png');
        capturedImage.src = imageData;
        photoInput.value = imageData;
        
        video.classList.add('hidden');
        photoPreview.classList.remove('hidden');
        capturePhotoBtn.classList.add('hidden');
        retakePhotoBtn.classList.remove('hidden');
        
        // Arrêter la caméra
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }
    });

    retakePhotoBtn.addEventListener('click', function() {
        photoPreview.classList.add('hidden');
        retakePhotoBtn.classList.add('hidden');
        startCameraBtn.classList.remove('hidden');
        photoInput.value = '';
    });

    // ========================================
    // Capture Empreintes — Thales DactyScan84c
    // ========================================
    //
    // Séquence standard NIST en 3 poses :
    //   Pose 1 → ObjectID 22 "Flat Left Four Fingers"  (index, majeur, annulaire, auriculaire gauche)
    //   Pose 2 → ObjectID 21 "Flat Two Thumbs"         (pouce gauche + pouce droit)
    //   Pose 3 → ObjectID 23 "Flat Right Four Fingers" (index, majeur, annulaire, auriculaire droit)
    //
    // Chaque pose a son propre bouton — l'utilisateur peut relancer n'importe quelle pose.
    //
    // FIX "FetchedValue has been disposed" :
    //   Le SDK réinitialise le contexte d'acquisition entre chaque appel.
    //   Il FAUT appeler PUT /devices/{sn} avant chaque GET /fingerprints/{id}.
    // ========================================

    const API_BASE            = 'http://localhost:8090';
    const DUPLICATE_CHECK_URL = '{{ route("households.check-fingerprint-duplicate") }}';
    const CSRF_TOKEN          = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const CAPTURE_FORMATS     = ['JPG', 'FMR_ISO'];

    // Correspondance pose → ObjectID réel du DactyScan84c
    const POSE_OBJECT_ID = { 1: 22, 2: 21, 3: 23 };

    // Couleurs Tailwind des boutons par pose (état capturé / erreur)
    const POSE_COLOR = { 1: 'purple', 2: 'indigo', 3: 'blue' };

    let _fpCapturing = false; // verrou global (une seule capture à la fois)
    let _fpSerial    = null;

    // ── Helpers HTTP ──────────────────────────────────────────────────────────

    async function _thalesFetch(method, path, expectJson) {
        let response;
        try {
            response = await fetch(API_BASE + path, {
                method,
                headers: { 'Accept': 'application/json' }
            });
        } catch (_) {
            throw new Error('Service Thales inaccessible sur ' + API_BASE
                + '. Démarrez webapi-1.2.8.jar sur le port 8090.');
        }
        if (!response.ok) {
            let body = '';
            try { body = await response.text(); } catch (_) {}
            throw new Error('HTTP ' + response.status + ' — ' + (body || response.statusText));
        }
        if (!expectJson) return null;
        try { return await response.json(); }
        catch (_) { throw new Error('Réponse non-JSON du service Thales.'); }
    }

    // Détecte le scanner à chaque activation pour éviter un état SDK périmé
    async function _getSerialNumber() {
        const devices = await _thalesFetch('GET', '/devices', true);
        if (!Array.isArray(devices) || devices.length === 0)
            throw new Error('Aucun scanner détecté. Branchez le scanner DactyScan84c.');
        _fpSerial = devices[0].SerialNumber;
        return _fpSerial;
    }

    // Active le scanner — OBLIGATOIRE avant chaque capture (sinon "FetchedValue has been disposed")
    async function _activateScanner() {
        const sn = await _getSerialNumber();
        await _thalesFetch('PUT', '/devices/' + sn, false);
    }

    // Lance une capture sur l'ObjectID donné, retourne le tableau de doigts capturés
    async function _capture(objectId) {
        const qs = CAPTURE_FORMATS.map(function (f) { return 'outputFormats=' + f; }).join('&');
        let data;
        try {
            data = await _thalesFetch('GET', '/fingerprints/' + objectId + '?' + qs, true);
        } catch (e) {
            // Certains SDK renvoient HTTP 500 au lieu du message FetchedValue dans la payload.
            const msg = (e && e.message ? e.message : '').toLowerCase();
            const isTransientCapture = msg.includes('fetchedvalue')
                || msg.includes('disposed')
                || (msg.includes('http 500') && msg.includes('fingerprints'));
            if (isTransientCapture) {
                throw new Error('FetchedValue has been disposed');
            }
            throw e;
        }
        if (!Array.isArray(data) || data.length === 0)
            throw new Error('Aucune donnée reçue du scanner.');

        // Extraire les données utiles — séparer l'erreur FMR_ISO de la réussite JPG
        const fingers = data.map(function (fp) {
            let imageBase64 = null, templateBase64 = null, sdkError = null;
            (fp.Outputs || []).forEach(function (out) {
                if (out.Format === 'JPG') {
                    if (out.Base64Data) imageBase64 = out.Base64Data;
                    // ignorer ErrorMessage sur JPG (ex: pas d'erreur réelle)
                } else if (out.Format === 'FMR_ISO') {
                    if (out.Base64Data)    templateBase64 = out.Base64Data;
                    else if (out.ErrorMessage && !sdkError) sdkError = out.ErrorMessage;
                } else if (out.ErrorMessage && !sdkError) {
                    sdkError = out.ErrorMessage;
                }
            });
            return { objectName: fp.ObjectName, imageBase64, templateBase64, sdkError };
        });

        // Un doigt est valide s'il a au moins l'image JPG
        const valid = fingers.filter(function (f) { return f.imageBase64 || f.templateBase64; });

        if (valid.length === 0) {
            // Erreurs bloquantes (FetchedValue, scanner déconnecté, etc.)
            const firstError = fingers.map(function (f) { return f.sdkError; }).filter(Boolean)[0];
            if (firstError && (firstError.toLowerCase().includes('fetchedvalue') || firstError.toLowerCase().includes('disposed'))) {
                throw new Error('Scanner en réinitialisation (FetchedValue). Réessayez dans 1 seconde.');
            }
            throw new Error(firstError || 'Aucune empreinte exploitable capturée.');
        }

        return valid;
    }

    function _isFetchedDisposedError(message) {
        const msg = (message || '').toLowerCase();
        return msg.includes('fetchedvalue')
            || msg.includes('fetched value')
            || msg.includes('disposed')
            || (msg.includes('http 500') && msg.includes('fingerprints'));
    }

    // ── Helpers UI ────────────────────────────────────────────────────────────

    function _setStatus(pose, msg, pulse) {
        const el = document.getElementById('fp-status-' + pose);
        el.textContent = msg;
        el.className   = 'text-xs mb-1.5 ' +
            (pulse ? 'text-purple-600 animate-pulse font-medium' : 'text-gray-500');
    }

    function _setBadge(pose, text, colorClass) {
        const b = document.getElementById('fp-badge-' + pose);
        b.textContent = text;
        b.className   = 'text-xs px-2 py-0.5 rounded-full font-medium ' + colorClass;
        b.classList.remove('hidden');
    }

    function _setBtn(pose, label, colorClass, disabled) {
        const btn = document.getElementById('fp-btn-' + pose);
        btn.textContent = label;
        btn.className   = 'fp-pose-btn w-full text-white px-3 py-1.5 rounded text-xs font-medium transition-colors ' + colorClass;
        btn.disabled    = disabled;
    }

    // Affiche les miniatures de doigts dans la zone de prévisualisation
    function _renderPreviews(pose, fingers) {
        const zone = document.getElementById('fp-preview-' + pose);
        const imgs = fingers
            .filter(function (f) { return f.imageBase64; })
            .map(function (f) {
                return '<img src="data:image/jpeg;base64,' + f.imageBase64 + '"'
                    + ' class="w-10 h-12 rounded border border-green-400 object-contain bg-white"'
                    + ' title="' + f.objectName + '">';
            });
        if (imgs.length > 0) {
            zone.innerHTML = '<div class="flex gap-1">' + imgs.join('') + '</div>';
        }
    }

    // ── Détection de doublon ────────────────────────────────────────────────
    // Retourne true si doublon trouvé (pour que capturePose puisse mettre à jour le badge).

    async function _checkDuplicate(templateBase64, imageBase64, pose) {
        // Filtrer les null (doigts sans FMR_ISO — qualité insuffisante ou GBFRSW absent)
        const validTemplates = (Array.isArray(templateBase64) ? templateBase64 : [templateBase64])
            .filter(function (t) { return typeof t === 'string' && t.length > 0; });
        const validImages = (Array.isArray(imageBase64) ? imageBase64 : [imageBase64])
            .filter(function (i) { return typeof i === 'string' && i.length > 0; });

        if (validTemplates.length === 0 && validImages.length === 0) return false;
        try {
            const r = await fetch(DUPLICATE_CHECK_URL, {
                method  : 'POST',
                headers : {
                    'Content-Type' : 'application/json',
                    'Accept'       : 'application/json',
                    'X-CSRF-TOKEN' : CSRF_TOKEN,
                },
                body: JSON.stringify({
                    templates: validTemplates,
                    images: validImages,
                }),
            });
            const resp = await r.json();
            if (resp.duplicate && resp.household) {
                const h = resp.household;
                document.getElementById('duplicate-detail').textContent =
                    'Pose ' + pose + ' — correspond au dossier\u00a0: ' + h.nom_complet
                    + '\u00a0— N°\u00a0' + h.numero_menage + ' (Site\u00a0: ' + h.site + ')';
                const alertEl = document.getElementById('duplicate-alert');
                alertEl.classList.remove('hidden');
                alertEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return true;
            }
        } catch (e) { console.warn('[Duplicate check] Erreur :', e); }
        return false;
    }

    // ── Flux principal ────────────────────────────────────────────────────────

    async function capturePose(pose) {
        if (_fpCapturing) return;
        _fpCapturing = true;

        const objectId = POSE_OBJECT_ID[pose];
        const color    = POSE_COLOR[pose];

        document.getElementById('fp-input-' + pose).value = '';
        _setBtn(pose, 'Capture en cours...', 'bg-gray-400 cursor-not-allowed', true);
        _setStatus(pose, 'Activation du scanner...', false);
        _setBadge(pose, '⏳ en cours', 'bg-blue-100 text-blue-700');

        try {
            _setStatus(pose, 'Posez la main sur le scanner...', true);

            let fingers;
            // Jusqu'à 5 tentatives uniquement pour FetchedValue.
            // La qualité est évaluée une seule fois par clic (pas de relance auto).
            const MAX_FETCH = 5;
            let lastCaptureErr  = null;

            for (let attempt = 1; attempt <= MAX_FETCH; attempt++) {
                try {
                    // Contrat SDK: PUT /devices/{sn} juste avant CHAQUE GET /fingerprints/{id}
                    _setStatus(pose, 'Activation du scanner (tentative ' + attempt + '/' + MAX_FETCH + ')...', false);
                    await _activateScanner();
                    await new Promise(function (r) { setTimeout(r, 1200); });

                    _setStatus(pose, 'Posez la main sur le scanner...', true);
                    fingers = await _capture(objectId);

                    // Si aucun template FMR_ISO capturé, on ne relance pas automatiquement.
                    // L'opérateur recapture manuellement après repositionnement des doigts.
                    const hasTemplate = fingers.some(function (f) { return f.templateBase64; });
                    if (!hasTemplate) {
                        lastCaptureErr = null;
                        break;
                    }

                    lastCaptureErr = null;
                    break;
                } catch (captureErr) {
                    lastCaptureErr = captureErr;
                    const isFetched = _isFetchedDisposedError(captureErr && captureErr.message ? captureErr.message : '');
                    if (isFetched && attempt < MAX_FETCH) {
                        _setStatus(pose, 'Réinitialisation scanner (tentative ' + attempt + '/' + MAX_FETCH + ')...', false);
                        _fpSerial = null;
                        await new Promise(function (r) { setTimeout(r, 1500 + 700 * attempt); });
                        // Probe best-effort: ne bloque pas la tentative suivante si le SDK tarde à répondre.
                        try { await _thalesFetch('GET', '/devices', true); } catch (_) {}
                    } else {
                        if (isFetched) throw new Error('Scanner bloqué (FetchedValue). Redémarrez webapi-1.2.8.jar puis réessayez.');
                        throw captureErr;
                    }
                }
            }
            if (lastCaptureErr) throw lastCaptureErr;

            const valid   = fingers.filter(function (f) { return f.imageBase64 || f.templateBase64; });

            if (valid.length === 0) throw new Error('Aucune empreinte exploitable capturée.');

            // Stockage : JSON avec tous les doigts + templateBase64 du premier (pour le hash de doublon)
            document.getElementById('fp-input-' + pose).value = JSON.stringify({
                pose           : pose,
                objectId       : objectId,
                fingers        : valid,
                templateBase64 : valid[0].templateBase64, // utilisé par le contrôleur pour SHA-256
                capturedAt     : new Date().toISOString(),
            });

            _renderPreviews(pose, valid);

            // Si aucun template FMR_ISO après toutes les tentatives → badge avertissement
            const hasAnyTemplate = valid.some(function (f) { return f.templateBase64; });
            if (!hasAnyTemplate) {
                _setStatus(pose, 'Vérification doublon (fallback JPG)...', false);
                _setBadge(pose, '⏳ vérification JPG', 'bg-yellow-100 text-yellow-700');

                // Fallback doublon exact à partir des JPG capturés
                const isDuplicateFromJpg = await _checkDuplicate(
                    [],
                    valid.map(function (f) { return f.imageBase64; }),
                    pose
                );

                _setStatus(
                    pose,
                    isDuplicateFromJpg
                        ? '⚠ Doublon détecté via JPG (fallback exact)'
                        : '⚠ Qualité insuffisante — doublon JPG non trouvé. Recapturez si possible.',
                    false
                );
                _setBadge(
                    pose,
                    isDuplicateFromJpg ? '⚠ Doublon (JPG)' : '⚠ Qualité faible',
                    isDuplicateFromJpg ? 'bg-orange-100 text-orange-700' : 'bg-yellow-100 text-yellow-800'
                );
                _setBtn(pose, 'Recapturer', 'bg-' + color + '-600 hover:bg-' + color + '-700', false);
                _fpCapturing = false;
                return;
            }

            _setStatus(pose, 'Vérification doublon...', false);
            _setBadge(pose, '⏳ vérification', 'bg-yellow-100 text-yellow-700');

            // Vérification doublon sur TOUS les doigts capturés — immédiate et attendue avant de débloquer le bouton
            const isDuplicate = await _checkDuplicate(
                valid.map(function (f) { return f.templateBase64; }),
                valid.map(function (f) { return f.imageBase64; }),
                pose
            );

            _setStatus(pose, valid.length + ' doigt(s) capturé(s)' + (isDuplicate ? ' — ⚠ DOUBLON' : ''), false);
            _setBadge(
                pose,
                isDuplicate ? '⚠ Doublon' : '✓ Capturée',
                isDuplicate ? 'bg-orange-100 text-orange-700' : 'bg-green-100 text-green-700'
            );
            _setBtn(pose, 'Recapturer', 'bg-' + color + '-600 hover:bg-' + color + '-700', false);

        } catch (err) {
            const isFetchedDisposed = _isFetchedDisposedError(err && err.message ? err.message : '');

            // Réinitialise le cache série si le service est tombé ou si FetchedValue persiste
            if (err.message.includes('inaccessible') || err.message.includes('HTTP')
                || isFetchedDisposed || err.message.includes('bloqué')) {
                _fpSerial = null;
            }

            // Ne pas afficher le message brut SDK "FetchedValue has been disposed" à l'opérateur.
            const uiMsg = isFetchedDisposed
                ? 'Scanner en réinitialisation. Attendez 1 seconde puis réessayez.'
                : err.message;

            _setStatus(pose, uiMsg, false);
            _setBadge(pose, '✗ Erreur', 'bg-red-100 text-red-700');
            _setBtn(pose, 'Réessayer', 'bg-' + color + '-600 hover:bg-' + color + '-700', false);
            document.getElementById('fp-input-' + pose).value = '';

        } finally {
            _fpCapturing = false;
        }
    }

    document.querySelectorAll('.fp-pose-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            capturePose(parseInt(this.dataset.pose));
        });
    });

    // Charger les territoires selon la province
    document.getElementById('province_origine_id').addEventListener('change', function() {
        const provinceId = this.value;
        const territoireSelect = document.getElementById('territoire_origine_id');
        
        if (!provinceId) {
            territoireSelect.innerHTML = '<option value="">Sélectionner d\'abord la province</option>';
            return;
        }
        
        // Charger les territoires via AJAX
        fetch(`/api/territoires?province_id=${provinceId}`)
            .then(response => response.json())
            .then(data => {
                territoireSelect.innerHTML = '<option value="">Sélectionner un territoire</option>';
                data.forEach(territoire => {
                    territoireSelect.innerHTML += `<option value="${territoire.id}">${territoire.name}</option>`;
                });
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
    });

    updateTotal();
});
</script>
@endpush
@endsection
