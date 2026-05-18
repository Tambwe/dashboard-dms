@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-6xl">
    <div class="mb-6">
        <a href="{{ route('households.show', $household) }}" class="text-blue-600 hover:text-blue-800 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour aux détails
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-md p-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Modifier le Ménage</h1>
            <p class="text-gray-600 mt-2">Numéro: {{ $household->numero_menage }}</p>
        </div>

        <form method="POST" action="{{ route('households.update', $household) }}" id="householdForm">
            @csrf
            @method('PUT')

            <!-- Utiliser le même formulaire que create, mais pré-rempli -->
            @include('households.partials.form', ['household' => $household])

            <div class="flex justify-end gap-4 mt-8">
                <a href="{{ route('households.show', $household) }}" class="px-6 py-3 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Annuler
                </a>
                <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-md">
                    Mettre à Jour
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
