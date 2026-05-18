@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">
    <div class="mb-6 flex justify-between items-center">
        <a href="{{ route('households.index') }}" class="text-blue-600 hover:text-blue-800 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour à la liste
        </a>
        <div class="flex gap-2">
            <a href="{{ route('households.edit', $household) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                Modifier
            </a>
            @if($household->niveau_enregistrement === '1')
            <a href="{{ route('households.upgrade-to-level2', $household) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                Passer au Niveau 2
            </a>
            @else
            <a href="{{ route('households.members.create', $household) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                Ajouter un Membre
            </a>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-8 mb-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">{{ $household->chef_nom_complet }}</h1>
                <p class="text-gray-600 mt-1">Numéro: {{ $household->numero_menage }}</p>
            </div>
            <div class="flex gap-2">
                <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $household->getNiveauBadgeClass() }}">
                    Niveau {{ $household->niveau_enregistrement }}
                </span>
                <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $household->getStatusBadgeClass() }}">
                    {{ ucfirst($household->statut) }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Photo -->
            @if($household->chef_photo)
            <div>
                <h3 class="text-lg font-semibold mb-3">Photo</h3>
                <img src="{{ Storage::url($household->chef_photo) }}" alt="Photo" class="w-48 h-48 object-cover rounded-lg border">
            </div>
            @endif

            <!-- Informations Générales -->
            <div>
                <h3 class="text-lg font-semibold mb-3">Informations Générales</h3>
                <dl class="space-y-2">
                    <div class="flex">
                        <dt class="font-medium text-gray-600 w-32">Site:</dt>
                        <dd>{{ $household->site->nom }}</dd>
                    </div>
                    <div class="flex">
                        <dt class="font-medium text-gray-600 w-32">Sexe:</dt>
                        <dd>{{ $household->chef_sexe === 'M' ? 'Masculin' : 'Féminin' }}</dd>
                    </div>
                    @if($household->chef_age)
                    <div class="flex">
                        <dt class="font-medium text-gray-600 w-32">Âge:</dt>
                        <dd>{{ $household->chef_age }} ans</dd>
                    </div>
                    @endif
                    @if($household->chef_telephone)
                    <div class="flex">
                        <dt class="font-medium text-gray-600 w-32">Téléphone:</dt>
                        <dd>{{ $household->chef_telephone }}</dd>
                    </div>
                    @endif
                    @if($household->chef_etat_civil)
                    <div class="flex">
                        <dt class="font-medium text-gray-600 w-32">État civil:</dt>
                        <dd>{{ $household->chef_etat_civil }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    <!-- Composition du Ménage -->
    <div class="bg-white rounded-lg shadow-md p-8 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Composition du Ménage</h2>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-6">
            <div class="text-center p-4 bg-blue-50 rounded-lg">
                <div class="text-3xl font-bold text-blue-600">{{ $household->nombre_hommes }}</div>
                <div class="text-sm text-gray-600 mt-1">Hommes (18+)</div>
            </div>
            <div class="text-center p-4 bg-pink-50 rounded-lg">
                <div class="text-3xl font-bold text-pink-600">{{ $household->nombre_femmes }}</div>
                <div class="text-sm text-gray-600 mt-1">Femmes (18+)</div>
            </div>
            <div class="text-center p-4 bg-cyan-50 rounded-lg">
                <div class="text-3xl font-bold text-cyan-600">{{ $household->nombre_garcons }}</div>
                <div class="text-sm text-gray-600 mt-1">Garçons (&lt;18)</div>
            </div>
            <div class="text-center p-4 bg-purple-50 rounded-lg">
                <div class="text-3xl font-bold text-purple-600">{{ $household->nombre_filles }}</div>
                <div class="text-sm text-gray-600 mt-1">Filles (&lt;18)</div>
            </div>
            <div class="text-center p-4 bg-green-50 rounded-lg border-2 border-green-500">
                <div class="text-3xl font-bold text-green-600">{{ $household->nombre_total_personnes }}</div>
                <div class="text-sm font-semibold text-gray-600 mt-1">TOTAL</div>
            </div>
        </div>
    </div>

    <!-- Membres Enregistrés (Niveau 2) -->
    @if($household->niveau_enregistrement === '2' && $household->members->count() > 0)
    <div class="bg-white rounded-lg shadow-md p-8 mb-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Membres du Ménage ({{ $household->members->count() }})</h2>
            <a href="{{ route('households.members.create', $household) }}" class="text-blue-600 hover:text-blue-800">
                + Ajouter un membre
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom Complet</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sexe/Âge</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lien</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($household->members as $member)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-900">{{ $member->nom_complet }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs rounded-full {{ $member->getSexeBadgeClass() }}">
                                {{ $member->sexe }}
                            </span>
                            <span class="text-sm text-gray-600 ml-2">{{ $member->age }} ans</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $member->lien_avec_chef }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs rounded-full {{ $member->getStatusBadgeClass() }}">
                                {{ ucfirst($member->statut) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-sm">
                            <a href="{{ route('households.members.edit', [$household, $member]) }}" class="text-blue-600 hover:text-blue-900">Modifier</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Vulnérabilités -->
    @if($household->hasVulnerabilites())
    <div class="bg-white rounded-lg shadow-md p-8 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Vulnérabilités</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @if($household->nombre_femmes_enceintes > 0)
            <div class="flex items-center p-3 bg-red-50 rounded-lg">
                <svg class="w-8 h-8 text-red-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 2a8 8 0 100 16 8 8 0 000-16z"/>
                </svg>
                <div>
                    <div class="font-semibold text-gray-900">{{ $household->nombre_femmes_enceintes }}</div>
                    <div class="text-xs text-gray-600">Femmes enceintes</div>
                </div>
            </div>
            @endif
            @if($household->nombre_personnes_handicapees > 0)
            <div class="flex items-center p-3 bg-orange-50 rounded-lg">
                <svg class="w-8 h-8 text-orange-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 2a8 8 0 100 16 8 8 0 000-16z"/>
                </svg>
                <div>
                    <div class="font-semibold text-gray-900">{{ $household->nombre_personnes_handicapees }}</div>
                    <div class="text-xs text-gray-600">Personnes handicapées</div>
                </div>
            </div>
            @endif
            @if($household->nombre_personnes_agees > 0)
            <div class="flex items-center p-3 bg-purple-50 rounded-lg">
                <svg class="w-8 h-8 text-purple-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 2a8 8 0 100 16 8 8 0 000-16z"/>
                </svg>
                <div>
                    <div class="font-semibold text-gray-900">{{ $household->nombre_personnes_agees }}</div>
                    <div class="text-xs text-gray-600">Personnes âgées</div>
                </div>
            </div>
            @endif
            @if($household->nombre_enfants_orphelins > 0)
            <div class="flex items-center p-3 bg-yellow-50 rounded-lg">
                <svg class="w-8 h-8 text-yellow-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 2a8 8 0 100 16 8 8 0 000-16z"/>
                </svg>
                <div>
                    <div class="font-semibold text-gray-900">{{ $household->nombre_enfants_orphelins }}</div>
                    <div class="text-xs text-gray-600">Enfants orphelins</div>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Origine et Déplacement -->
    <div class="bg-white rounded-lg shadow-md p-8 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Origine et Déplacement</h2>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @if($household->provinceOrigine)
            <div>
                <dt class="font-medium text-gray-600">Province d'origine:</dt>
                <dd class="text-gray-900">{{ $household->provinceOrigine->name }}</dd>
            </div>
            @endif
            @if($household->territoireOrigine)
            <div>
                <dt class="font-medium text-gray-600">Territoire d'origine:</dt>
                <dd class="text-gray-900">{{ $household->territoireOrigine->name }}</dd>
            </div>
            @endif
            @if($household->date_arrivee_site)
            <div>
                <dt class="font-medium text-gray-600">Date d'arrivée:</dt>
                <dd class="text-gray-900">{{ $household->date_arrivee_site->format('d/m/Y') }}</dd>
            </div>
            @endif
        </dl>
        @if($household->raison_deplacement)
        <div class="mt-4">
            <dt class="font-medium text-gray-600 mb-2">Raison du déplacement:</dt>
            <dd class="text-gray-900 bg-gray-50 p-4 rounded">{{ $household->raison_deplacement }}</dd>
        </div>
        @endif
    </div>

    @if($household->observations)
    <div class="bg-white rounded-lg shadow-md p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Observations</h2>
        <p class="text-gray-700">{{ $household->observations }}</p>
    </div>
    @endif
</div>
@endsection
