{{-- Partial: formulaire ménage (create & edit) --}}
{{-- Variables attendues : $sites, $provinces, $household (optionnel pour edit) --}}

<!-- Site -->
<div class="mb-8 p-6 bg-gray-50 rounded-lg">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Localisation</h2>
    <div class="grid grid-cols-1 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Site *</label>
            <select name="site_id" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('site_id') border-red-500 @enderror">
                <option value="">Sélectionnez un site</option>
                @foreach($sites as $site)
                    <option value="{{ $site->id }}" {{ old('site_id', $household->site_id ?? '') == $site->id ? 'selected' : '' }}>
                        {{ $site->nom }}
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
            <input type="text" name="chef_nom" value="{{ old('chef_nom', $household->chef_nom ?? '') }}" required
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('chef_nom') border-red-500 @enderror">
            @error('chef_nom')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Postnom</label>
            <input type="text" name="chef_postnom" value="{{ old('chef_postnom', $household->chef_postnom ?? '') }}"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Prénom</label>
            <input type="text" name="chef_prenom" value="{{ old('chef_prenom', $household->chef_prenom ?? '') }}"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Sexe *</label>
            <select name="chef_sexe" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">Sélectionner</option>
                <option value="M" {{ old('chef_sexe', $household->chef_sexe ?? '') == 'M' ? 'selected' : '' }}>Masculin</option>
                <option value="F" {{ old('chef_sexe', $household->chef_sexe ?? '') == 'F' ? 'selected' : '' }}>Féminin</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Date de naissance</label>
            <input type="date" name="chef_date_naissance"
                value="{{ old('chef_date_naissance', isset($household->chef_date_naissance) ? $household->chef_date_naissance->format('Y-m-d') : '') }}"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Âge</label>
            <input type="number" name="chef_age" value="{{ old('chef_age', $household->chef_age ?? '') }}" min="0" max="150"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">État civil</label>
            <select name="chef_etat_civil" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">Sélectionner</option>
                @foreach(['Célibataire','Marié(e)','Divorcé(e)','Veuf/Veuve','Union libre'] as $etat)
                    <option value="{{ $etat }}" {{ old('chef_etat_civil', $household->chef_etat_civil ?? '') == $etat ? 'selected' : '' }}>{{ $etat }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
            <input type="text" name="chef_telephone" value="{{ old('chef_telephone', $household->chef_telephone ?? '') }}"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
            <input type="email" name="chef_email" value="{{ old('chef_email', $household->chef_email ?? '') }}"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Lieu de naissance</label>
            <input type="text" name="chef_lieu_naissance" value="{{ old('chef_lieu_naissance', $household->chef_lieu_naissance ?? '') }}"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nationalité</label>
            <input type="text" name="chef_nationalite" value="{{ old('chef_nationalite', $household->chef_nationalite ?? 'Congolaise') }}"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Type de document</label>
            <select name="chef_type_document" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">Sélectionner</option>
                @foreach(['Carte électorale','Passeport','Permis de conduire','Attestation','Aucun'] as $doc)
                    <option value="{{ $doc }}" {{ old('chef_type_document', $household->chef_type_document ?? '') == $doc ? 'selected' : '' }}>{{ $doc }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Numéro du document</label>
            <input type="text" name="chef_numero_document" value="{{ old('chef_numero_document', $household->chef_numero_document ?? '') }}"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
    </div>

    <!-- Photo et Empreinte -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Capture Photo -->
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6">
            <label class="block text-sm font-medium text-gray-700 mb-4">Photo du Chef</label>
            @if(!empty($household->chef_photo))
                <div class="mb-4">
                    <img src="{{ $household->chef_photo }}" alt="Photo actuelle" class="w-full rounded mb-2">
                    <p class="text-sm text-gray-500">Photo actuelle (nouvelle capture pour remplacer)</p>
                </div>
            @endif
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
                    @if(!empty($household->chef_empreinte))
                        <p class="text-green-600 font-semibold">✓ Empreinte enregistrée</p>
                        <p class="text-sm text-gray-500 mt-2">Recapturer pour remplacer</p>
                    @else
                        <p class="text-gray-600">En attente de capture</p>
                    @endif
                </div>
            </div>
            <input type="hidden" name="chef_empreinte" id="chef_empreinte">
            <button type="button" id="capture-fingerprint" class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm">
                Capturer Empreinte
            </button>
        </div>
    </div>
</div>

<!-- Origine et Déplacement -->
<div class="mb-8 p-6 bg-yellow-50 rounded-lg">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Origine et Déplacement</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Province d'origine</label>
            <select name="province_origine_id" id="province_origine_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">Sélectionner</option>
                @foreach($provinces as $province)
                    <option value="{{ $province->id }}" {{ old('province_origine_id', $household->province_origine_id ?? '') == $province->id ? 'selected' : '' }}>
                        {{ $province->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Territoire d'origine</label>
            <select name="territoire_origine_id" id="territoire_origine_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">Sélectionner d'abord la province</option>
                @if(!empty($territoires))
                    @foreach($territoires as $territoire)
                        <option value="{{ $territoire->id }}"
                            data-province="{{ $territoire->province_id }}"
                            {{ old('territoire_origine_id', $household->territoire_origine_id ?? '') == $territoire->id ? 'selected' : '' }}>
                            {{ $territoire->name }}
                        </option>
                    @endforeach
                @endif
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Commune/Secteur d'origine</label>
            <input type="text" name="commune_origine" value="{{ old('commune_origine', $household->commune_origine ?? '') }}"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Village/Quartier d'origine</label>
            <input type="text" name="village_origine" value="{{ old('village_origine', $household->village_origine ?? '') }}"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Raison du déplacement</label>
            <textarea name="raison_deplacement" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('raison_deplacement', $household->raison_deplacement ?? '') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Date d'arrivée sur le site</label>
            <input type="date" name="date_arrivee_site"
                value="{{ old('date_arrivee_site', isset($household->date_arrivee_site) ? $household->date_arrivee_site->format('Y-m-d') : '') }}"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
    </div>
</div>

<!-- Composition du Ménage -->
<div class="mb-8 p-6 bg-green-50 rounded-lg">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Composition du Ménage</h2>
    <p class="text-sm text-gray-600 mb-4">Indiquez le nombre de personnes dans chaque catégorie (y compris le chef de ménage)</p>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Hommes (18+) *</label>
            <input type="number" name="nombre_hommes" value="{{ old('nombre_hommes', $household->nombre_hommes ?? 0) }}" min="0" required
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Femmes (18+) *</label>
            <input type="number" name="nombre_femmes" value="{{ old('nombre_femmes', $household->nombre_femmes ?? 0) }}" min="0" required
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Garçons (&lt;18) *</label>
            <input type="number" name="nombre_garcons" value="{{ old('nombre_garcons', $household->nombre_garcons ?? 0) }}" min="0" required
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Filles (&lt;18) *</label>
            <input type="number" name="nombre_filles" value="{{ old('nombre_filles', $household->nombre_filles ?? 0) }}" min="0" required
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
    </div>

    <div class="bg-white p-4 rounded border border-gray-200">
        <p class="text-sm font-medium text-gray-700">Total des personnes: <span id="total-personnes" class="text-2xl font-bold text-green-600">{{ ($household->nombre_total_personnes ?? 0) }}</span></p>
    </div>
</div>

<!-- Vulnérabilités -->
<div class="mb-8 p-6 bg-red-50 rounded-lg">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Vulnérabilités</h2>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        @foreach([
            ['nombre_femmes_enceintes', 'Femmes enceintes'],
            ['nombre_femmes_allaitantes', 'Femmes allaitantes'],
            ['nombre_personnes_handicapees', 'Personnes handicapées'],
            ['nombre_personnes_agees', 'Personnes âgées (60+)'],
            ['nombre_enfants_orphelins', 'Enfants orphelins'],
            ['nombre_enfants_separes', 'Enfants séparés'],
            ['nombre_malades_chroniques', 'Malades chroniques'],
        ] as [$field, $label])
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $label }}</label>
            <input type="number" name="{{ $field }}" value="{{ old($field, $household->{$field} ?? 0) }}" min="0"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
        @endforeach
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
                @foreach(['Tente','Bâche','Maison en dur','Abri de fortune','Famille d\'accueil','Autre'] as $abri)
                    <option value="{{ $abri }}" {{ old('type_abri', $household->type_abri ?? '') == $abri ? 'selected' : '' }}>{{ $abri }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach([
            ['acces_eau_potable', 'acces_eau_potable', "Accès à l'eau potable"],
            ['acces_latrines', 'acces_latrines', 'Accès aux latrines'],
            ['acces_electricite', 'acces_electricite', "Accès à l'électricité"],
        ] as [$name, $id, $label])
        <div class="flex items-center">
            <input type="checkbox" name="{{ $name }}" id="{{ $id }}" value="1"
                {{ old($name, $household->{$name} ?? false) ? 'checked' : '' }}
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
            <label for="{{ $id }}" class="ml-2 block text-sm text-gray-700">{{ $label }}</label>
        </div>
        @endforeach
    </div>
</div>

<!-- Assistance Reçue -->
<div class="mb-8 p-6 bg-indigo-50 rounded-lg">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Assistance Reçue</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach([
            ['recu_kits_nfi', 'recu_kits_nfi', 'Kits NFI reçus'],
            ['recu_assistance_alimentaire', 'recu_assistance_alimentaire', 'Assistance alimentaire reçue'],
            ['recu_soins_sante', 'recu_soins_sante', 'Soins de santé reçus'],
        ] as [$name, $id, $label])
        <div class="flex items-center">
            <input type="checkbox" name="{{ $name }}" id="{{ $id }}" value="1"
                {{ old($name, $household->{$name} ?? false) ? 'checked' : '' }}
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
            <label for="{{ $id }}" class="ml-2 block text-sm text-gray-700">{{ $label }}</label>
        </div>
        @endforeach
    </div>
</div>

<!-- Observations -->
<div class="mb-8 p-6 bg-gray-50 rounded-lg">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Observations</h2>
    <textarea name="observations" rows="4" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('observations', $household->observations ?? '') }}</textarea>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Total personnes
    function updateTotal() {
        const h = parseInt(document.querySelector('input[name="nombre_hommes"]').value) || 0;
        const f = parseInt(document.querySelector('input[name="nombre_femmes"]').value) || 0;
        const g = parseInt(document.querySelector('input[name="nombre_garcons"]').value) || 0;
        const fi = parseInt(document.querySelector('input[name="nombre_filles"]').value) || 0;
        document.getElementById('total-personnes').textContent = h + f + g + fi;
    }
    ['nombre_hommes','nombre_femmes','nombre_garcons','nombre_filles'].forEach(n => {
        const el = document.querySelector('input[name="' + n + '"]');
        if (el) el.addEventListener('input', updateTotal);
    });

    // Filtrer les territoires selon la province sélectionnée
    const provinceSelect = document.getElementById('province_origine_id');
    const territoireSelect = document.getElementById('territoire_origine_id');
    if (provinceSelect && territoireSelect) {
        const allOptions = Array.from(territoireSelect.querySelectorAll('option[data-province]'));
        provinceSelect.addEventListener('change', function() {
            const selectedProvince = this.value;
            allOptions.forEach(opt => {
                opt.hidden = selectedProvince && opt.dataset.province != selectedProvince;
            });
        });
        // Déclencher pour afficher les bons territoires à l'ouverture
        provinceSelect.dispatchEvent(new Event('change'));
    }

    // Caméra
    let stream = null;
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const startCameraBtn = document.getElementById('start-camera');
    const capturePhotoBtn = document.getElementById('capture-photo');
    const retakePhotoBtn = document.getElementById('retake-photo');
    const photoPreview = document.getElementById('photo-preview');
    const capturedImage = document.getElementById('captured-image');
    const photoInput = document.getElementById('chef_photo');

    if (startCameraBtn) {
        startCameraBtn.addEventListener('click', async function() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: true });
                video.srcObject = stream;
                video.classList.remove('hidden');
                startCameraBtn.classList.add('hidden');
                capturePhotoBtn.classList.remove('hidden');
            } catch(err) {
                alert('Erreur d\'accès à la caméra: ' + err.message);
            }
        });
    }

    if (capturePhotoBtn) {
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
            if (stream) stream.getTracks().forEach(t => t.stop());
        });
    }

    if (retakePhotoBtn) {
        retakePhotoBtn.addEventListener('click', function() {
            photoPreview.classList.add('hidden');
            retakePhotoBtn.classList.add('hidden');
            startCameraBtn.classList.remove('hidden');
            photoInput.value = '';
        });
    }
});
</script>
@endpush
