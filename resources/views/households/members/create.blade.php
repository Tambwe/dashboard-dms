@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-6xl">
    <div class="mb-6">
        <a href="{{ route('households.show', $household) }}" class="text-blue-600 hover:text-blue-800 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour au ménage
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-md p-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Ajouter un Membre au Ménage - Niveau 2</h1>
            <p class="text-gray-600 mt-2">Ménage: {{ $household->chef_nom_complet }} ({{ $household->numero_menage }})</p>
            <p class="text-sm text-gray-500 mt-1">Membres déjà enregistrés: {{ $household->members->count() }} / {{ $household->nombre_total_personnes }}</p>
        </div>

        <form method="POST" action="{{ route('households.members.store', $household) }}" id="memberForm">
            @csrf

            <!-- Informations Personnelles -->
            <div class="mb-8 p-6 bg-blue-50 rounded-lg">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Informations Personnelles</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nom *</label>
                        <input type="text" name="nom" value="{{ old('nom') }}" required 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('nom') border-red-500 @enderror">
                        @error('nom')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Postnom</label>
                        <input type="text" name="postnom" value="{{ old('postnom') }}" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Prénom</label>
                        <input type="text" name="prenom" value="{{ old('prenom') }}" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sexe *</label>
                        <select name="sexe" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('sexe') border-red-500 @enderror">
                            <option value="">Sélectionner</option>
                            <option value="M" {{ old('sexe') == 'M' ? 'selected' : '' }}>Masculin</option>
                            <option value="F" {{ old('sexe') == 'F' ? 'selected' : '' }}>Féminin</option>
                        </select>
                        @error('sexe')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date de naissance</label>
                        <input type="date" name="date_naissance" value="{{ old('date_naissance') }}" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" id="date_naissance">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Âge *</label>
                        <input type="number" name="age" value="{{ old('age') }}" min="0" max="150" required id="age"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('age') border-red-500 @enderror">
                        @error('age')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">État civil</label>
                        <select name="etat_civil" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Sélectionner</option>
                            <option value="Célibataire" {{ old('etat_civil') == 'Célibataire' ? 'selected' : '' }}>Célibataire</option>
                            <option value="Marié(e)" {{ old('etat_civil') == 'Marié(e)' ? 'selected' : '' }}>Marié(e)</option>
                            <option value="Divorcé(e)" {{ old('etat_civil') == 'Divorcé(e)' ? 'selected' : '' }}>Divorcé(e)</option>
                            <option value="Veuf/Veuve" {{ old('etat_civil') == 'Veuf/Veuve' ? 'selected' : '' }}>Veuf/Veuve</option>
                            <option value="Union libre" {{ old('etat_civil') == 'Union libre' ? 'selected' : '' }}>Union libre</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Lieu de naissance</label>
                        <input type="text" name="lieu_naissance" value="{{ old('lieu_naissance') }}" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nationalité</label>
                        <input type="text" name="nationalite" value="{{ old('nationalite', 'Congolaise') }}" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Lien avec le chef *</label>
                        <select name="lien_avec_chef" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('lien_avec_chef') border-red-500 @enderror">
                            <option value="">Sélectionner</option>
                            <option value="Chef de ménage" {{ old('lien_avec_chef') == 'Chef de ménage' ? 'selected' : '' }}>Chef de ménage</option>
                            <option value="Époux/Épouse" {{ old('lien_avec_chef') == 'Époux/Épouse' ? 'selected' : '' }}>Époux/Épouse</option>
                            <option value="Fils/Fille" {{ old('lien_avec_chef') == 'Fils/Fille' ? 'selected' : '' }}>Fils/Fille</option>
                            <option value="Père/Mère" {{ old('lien_avec_chef') == 'Père/Mère' ? 'selected' : '' }}>Père/Mère</option>
                            <option value="Frère/Sœur" {{ old('lien_avec_chef') == 'Frère/Sœur' ? 'selected' : '' }}>Frère/Sœur</option>
                            <option value="Grand-parent" {{ old('lien_avec_chef') == 'Grand-parent' ? 'selected' : '' }}>Grand-parent</option>
                            <option value="Petit-fils/Petite-fille" {{ old('lien_avec_chef') == 'Petit-fils/Petite-fille' ? 'selected' : '' }}>Petit-fils/Petite-fille</option>
                            <option value="Oncle/Tante" {{ old('lien_avec_chef') == 'Oncle/Tante' ? 'selected' : '' }}>Oncle/Tante</option>
                            <option value="Neveu/Nièce" {{ old('lien_avec_chef') == 'Neveu/Nièce' ? 'selected' : '' }}>Neveu/Nièce</option>
                            <option value="Cousin(e)" {{ old('lien_avec_chef') == 'Cousin(e)' ? 'selected' : '' }}>Cousin(e)</option>
                            <option value="Autre parent" {{ old('lien_avec_chef') == 'Autre parent' ? 'selected' : '' }}>Autre parent</option>
                            <option value="Sans lien" {{ old('lien_avec_chef') == 'Sans lien' ? 'selected' : '' }}>Sans lien</option>
                        </select>
                        @error('lien_avec_chef')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Documents d'identité -->
            <div class="mb-8 p-6 bg-gray-50 rounded-lg">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Documents d'Identité</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type de document</label>
                        <select name="type_document" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Sélectionner</option>
                            <option value="Carte électorale" {{ old('type_document') == 'Carte électorale' ? 'selected' : '' }}>Carte électorale</option>
                            <option value="Passeport" {{ old('type_document') == 'Passeport' ? 'selected' : '' }}>Passeport</option>
                            <option value="Permis de conduire" {{ old('type_document') == 'Permis de conduire' ? 'selected' : '' }}>Permis de conduire</option>
                            <option value="Attestation" {{ old('type_document') == 'Attestation' ? 'selected' : '' }}>Attestation</option>
                            <option value="Acte de naissance" {{ old('type_document') == 'Acte de naissance' ? 'selected' : '' }}>Acte de naissance</option>
                            <option value="Aucun" {{ old('type_document') == 'Aucun' ? 'selected' : '' }}>Aucun</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Numéro du document</label>
                        <input type="text" name="numero_document" value="{{ old('numero_document') }}" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>

            <!-- Photo et Empreinte -->
            <div class="mb-8 p-6 bg-purple-50 rounded-lg">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Biométrie</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Capture Photo -->
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6">
                        <label class="block text-sm font-medium text-gray-700 mb-4">Photo du Membre</label>
                        <div id="camera-container" class="mb-4">
                            <video id="video" width="100%" height="240" autoplay class="bg-gray-900 rounded mb-2"></video>
                            <canvas id="canvas" class="hidden"></canvas>
                            <div id="photo-preview" class="hidden">
                                <img id="captured-image" src="" alt="Photo capturée" class="w-full rounded mb-2">
                            </div>
                        </div>
                        <input type="hidden" name="photo" id="photo">
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

                    <!-- Capture Empreinte -->
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6">
                        <label class="block text-sm font-medium text-gray-700 mb-4">Empreinte Digitale</label>
                        <div id="fingerprint-container" class="mb-4 h-64 bg-gray-100 rounded flex items-center justify-center">
                            <div id="fingerprint-status" class="text-center">
                                <svg class="w-24 h-24 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
                                </svg>
                                <p class="text-gray-600">En attente de capture</p>
                            </div>
                        </div>
                        <input type="hidden" name="empreinte" id="empreinte">
                        <button type="button" id="capture-fingerprint" class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
                            </svg>
                            Capturer Empreinte
                        </button>
                    </div>
                </div>
            </div>

            <!-- Contact -->
            <div class="mb-8 p-6 bg-indigo-50 rounded-lg">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Contact</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                        <input type="text" name="telephone" value="{{ old('telephone') }}" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>

            <!-- Éducation et Profession -->
            <div class="mb-8 p-6 bg-green-50 rounded-lg">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Éducation et Profession</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Niveau d'éducation</label>
                        <select name="niveau_education" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Sélectionner</option>
                            <option value="Aucun" {{ old('niveau_education') == 'Aucun' ? 'selected' : '' }}>Aucun</option>
                            <option value="Primaire incomplet" {{ old('niveau_education') == 'Primaire incomplet' ? 'selected' : '' }}>Primaire incomplet</option>
                            <option value="Primaire complet" {{ old('niveau_education') == 'Primaire complet' ? 'selected' : '' }}>Primaire complet</option>
                            <option value="Secondaire incomplet" {{ old('niveau_education') == 'Secondaire incomplet' ? 'selected' : '' }}>Secondaire incomplet</option>
                            <option value="Secondaire complet" {{ old('niveau_education') == 'Secondaire complet' ? 'selected' : '' }}>Secondaire complet</option>
                            <option value="Universitaire" {{ old('niveau_education') == 'Universitaire' ? 'selected' : '' }}>Universitaire</option>
                            <option value="Professionnel" {{ old('niveau_education') == 'Professionnel' ? 'selected' : '' }}>Professionnel</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Profession</label>
                        <input type="text" name="profession" value="{{ old('profession') }}" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Ex: Agriculteur, Commerçant, Enseignant...">
                    </div>

                    <div class="flex items-center pt-6">
                        <input type="checkbox" name="scolarise_actuellement" id="scolarise_actuellement" value="1" {{ old('scolarise_actuellement') ? 'checked' : '' }}
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="scolarise_actuellement" class="ml-2 block text-sm text-gray-700">Scolarisé(e) actuellement</label>
                    </div>
                </div>
            </div>

            <!-- Vulnérabilités et Santé -->
            <div class="mb-8 p-6 bg-red-50 rounded-lg">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Vulnérabilités et Santé</h2>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="flex items-center">
                        <input type="checkbox" name="handicap" id="handicap" value="1" {{ old('handicap') ? 'checked' : '' }}
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="handicap" class="ml-2 block text-sm text-gray-700">Handicap</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="maladie_chronique" id="maladie_chronique" value="1" {{ old('maladie_chronique') ? 'checked' : '' }}
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="maladie_chronique" class="ml-2 block text-sm text-gray-700">Maladie chronique</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="femme_enceinte" id="femme_enceinte" value="1" {{ old('femme_enceinte') ? 'checked' : '' }}
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="femme_enceinte" class="ml-2 block text-sm text-gray-700">Femme enceinte</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="femme_allaitante" id="femme_allaitante" value="1" {{ old('femme_allaitante') ? 'checked' : '' }}
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="femme_allaitante" class="ml-2 block text-sm text-gray-700">Femme allaitante</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="enfant_orphelin" id="enfant_orphelin" value="1" {{ old('enfant_orphelin') ? 'checked' : '' }}
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="enfant_orphelin" class="ml-2 block text-sm text-gray-700">Enfant orphelin</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="enfant_separe" id="enfant_separe" value="1" {{ old('enfant_separe') ? 'checked' : '' }}
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="enfant_separe" class="ml-2 block text-sm text-gray-700">Enfant séparé</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="personne_agee" id="personne_agee" value="1" {{ old('personne_agee') ? 'checked' : '' }}
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="personne_agee" class="ml-2 block text-sm text-gray-700">Personne âgée (60+)</label>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div id="handicap-detail" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type de handicap</label>
                        <input type="text" name="type_handicap" value="{{ old('type_handicap') }}" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Ex: Moteur, Visuel, Auditif...">
                    </div>

                    <div id="maladie-detail" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type de maladie chronique</label>
                        <input type="text" name="type_maladie" value="{{ old('type_maladie') }}" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Ex: Diabète, Hypertension, VIH...">
                    </div>
                </div>
            </div>

            <!-- Observations -->
            <div class="mb-8 p-6 bg-gray-50 rounded-lg">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Observations</h2>
                <textarea name="observations" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Informations complémentaires sur ce membre...">{{ old('observations') }}</textarea>
            </div>

            <!-- Boutons d'action -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('households.show', $household) }}" class="px-6 py-3 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Annuler
                </a>
                <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-md">
                    Enregistrer le Membre
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
    const photoInput = document.getElementById('photo');

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
    // Capture Empreinte Digitale - Thales Multifinger Scanner
    // ========================================
    
    let thalesWebSocket = null;
    const THALES_WS_URL = 'ws://localhost:8181'; // Port par défaut Thales WebSocket API
    
    // Fonction pour initialiser la connexion Thales
    function initThalesConnection() {
        return new Promise((resolve, reject) => {
            try {
                thalesWebSocket = new WebSocket(THALES_WS_URL);
                
                thalesWebSocket.onopen = function() {
                    console.log('✓ Connexion Thales établie');
                    resolve(true);
                };
                
                thalesWebSocket.onerror = function(error) {
                    console.error('✗ Erreur connexion Thales:', error);
                    reject(error);
                };
                
                thalesWebSocket.onclose = function() {
                    console.log('Connexion Thales fermée');
                };
                
                // Timeout de 3 secondes
                setTimeout(() => {
                    if (thalesWebSocket.readyState !== WebSocket.OPEN) {
                        reject(new Error('Timeout connexion Thales'));
                    }
                }, 3000);
            } catch(error) {
                reject(error);
            }
        });
    }
    
    // Fonction pour capturer l'empreinte
    function captureThalesFingerprint() {
        return new Promise((resolve, reject) => {
            if (!thalesWebSocket || thalesWebSocket.readyState !== WebSocket.OPEN) {
                reject(new Error('Connexion Thales non établie'));
                return;
            }
            
            // Commande pour capturer l'empreinte (format JSON pour Thales)
            const captureCommand = {
                action: "capture",
                timeout: 10000,
                quality: 60, // Qualité minimale (1-100)
                fingerCount: 1 // Nombre de doigts à capturer
            };
            
            // Gestionnaire de réponse
            thalesWebSocket.onmessage = function(event) {
                try {
                    const response = JSON.parse(event.data);
                    
                    if (response.status === 'success' && response.data) {
                        // Empreinte capturée avec succès
                        resolve({
                            success: true,
                            template: response.data.template, // Template d'empreinte (base64)
                            image: response.data.image, // Image de l'empreinte (base64)
                            quality: response.data.quality
                        });
                    } else if (response.status === 'error') {
                        reject(new Error(response.message || 'Erreur de capture'));
                    }
                } catch(e) {
                    reject(new Error('Erreur parsing réponse: ' + e.message));
                }
            };
            
            // Envoyer la commande
            thalesWebSocket.send(JSON.stringify(captureCommand));
            
            // Timeout de 15 secondes pour la capture
            setTimeout(() => {
                reject(new Error('Timeout capture empreinte'));
            }, 15000);
        });
    }
    
    // Bouton de capture d'empreinte
    document.getElementById('capture-fingerprint').addEventListener('click', async function() {
        const fingerprintStatus = document.getElementById('fingerprint-status');
        const fingerprintInput = document.getElementById('empreinte');
        
        // Afficher le statut "En cours..."
        fingerprintStatus.innerHTML = `
            <div class="animate-pulse">
                <svg class="w-24 h-24 mx-auto text-blue-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
                </svg>
                <p class="text-blue-600 font-semibold">Connexion au scanner Thales...</p>
                <p class="text-sm text-gray-600 mt-2">Veuillez patienter</p>
            </div>
        `;
        
        try {
            // Étape 1: Initialiser la connexion si nécessaire
            if (!thalesWebSocket || thalesWebSocket.readyState !== WebSocket.OPEN) {
                fingerprintStatus.innerHTML = `
                    <div class="animate-pulse">
                        <svg class="w-24 h-24 mx-auto text-blue-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
                        </svg>
                        <p class="text-blue-600 font-semibold">Initialisation du scanner...</p>
                    </div>
                `;
                await initThalesConnection();
            }
            
            // Étape 2: Demander la capture
            fingerprintStatus.innerHTML = `
                <div class="animate-pulse">
                    <svg class="w-24 h-24 mx-auto text-purple-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
                    </svg>
                    <p class="text-purple-600 font-semibold text-lg">Veuillez placer votre doigt</p>
                    <p class="text-sm text-gray-600 mt-2">Sur le capteur Thales</p>
                </div>
            `;
            
            // Étape 3: Capturer l'empreinte
            const result = await captureThalesFingerprint();
            
            // Étape 4: Succès - Sauvegarder les données
            fingerprintInput.value = JSON.stringify({
                template: result.template,
                image: result.image,
                quality: result.quality,
                capturedAt: new Date().toISOString(),
                device: 'Thales Multifinger Scanner'
            });
            
            // Afficher le succès avec l'image si disponible
            let successHTML = `
                <div>
                    <svg class="w-24 h-24 mx-auto text-green-600 mb-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 0C4.477 0 0 4.477 0 10s4.477 10 10 10 10-4.477 10-10S15.523 0 10 0zm5.707 7.707l-7 7a1 1 0 01-1.414 0l-3-3a1 1 0 011.414-1.414L8 12.586l6.293-6.293a1 1 0 011.414 1.414z"/>
                    </svg>
                    <p class="text-green-600 font-semibold text-lg">✓ Empreinte capturée avec succès</p>
                    <p class="text-sm text-gray-600 mt-2">Qualité: ${result.quality}%</p>
            `;
            
            // Afficher l'image de l'empreinte si disponible
            if (result.image) {
                successHTML += `
                    <div class="mt-4">
                        <img src="data:image/png;base64,${result.image}" 
                            alt="Empreinte" 
                            class="w-32 h-32 mx-auto border-2 border-green-500 rounded">
                    </div>
                `;
            }
            
            successHTML += '</div>';
            fingerprintStatus.innerHTML = successHTML;
            
        } catch(error) {
            console.error('Erreur capture empreinte:', error);
            
            // Afficher l'erreur
            fingerprintStatus.innerHTML = `
                <div>
                    <svg class="w-24 h-24 mx-auto text-red-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-red-600 font-semibold">✗ Erreur de capture</p>
                    <p class="text-sm text-gray-600 mt-2">${error.message}</p>
                    <div class="mt-4 p-3 bg-yellow-50 rounded text-left text-xs">
                        <p class="font-semibold text-yellow-800 mb-2">Vérifications:</p>
                        <ul class="list-disc list-inside text-yellow-700 space-y-1">
                            <li>Le scanner Thales est-il branché?</li>
                            <li>L'application Thales est-elle lancée?</li>
                            <li>Le service WebSocket écoute sur le port 8181?</li>
                            <li>Avez-vous les droits d'accès?</li>
                        </ul>
                        <p class="mt-2 text-yellow-800">
                            <strong>Alternative:</strong> Vous pouvez continuer sans empreinte et l'ajouter plus tard.
                        </p>
                    </div>
                </div>
            `;
        }
    });

    // Calcul automatique de l'âge
    document.getElementById('date_naissance').addEventListener('change', function() {
        const birthDate = new Date(this.value);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        
        if (age >= 0 && age <= 150) {
            document.getElementById('age').value = age;
        }
    });

    // Afficher/masquer les détails de vulnérabilités
    document.getElementById('handicap').addEventListener('change', function() {
        document.getElementById('handicap-detail').classList.toggle('hidden', !this.checked);
    });

    document.getElementById('maladie_chronique').addEventListener('change', function() {
        document.getElementById('maladie-detail').classList.toggle('hidden', !this.checked);
    });

    // Initialiser l'état des détails si pré-rempli
    if (document.getElementById('handicap').checked) {
        document.getElementById('handicap-detail').classList.remove('hidden');
    }
    if (document.getElementById('maladie_chronique').checked) {
        document.getElementById('maladie-detail').classList.remove('hidden');
    }
});
</script>
@endpush
@endsection
