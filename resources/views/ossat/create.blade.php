@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Nouveau Rapport OSSAT</h1>
            <p class="text-gray-500 mt-1">Outil de Suivi des Sites d'Accueil Temporaire – RDC</p>
        </div>
        <a href="{{ route('ossat.index') }}" class="text-blue-600 hover:text-blue-800 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour à la liste
        </a>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded mb-6">
        <ul class="list-disc list-inside text-sm">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('ossat.store') }}" id="ossatForm" novalidate>
        @csrf

        {{-- Navigation par onglets --}}
        <div class="mb-4 border-b border-gray-200 overflow-x-auto">
            <nav class="flex gap-1 min-w-max" id="tabNav">
                @php
                $tabs = [
                    's1' => '1. Collecte',
                    's2' => '2. Localisation',
                    's3' => '3. Géo / Statut',
                    's4' => '4. Gestion',
                    's5' => '5. Admin & Coord',
                    's6' => '6. Organisation',
                    's7' => '7. Population',
                    's8' => '8. Abris & AME',
                    's9' => '9. WASH',
                    's10' => '10. Santé & Alim',
                    's11' => '11. Protection',
                    's12' => '12. Educ & Subs',
                    's13' => '13. Priorités',
                    's14' => '14. Partenaires',
                ];
                @endphp
                @foreach($tabs as $id => $label)
                <button type="button"
                    class="tab-btn px-3 py-2 text-sm font-medium border-b-2 border-transparent text-gray-600 hover:text-blue-600 hover:border-blue-300 whitespace-nowrap"
                    data-tab="{{ $id }}">
                    {{ $label }}
                </button>
                @endforeach
            </nav>
        </div>

        {{-- ═══════════════ S1 : COLLECTE ═══════════════ --}}
        <div class="tab-section hidden" id="tab-s1">
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">1. Données de collecte</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'agent recenseur *</label>
                        <input type="text" name="enumerator_name" value="{{ old('enumerator_name') }}" required
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                        <input type="text" name="phonenumber" value="{{ old('phonenumber') }}"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fait partie d'une agence ?</label>
                        <select name="fait_partie_agence" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">--</option>
                            <option value="1" {{ old('fait_partie_agence') ? 'selected' : '' }}>Oui</option>
                            <option value="0">Non</option>
                        </select>
                    </div>
                    <div id="cond_agence_enqueteur">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Organisation / Agence</label>
                        <input type="text" name="agence_enqueteur" value="{{ old('agence_enqueteur') }}"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date de collecte</label>
                        <input type="date" name="today" value="{{ old('today', date('Y-m-d')) }}"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Données pour un nouveau site ?</label>
                        <select name="nouveau_site" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">--</option>
                            <option value="1">Oui – nouveau site</option>
                            <option value="0">Non – site existant</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════ S2 : LOCALISATION ═══════════════ --}}
        <div class="tab-section hidden" id="tab-s2">
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">2. Localisation du site</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Province *</label>
                        <select name="province_id" required id="province_id"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Sélectionner</option>
                            @foreach($provinces as $p)
                                <option value="{{ $p->id }}" {{
                                    old('province_id', isset($preselectedSite) ? $preselectedSite->commune?->territoire?->province_id : '') == $p->id ? 'selected' : ''
                                }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Territoire *</label>
                        <select name="territoire_id" required id="territoire_id"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Sélectionner d'abord la province</option>
                            @foreach($territoires as $t)
                                <option value="{{ $t->id }}" data-province="{{ $t->province_id }}"
                                    {{
                                        old('territoire_id', isset($preselectedSite) ? $preselectedSite->commune?->territoire_id : '') == $t->id ? 'selected' : ''
                                    }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Site *</label>
                        <select name="site_id" id="site_id"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Sélectionner d'abord le territoire</option>
                        </select>
                        <input type="hidden" name="site_nom" id="site_nom_hidden">
                        <input type="hidden" name="site_code" id="site_code_hidden">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type de site *</label>
                        <select name="type_installation" required
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Sélectionner</option>
                            @foreach($choices['type_installation'] as $opt)
                                <option value="{{ $opt }}" {{ old('type_installation') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type de propriété foncière</label>
                        <select name="propriete_fonciere"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Sélectionner</option>
                            @foreach($choices['propriete'] as $opt)
                                <option value="{{ $opt }}" {{ old('propriete_fonciere') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Statut du site *</label>
                        <select name="statut" required
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Sélectionner</option>
                            @foreach($choices['statut_site'] as $opt)
                                <option value="{{ $opt }}" {{ old('statut') == $opt ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $opt)) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════ S3 : GEO / DATE ═══════════════ --}}
        <div class="tab-section hidden" id="tab-s3">
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">3. Géolocalisation</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                        <input type="number" step="0.0000001" name="gps_latitude" value="{{ old('gps_latitude') }}"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                        <input type="number" step="0.0000001" name="gps_longitude" value="{{ old('gps_longitude') }}"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Altitude (m)</label>
                        <input type="number" step="0.01" name="gps_altitude" value="{{ old('gps_altitude') }}"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mois et année de mise à jour</label>
                        <input type="date" name="date_mise_a_jour" value="{{ old('date_mise_a_jour') }}"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                <div class="mt-4">
                    <button type="button" id="geolocate-btn"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm">
                        Obtenir ma position GPS
                    </button>
                </div>
            </div>
        </div>

        {{-- ═══════════════ S4 : GESTION DE SITE ═══════════════ --}}
        <div class="tab-section hidden" id="tab-s4">
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">5. Gestion de site</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-ossat-yesno name="agence_gestion" label="5.2. Existe-t-il une organisation de gestion du site ?" />
                    <div id="cond_agence_gestion_nom">
                        <label class="block text-sm font-medium text-gray-700 mb-1">5.3. Nom de l'organisation</label>
                        <input type="text" name="agence_gestion_nom" value="{{ old('agence_gestion_nom') }}"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <x-ossat-yesno name="gestionnaire_dedie" label="5.4. Le site a-t-il un gestionnaire dédié ?" />
                    <div id="cond_gestionnaire_details" class="col-span-full grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">5.5. Nom complet du gestionnaire</label>
                            <input type="text" name="gestionnaire_nom" value="{{ old('gestionnaire_nom') }}"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">5.6. Sexe du gestionnaire</label>
                            <select name="gestionnaire_sexe"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">--</option>
                                @foreach($choices['sexe'] as $opt)
                                    <option value="{{ $opt }}" {{ old('gestionnaire_sexe') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">5.7. Téléphone gestionnaire</label>
                            <input type="text" name="gestionnaire_telephone" value="{{ old('gestionnaire_telephone') }}"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">5.8. Email gestionnaire</label>
                            <input type="email" name="gestionnaire_email" value="{{ old('gestionnaire_email') }}"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <x-ossat-yesno name="gestionnaire_accepte_partage" label="5.9. Le gestionnaire accepte-t-il le partage de ses coordonnées ?" />
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════ S5 : ADMIN & COORD ═══════════════ --}}
        <div class="tab-section hidden" id="tab-s5">
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">6. Administration et Coordination</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-ossat-yesno name="agence_admin" label="6.1. Existe-t-il une agence administrative ?" />
                    <div id="cond_agence_admin_nom">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'agence administrative</label>
                        <input type="text" name="agence_admin_nom" value="{{ old('agence_admin_nom') }}"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <x-ossat-yesno name="admin_dedie" label="5.2. Le site a-t-il un administrateur dédié ?" />
                    <div id="cond_admin_details" class="col-span-full grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom complet de l'administrateur</label>
                            <input type="text" name="admin_nom" value="{{ old('admin_nom') }}"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sexe administrateur</label>
                            <select name="admin_sexe"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">--</option>
                                @foreach($choices['sexe'] as $opt)
                                    <option value="{{ $opt }}" {{ old('admin_sexe') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone administrateur</label>
                            <input type="text" name="admin_telephone" value="{{ old('admin_telephone') }}"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <x-ossat-yesno name="agence_coord" label="6.1. Existe-t-il une agence de coordination ?" />
                    <div id="cond_agence_coord_nom">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'agence de coordination</label>
                        <input type="text" name="agence_coord_nom" value="{{ old('agence_coord_nom') }}"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════ S6 : ORGANISATION INTERNE ═══════════════ --}}
        <div class="tab-section hidden" id="tab-s6">
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">7. Organisation interne</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-ossat-yesno name="bureau_dedie" label="7.1. Existe-t-il un bureau dédié à la gestion du site ?" />
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">7.2. Nombre d'hommes dans l'équipe</label>
                        <input type="number" name="nb_hommes_staff" value="{{ old('nb_hommes_staff', 0) }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">7.3. Nombre de femmes dans l'équipe</label>
                        <input type="number" name="nb_femmes_staff" value="{{ old('nb_femmes_staff', 0) }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <x-ossat-yesno name="presence_comite" label="7.4. Y a-t-il des comités présents sur le site ?" />
                </div>
                <div id="cond_comites_details" class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Types de comités</label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                        @foreach($choices['comites'] as $opt)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="comites[]" value="{{ $opt }}"
                                {{ in_array($opt, old('comites', [])) ? 'checked' : '' }}
                                class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                            {{ $opt }}
                        </label>
                        @endforeach
                    </div>
                    <input type="text" name="autres_comites" value="{{ old('autres_comites') }}" placeholder="Autres comités..."
                        class="mt-2 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                    <div id="cond_comites_election" class="col-span-full grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-ossat-yesno name="comites_elus" label="Les comités ont-ils été élus ?" />
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Les comités fonctionnent-ils ?</label>
                            <select name="comites_fonctionnels"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">--</option>
                                <option value="Oui">Oui</option>
                                <option value="Non">Non</option>
                                <option value="Partiellement">Partiellement</option>
                            </select>
                        </div>
                    </div>
                    <x-ossat-yesno name="reunions_coordination" label="7.8. Des réunions de coordination sont-elles organisées ?" />
                    <div id="cond_periodicite_reunions">
                        <label class="block text-sm font-medium text-gray-700 mb-1">7.9. Périodicité des réunions</label>
                        <select name="periodicite_reunions"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">--</option>
                            @foreach($choices['periodicite'] as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-ossat-yesno name="cci" label="7.6. Existe-t-il un centre communautaire d'information ?" />
                    <x-ossat-yesno name="mgp" label="7.7. Existe-t-il un mécanisme fonctionnel de gestion de plaintes ?" />
                </div>
            </div>
        </div>

        {{-- ═══════════════ S7 : POPULATION ═══════════════ --}}
        <div class="tab-section hidden" id="tab-s7">
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">8. Population</h2>

                {{-- Mouvements --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <x-ossat-yesno name="pdi_nouvelles_arrivees" label="8.1. Y a-t-il des PDI nouvellement arrivées ce mois ?" />
                    <div id="cond_pdi_nouvelles_qtite">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de nouvelles arrivées</label>
                        <input type="number" name="pdi_nouvelles_qtite" value="{{ old('pdi_nouvelles_qtite', 0) }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <x-ossat-yesno name="pdi_retours" label="8.2. Y a-t-il des PDI retournées ce mois ?" />
                    <div id="cond_pdi_retours_qtite">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de retours</label>
                        <input type="number" name="pdi_retours_qtite" value="{{ old('pdi_retours_qtite', 0) }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                {{-- Composition --}}
                <h3 class="text-lg font-semibold text-gray-700 mb-3">Composition du site</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de familles</label>
                        <input type="number" name="nb_familles" value="{{ old('nb_familles', 0) }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre d'individus</label>
                        <input type="number" name="nb_individus" value="{{ old('nb_individus', 0) }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                {{-- Désagrégation âge/sexe --}}
                <h3 class="text-lg font-semibold text-gray-700 mb-3">Désagrégation âge / sexe</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-3 py-2 text-left">Tranche d'âge</th>
                                <th class="px-3 py-2 text-center">Hommes</th>
                                <th class="px-3 py-2 text-center">Femmes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(['0_4'=>'0 – 4 ans','5_17'=>'5 – 17 ans','18_59'=>'18 – 59 ans','60plus'=>'60 ans +'] as $k => $label)
                            <tr class="border-t">
                                <td class="px-3 py-2 font-medium text-gray-700">{{ $label }}</td>
                                <td class="px-3 py-2">
                                    <input type="number" name="h_{{ $k }}" value="{{ old('h_'.$k, 0) }}" min="0"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-center">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" name="f_{{ $k }}" value="{{ old('f_'.$k, 0) }}" min="0"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-center">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Groupes spécifiques --}}
                <h3 class="text-lg font-semibold text-gray-700 mt-6 mb-3">9. Groupes à besoins spécifiques</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach([
                        'menages_femme_chef' => '9.1 Ménages / femme chef',
                        'menages_enfant_chef' => '9.2 Ménages / enfant chef',
                        'enfants_non_accompagnes' => '9.3 Enfants non accompagnés',
                        'handicap_physique' => '9.4 Handicap physique',
                        'handicap_mental' => '9.5 Handicap mental',
                        'maladies_chroniques_nb' => '9.6 Maladies chroniques',
                        'personnes_agees_isolees' => '9.7 Personnes âgées isolées',
                    ] as $field => $label)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                        <input type="number" name="{{ $field }}" value="{{ old($field, 0) }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ═══════════════ S8 : ABRIS & AME ═══════════════ --}}
        <div class="tab-section hidden" id="tab-s8">
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">10 – 14. Abris et AME</h2>

                {{-- Capacité --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">10.2 Capacité d'accueil maximale (familles)</label>
                        <input type="number" name="capacite_accueil" value="{{ old('capacite_accueil', 0) }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">10.3 Familles sur liste d'attente</label>
                        <input type="number" name="familles_attente" value="{{ old('familles_attente', 0) }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <x-ossat-yesno name="reduction_prevue" label="10.4 Réduction du site prévue ?" />
                </div>

                {{-- Types d'abris --}}
                <h3 class="text-lg font-semibold text-gray-700 mb-3">11. Types d'abris présents</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-4">
                    @foreach($choices['types_abri'] as $opt)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="types_abri[]" value="{{ $opt }}"
                            {{ in_array($opt, old('types_abri', [])) ? 'checked' : '' }}
                            class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                        {{ $opt }}
                    </label>
                    @endforeach
                </div>

                {{-- Détails par type (compacts) --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-2 py-2 text-left">Type</th>
                                <th class="px-2 py-2 text-center">Installés</th>
                                <th class="px-2 py-2 text-center">Occupés</th>
                                <th class="px-2 py-2 text-center">Maintenance</th>
                                <th class="px-2 py-2 text-center">Remplacement</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach([
                                ['Tente bâche', 'bache'],
                                ['Matériaux', 'materiaux'],
                                ['Planches', 'planches'],
                                ['Feuilles (RHU)', 'feuilles'],
                                ['Fortune', 'fortune'],
                                ['Autres', 'autres_abris'],
                            ] as [$label, $prefix])
                            <tr class="border-t">
                                <td class="px-2 py-1 font-medium text-gray-700">{{ $label }}</td>
                                @foreach(['installees','occupees','maintenance','remplacement'] as $suffix)
                                <td class="px-1 py-1">
                                    <input type="number" name="{{ $prefix }}_{{ $suffix }}" value="{{ old($prefix.'_'.$suffix, 0) }}" min="0"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-center text-xs">
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                            <tr class="border-t">
                                <td class="px-2 py-1 font-medium text-gray-700">Construites</td>
                                <td class="px-1 py-1">
                                    <input type="number" name="construites_nb" value="{{ old('construites_nb', 0) }}" min="0"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-center text-xs">
                                </td>
                                <td class="px-1 py-1"></td>
                                <td class="px-1 py-1">
                                    <input type="number" name="construites_maintenance" value="{{ old('construites_maintenance', 0) }}" min="0"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-center text-xs">
                                </td>
                                <td class="px-1 py-1"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Sites / Infra --}}
                <h3 class="text-lg font-semibold text-gray-700 mt-6 mb-3">14. État des infrastructures</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach([
                        'etat_parcelles' => '14.1 État des parcelles communautaires',
                        'etat_routes' => '14.2 État des routes',
                        'etat_canaux' => '14.3 État des canaux d\'évacuation',
                    ] as $field => $label)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                        <select name="{{ $field }}"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">--</option>
                            @foreach($choices['etat_infra'] as $opt)
                                <option value="{{ $opt }}" {{ old($field) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endforeach
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">14.4 Risque d'inondation</label>
                        <select name="risque_inondation"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">--</option>
                            @foreach($choices['risque_inondation'] as $opt)
                                <option value="{{ $opt }}" {{ old('risque_inondation') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">14.5 Nombre d'incendies ce mois</label>
                        <input type="number" name="nb_incendies" value="{{ old('nb_incendies', 0) }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <x-ossat-yesno name="mesures_incendie" label="14.6 Mesures de prévention incendie ?" />
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">14.7 Autres problèmes</label>
                    <textarea name="autres_problemes" rows="3"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('autres_problemes') }}</textarea>
                </div>

                {{-- Eclairage --}}
                <h3 class="text-lg font-semibold text-gray-700 mt-6 mb-3">15. Eclairage</h3>
                <x-ossat-yesno name="eclairage_existant" label="15.1 Existe-t-il de l'éclairage sur le site ?" />
                <div id="cond_sources_electricite" class="mt-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sources d'électricité</label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                        @foreach($choices['sources_electricite'] as $opt)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="sources_electricite[]" value="{{ $opt }}"
                                {{ in_array($opt, old('sources_electricite', [])) ? 'checked' : '' }}
                                class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                            {{ $opt }}
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════ S9 : WASH ═══════════════ --}}
        <div class="tab-section hidden" id="tab-s9">
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">16. WASH</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">16.1 Litres/personne/jour</label>
                        <input type="number" name="litres_eau_jour" value="{{ old('litres_eau_jour') }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">16.4 Jours sans accès eau (30 derniers jours)</label>
                        <input type="number" name="jours_sans_eau" value="{{ old('jours_sans_eau', 0) }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <x-ossat-yesno name="qualite_eau" label="16.3 Qualité de l'eau acceptable ?" />
                    <x-ossat-yesno name="defecation_plein_air" label="16.5 Défécation à l'air libre ?" />
                    <x-ossat-yesno name="savon_disponible" label="16.6 Savon disponible pour lavage mains ?" />
                    <x-ossat-yesno name="inondations_6mois" label="16.7 Inondations dommageables (6 derniers mois) ?" />
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">16.8 Méthode d'élimination des déchets</label>
                        <select name="methode_elimination_dechets"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">--</option>
                            @foreach($choices['elimination_dechets'] as $opt)
                                <option value="{{ $opt }}" {{ old('methode_elimination_dechets') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">16.10 Nombre de latrines fonctionnelles</label>
                        <input type="number" name="nb_latrines" value="{{ old('nb_latrines', 0) }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">16.12 Nombre de douches fonctionnelles</label>
                        <input type="number" name="nb_douches" value="{{ old('nb_douches', 0) }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <x-ossat-yesno name="douches_separees" label="16.13 Douches séparées hommes/femmes ?" />
                    <x-ossat-yesno name="latrines_vidangees" label="16.14 Latrines vidangées ?" />
                    <div id="cond_date_derniere_vidange">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date dernière vidange</label>
                        <input type="date" name="date_derniere_vidange" value="{{ old('date_derniere_vidange') }}"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <x-ossat-yesno name="eclairage_latrines" label="16.15 Eclairage autour des latrines/douches ?" />
                    <x-ossat-yesno name="wash_adapte_handicapes" label="16.16 WASH adapté aux personnes handicapées ?" />
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">16.2 Sources d'eau principale</label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                        @foreach($choices['sources_eau'] as $opt)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="sources_eau[]" value="{{ $opt }}"
                                {{ in_array($opt, old('sources_eau', [])) ? 'checked' : '' }}
                                class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                            {{ $opt }}
                        </label>
                        @endforeach
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">16.9 Types de latrines</label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                        @foreach($choices['types_latrines'] as $opt)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="types_latrines[]" value="{{ $opt }}"
                                {{ in_array($opt, old('types_latrines', [])) ? 'checked' : '' }}
                                class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                            {{ $opt }}
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════ S10 : SANTE & ALIMENTATION ═══════════════ --}}
        <div class="tab-section hidden" id="tab-s10">
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">17. Santé</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-ossat-yesno name="soin_sante_fonctionnel" label="17.3 Prestataire de soins fonctionnel disponible ?" />
                    <div id="cond_soin_sante_details" class="col-span-full grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-ossat-yesno name="soin_sante_interieur" label="17.4 Le prestataire est-il à l'intérieur du site ?" />
                        <div id="cond_distance_soin_sante">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Distance du prestataire (km)</label>
                            <input type="text" name="distance_soin_sante" value="{{ old('distance_soin_sante') }}"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Enfants &lt;5 ans non vaccinés (polio/rougeole)</label>
                        <input type="number" name="enfants_non_vaccines" value="{{ old('enfants_non_vaccines', 0) }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <x-ossat-yesno name="services_urgences" label="Services d'urgence ?" />
                    <x-ossat-yesno name="ambulance" label="Ambulance disponible ?" />
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">17.1 Problèmes de santé des 30 derniers jours</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        @foreach($choices['problemes_sante'] as $opt)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="problemes_sante[]" value="{{ $opt }}"
                                {{ in_array($opt, old('problemes_sante', [])) ? 'checked' : '' }}
                                class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                            {{ $opt }}
                        </label>
                        @endforeach
                    </div>
                </div>

                <h2 class="text-xl font-semibold text-gray-800 mt-8 mb-4">19. Sécurité alimentaire</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">19.1 Repas par jour en moyenne</label>
                        <select name="repas_par_jour"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">--</option>
                            @foreach($choices['repas_par_jour'] as $opt)
                                <option value="{{ $opt }}" {{ old('repas_par_jour') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-ossat-yesno name="stockage_magasin" label="19.3 Existence de magasins de stockage des vivres ?" />
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">19.4 Régularité de l'aide alimentaire (3 derniers mois)</label>
                        <select name="regularite_assistance_alimentaire"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">--</option>
                            @foreach($choices['regularite_aide'] as $opt)
                                <option value="{{ $opt }}" {{ old('regularite_assistance_alimentaire') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">19.2 Principales difficultés d'accès à la nourriture</label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                        @foreach($choices['defis_alimentation'] as $opt)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="defis_alimentation[]" value="{{ $opt }}"
                                {{ in_array($opt, old('defis_alimentation', [])) ? 'checked' : '' }}
                                class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                            {{ $opt }}
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════ S11 : PROTECTION ═══════════════ --}}
        <div class="tab-section hidden" id="tab-s11">
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">20. Protection</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-ossat-yesno name="restrictions_mouvement" label="20.1 Restrictions de mouvement ?" />
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">20.3 Tensions avec la communauté d'accueil</label>
                        <select name="tensions_communaute"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">--</option>
                            @foreach($choices['yesno_pasdire'] as $opt)
                                <option value="{{ $opt }}" {{ old('tensions_communaute') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">20.4 Incidents sécuritaires (30 derniers jours)</label>
                        <select name="incidents_securitaires"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">--</option>
                            @foreach($choices['yesno_pasdire'] as $opt)
                                <option value="{{ $opt }}" {{ old('incidents_securitaires') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="cond_nature_incident">
                        <label class="block text-sm font-medium text-gray-700 mb-1">20.6 Nature de l'incident</label>
                        <textarea name="nature_incident" rows="2"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('nature_incident') }}</textarea>
                    </div>
                    <x-ossat-yesno name="sentiment_securite" label="20.7 Les gens se sentent-ils en sécurité ?" />
                    <x-ossat-yesno name="services_handicapes" label="20.11 Services adéquats pour les personnes handicapées ?" />
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">20.13 Familles sans documents nécessaires</label>
                        <input type="number" name="familles_sans_documents" value="{{ old('familles_sans_documents', 0) }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">20.14 Distance tribunaux/état civil (km)</label>
                        <input type="text" name="distance_tribunaux" value="{{ old('distance_tribunaux') }}"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <x-ossat-yesno name="acces_tribunaux" label="20.14 Accès aux tribunaux accessible ?" />
                </div>
            </div>
        </div>

        {{-- ═══════════════ S12 : EDUCATION & SUBSISTANCE ═══════════════ --}}
        <div class="tab-section hidden" id="tab-s12">
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">21. Éducation</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-ossat-yesno name="ecole_primaire_presente" label="21.1 École primaire fonctionnelle à l'intérieur ?" />
                    <div id="cond_distance_ecole_primaire">
                        <label class="block text-sm font-medium text-gray-700 mb-1">21.2 Distance école primaire (km)</label>
                        <input type="text" name="distance_ecole_primaire" value="{{ old('distance_ecole_primaire') }}"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <x-ossat-yesno name="ecole_secondaire_presente" label="21.3 École secondaire fonctionnelle à l'intérieur ?" />
                    <div id="cond_distance_ecole_secondaire">
                        <label class="block text-sm font-medium text-gray-700 mb-1">21.4 Distance école secondaire (km)</label>
                        <input type="text" name="distance_ecole_secondaire" value="{{ old('distance_ecole_secondaire') }}"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">21.5 Enfants en âge scolaire avec accès</label>
                        <input type="number" name="nb_enfants_scolarises" value="{{ old('nb_enfants_scolarises', 0) }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <x-ossat-yesno name="education_informelle" label="21.7 Éducation informelle disponible ?" />
                    <div id="cond_nb_enfants_education_informelle">
                        <label class="block text-sm font-medium text-gray-700 mb-1">21.8 Enfants avec accès éducation informelle</label>
                        <input type="number" name="nb_enfants_education_informelle" value="{{ old('nb_enfants_education_informelle', 0) }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <h2 class="text-xl font-semibold text-gray-800 mt-8 mb-4">22. Moyens de subsistance</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-ossat-yesno name="marche_interieur" label="22.1 Marché à l'intérieur du site ?" />
                    <div id="cond_distance_marche">
                        <label class="block text-sm font-medium text-gray-700 mb-1">22.2 Distance du marché le plus proche (km)</label>
                        <input type="text" name="distance_marche" value="{{ old('distance_marche') }}"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">22.5 Familles ayant un revenu (30 derniers jours)</label>
                        <input type="number" name="nb_familles_avec_revenu" value="{{ old('nb_familles_avec_revenu', 0) }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">22.6 Jeunes (&lt;18 ans) ayant travaillé</label>
                        <input type="number" name="nb_jeunes_travaillant" value="{{ old('nb_jeunes_travaillant', 0) }}" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <x-ossat-yesno name="enclos_betail" label="22.7 Existence d'enclos pour bétail ?" />
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">22.4 Principaux moyens de subsistance</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        @foreach($choices['sources_subsistance'] as $opt)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="sources_subsistance[]" value="{{ $opt }}"
                                {{ in_array($opt, old('sources_subsistance', [])) ? 'checked' : '' }}
                                class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                            {{ $opt }}
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════ S13 : PRIORITES & ACCES SERVICES ═══════════════ --}}
        <div class="tab-section hidden" id="tab-s13">
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">23. Besoins prioritaires</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach(['besoin_prioritaire_1'=>'1er besoin','besoin_prioritaire_2'=>'2ème besoin','besoin_prioritaire_3'=>'3ème besoin'] as $field => $label)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                        <select name="{{ $field }}"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">--</option>
                            @foreach($choices['besoin_prioritaire'] as $opt)
                                <option value="{{ $opt }}" {{ old($field) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endforeach
                </div>

                <h2 class="text-xl font-semibold text-gray-800 mt-8 mb-4">24. Accès aux services</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach([
                        'acces_education'=>'24.1 Éducation',
                        'acces_vivres'=>'24.2 Distribution vivres',
                        'acces_sante'=>'24.3 Santé',
                        'acces_sante_mentale'=>'24.4 Santé mentale/psychosocial',
                        'acces_subsistance'=>'24.5 Moyens d\'existences',
                        'acces_cash'=>'24.6 Distribution cash',
                        'acces_nfi'=>'24.7 NFI/AME',
                        'acces_nutrition'=>'24.8 Nutrition',
                        'acces_protection'=>'24.9 Protection',
                        'acces_abri'=>'24.10 Maintenance abris',
                        'acces_wash'=>'24.11 WASH',
                        'acces_dechets'=>'24.12 Élimination déchets',
                    ] as $field => $label)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                        <select name="{{ $field }}"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">--</option>
                            @foreach($choices['acces'] as $opt)
                                <option value="{{ $opt }}" {{ old($field) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ═══════════════ S14 : PARTENAIRES ═══════════════ --}}
        <div class="tab-section hidden" id="tab-s14">
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">25. Cartographie des acteurs / Partenaires</h2>
                @php
                $secteurs = [
                    ['protection','Protection'],['gbv','VBG'],['enfance','Protection enfance'],
                    ['education','Éducation'],['abri','Abri & AME'],['eau','Eau'],
                    ['assainissement','Assainissement'],['dechets','Gestion des déchets'],
                    ['sante_primaire','Santé primaire'],['sante_secondaire','Santé secondaire'],
                    ['mhpss','Santé mentale/psychosociale'],['nutrition','Nutrition'],
                    ['alimentaire','Sécurité alimentaire'],['cohesion','Cohésion sociale'],
                    ['subsistance','Moyens de subsistance'],['communication','Communication'],
                ];
                @endphp
                <div class="grid grid-cols-1 gap-4">
                    @foreach($secteurs as [$key, $label])
                    <div class="border rounded-lg p-4">
                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-2 font-medium text-gray-700 w-64 shrink-0">
                                <input type="checkbox" name="partenaires_{{ $key }}_presence" value="1"
                                    {{ old('partenaires_'.$key.'_presence') ? 'checked' : '' }}
                                    class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                {{ $label }}
                            </label>
                            <input type="text" name="partenaires_{{ $key }}_autre"
                                value="{{ old('partenaires_'.$key.'_autre') }}"
                                placeholder="Noms des partenaires (séparés par virgule)..."
                                class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ═══ ACTIONS ═══ --}}
        <div class="flex justify-between items-center mt-6 bg-white rounded-lg shadow p-4">
            <button type="button" id="prev-tab"
                class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md">
                ← Précédent
            </button>
            <div class="flex gap-3">
                <button type="submit" name="action" value="brouillon"
                    class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md">
                    Sauvegarder brouillon
                </button>
                <button type="submit" name="action" value="soumettre"
                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md">
                    Soumettre le rapport
                </button>
            </div>
            <button type="button" id="next-tab"
                class="px-6 py-2 bg-blue-200 hover:bg-blue-300 text-blue-700 rounded-md">
                Suivant →
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = Array.from(document.querySelectorAll('.tab-btn'));
    const sections = Array.from(document.querySelectorAll('.tab-section'));
    let currentIndex = 0;

    function showTab(index) {
        tabs.forEach((btn, i) => {
            btn.classList.toggle('border-blue-600', i === index);
            btn.classList.toggle('text-blue-600', i === index);
            btn.classList.toggle('border-transparent', i !== index);
            btn.classList.toggle('text-gray-600', i !== index);
        });
        sections.forEach((sec, i) => sec.classList.toggle('hidden', i !== index));
        currentIndex = index;
        document.getElementById('prev-tab').disabled = index === 0;
        document.getElementById('next-tab').disabled = index === tabs.length - 1;
    }

    tabs.forEach((btn, i) => btn.addEventListener('click', () => showTab(i)));
    document.getElementById('next-tab').addEventListener('click', () => {
        if (currentIndex < tabs.length - 1) showTab(currentIndex + 1);
    });
    document.getElementById('prev-tab').addEventListener('click', () => {
        if (currentIndex > 0) showTab(currentIndex - 1);
    });

    showTab(0);

    // Cascade Province → Territoire → Site
    const provinceSelect = document.getElementById('province_id');
    const territoireSelect = document.getElementById('territoire_id');
    const siteSelect = document.getElementById('site_id');

    function resetSiteSelect() {
        siteSelect.innerHTML = '<option value="">Sélectionner d\'abord le territoire</option>';
        document.getElementById('site_nom_hidden').value = '';
        document.getElementById('site_code_hidden').value = '';
    }

    function loadSites(territoireId, selectedSiteId) {
        if (!territoireId) { resetSiteSelect(); return; }
        fetch('/api/sites-par-territoire?territoire_id=' + territoireId)
            .then(function(r) { return r.json(); })
            .then(function(sites) {
                siteSelect.innerHTML = '<option value="">-- Sélectionner le site --</option>';
                sites.forEach(function(s) {
                    const opt = new Option(s.nom + (s.code_site ? ' (' + s.code_site + ')' : ''), s.id);
                    opt.dataset.nom = s.nom;
                    opt.dataset.code = s.code_site || '';
                    if (selectedSiteId && s.id == selectedSiteId) opt.selected = true;
                    siteSelect.appendChild(opt);
                });
            });
    }

    if (provinceSelect && territoireSelect) {
        const allTerritoireOptions = Array.from(territoireSelect.querySelectorAll('option[data-province]'));

        function filterTerritoires(provinceId) {
            allTerritoireOptions.forEach(function(opt) {
                opt.hidden = provinceId && opt.dataset.province != provinceId;
            });
        }

        provinceSelect.addEventListener('change', function () {
            filterTerritoires(this.value);
            territoireSelect.value = '';
            resetSiteSelect();
        });
        filterTerritoires(provinceSelect.value);
    }

    // Initialisation avec pré-sélection depuis profil du site
    @if(isset($preselectedSite))
    (function() {
        const initTerritoire = {{ $preselectedSite->commune?->territoire_id ?? 'null' }};
        const initSite      = {{ $preselectedSite->id ?? 'null' }};
        if (initTerritoire) {
            if (typeof filterTerritoires === 'function') filterTerritoires(provinceSelect.value);
            loadSites(initTerritoire, initSite);
        }
    })();
    @endif

    territoireSelect.addEventListener('change', function() {
        loadSites(this.value, null);
    });

    siteSelect.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        document.getElementById('site_nom_hidden').value = opt ? (opt.dataset.nom || '') : '';
        document.getElementById('site_code_hidden').value = opt ? (opt.dataset.code || '') : '';
    });

    // Géolocalisation
    const geoBtn = document.getElementById('geolocate-btn');
    if (geoBtn) {
        geoBtn.addEventListener('click', function () {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (pos) {
                    document.querySelector('input[name="gps_latitude"]').value = pos.coords.latitude.toFixed(7);
                    document.querySelector('input[name="gps_longitude"]').value = pos.coords.longitude.toFixed(7);
                    document.querySelector('input[name="gps_altitude"]').value = pos.coords.altitude ? pos.coords.altitude.toFixed(2) : '';
                }, function (err) {
                    alert('Erreur GPS : ' + err.message);
                });
            } else {
                alert('Géolocalisation non supportée par ce navigateur.');
            }
        });
    }
    // ── Logique conditionnelle ────────────────────────────────────────────
    function condShow(name, ids, showValue) {
        const el = document.querySelector('[name="' + name + '"]');
        if (!el) return;
        const list = Array.isArray(ids) ? ids : [ids];
        function update() {
            const v = el.value;
            const vis = Array.isArray(showValue) ? showValue.includes(v) : v === showValue;
            list.forEach(function(id) { const t = document.getElementById(id); if (t) t.classList.toggle('hidden', !vis); });
        }
        el.addEventListener('change', update);
        update();
    }

    // S1
    condShow('fait_partie_agence', 'cond_agence_enqueteur', '1');
    // S4
    condShow('agence_gestion', 'cond_agence_gestion_nom', '1');
    condShow('gestionnaire_dedie', 'cond_gestionnaire_details', '1');
    // S5
    condShow('agence_admin', 'cond_agence_admin_nom', '1');
    condShow('admin_dedie', 'cond_admin_details', '1');
    condShow('agence_coord', 'cond_agence_coord_nom', '1');
    // S6
    condShow('presence_comite', ['cond_comites_details', 'cond_comites_election'], '1');
    condShow('reunions_coordination', 'cond_periodicite_reunions', '1');
    // S7
    condShow('pdi_nouvelles_arrivees', 'cond_pdi_nouvelles_qtite', '1');
    condShow('pdi_retours', 'cond_pdi_retours_qtite', '1');
    // S8
    condShow('eclairage_existant', 'cond_sources_electricite', '1');
    // S9
    condShow('latrines_vidangees', 'cond_date_derniere_vidange', '1');
    // S10
    condShow('soin_sante_fonctionnel', 'cond_soin_sante_details', '1');
    condShow('soin_sante_interieur', 'cond_distance_soin_sante', '0');
    // S11
    condShow('incidents_securitaires', 'cond_nature_incident', 'Oui');
    // S12
    condShow('ecole_primaire_presente', 'cond_distance_ecole_primaire', '0');
    condShow('ecole_secondaire_presente', 'cond_distance_ecole_secondaire', '0');
    condShow('education_informelle', 'cond_nb_enfants_education_informelle', '1');
    condShow('marche_interieur', 'cond_distance_marche', '0');
});
</script>
@endpush
