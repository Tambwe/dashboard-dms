@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('households.show', $household) }}" class="text-blue-600 hover:text-blue-800 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour aux détails
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-md p-8">
        <div class="text-center mb-8">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Passer au Niveau 2</h1>
            <p class="text-gray-600">Ménage: {{ $household->chef_nom_complet }}</p>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold text-blue-900 mb-3">Qu'est-ce que le Niveau 2 ?</h2>
            <ul class="space-y-2 text-blue-800">
                <li class="flex items-start">
                    <svg class="w-5 h-5 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>Enregistrement détaillé de tous les membres du ménage</span>
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>Informations individuelles (nom, âge, lien familial, etc.)</span>
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>Photos et empreintes digitales pour chaque membre</span>
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>Meilleur suivi et identification des vulnérabilités individuelles</span>
                </li>
            </ul>
        </div>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-yellow-900 mb-2">⚠️ Important</h3>
            <p class="text-yellow-800">
                Cette action est <strong>irréversible</strong>. Une fois passé au Niveau 2, vous devrez enregistrer individuellement 
                tous les membres du ménage (actuellement <strong>{{ $household->nombre_total_personnes }} personnes</strong>).
            </p>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-8">
            <div class="bg-gray-50 p-4 rounded-lg">
                <div class="text-sm text-gray-600 mb-1">État actuel</div>
                <div class="text-2xl font-bold text-gray-800">Niveau 1</div>
                <div class="text-sm text-gray-600 mt-1">Données agrégées uniquement</div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <div class="text-sm text-gray-600 mb-1">Après validation</div>
                <div class="text-2xl font-bold text-green-600">Niveau 2</div>
                <div class="text-sm text-gray-600 mt-1">Données individuelles complètes</div>
            </div>
        </div>

        <form method="POST" action="{{ route('households.process-upgrade-to-level2', $household) }}">
            @csrf
            
            <div class="flex justify-end gap-4">
                <a href="{{ route('households.show', $household) }}" class="px-6 py-3 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Annuler
                </a>
                <button type="submit" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-md">
                    Confirmer le passage au Niveau 2
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
