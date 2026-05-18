@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Rapport : {{ $ossat->site_nom }}</h1>
            <p class="text-gray-500">{{ $ossat->type_installation }} – {{ $ossat->province->name ?? '' }} / {{ $ossat->territoire->name ?? '' }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('ossat.edit', $ossat) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md text-sm">Modifier</a>
            <a href="{{ route('ossat.index') }}" class="text-blue-600 hover:text-blue-800 text-sm flex items-center gap-1">← Retour</a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- Infos générales --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-700 mb-3 border-b pb-2">Informations générales</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Enquêteur</dt><dd>{{ $ossat->enumerator_name }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Date de collecte</dt><dd>{{ $ossat->today?->format('d/m/Y') }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Statut du site</dt><dd>{{ $ossat->statut }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">GPS</dt><dd>{{ $ossat->gps_latitude }}, {{ $ossat->gps_longitude }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Validation</dt><dd><span class="font-medium">{{ $ossat->statut_validation }}</span></dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Saisi par</dt><dd>{{ $ossat->createdBy?->name }}</dd></div>
            </dl>
        </div>

        {{-- Population --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-700 mb-3 border-b pb-2">Population</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Familles</dt><dd>{{ number_format($ossat->nb_familles ?? 0) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Individus</dt><dd>{{ number_format($ossat->nb_individus ?? 0) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Hommes 0-4</dt><dd>{{ $ossat->h_0_4 ?? 0 }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Femmes 0-4</dt><dd>{{ $ossat->f_0_4 ?? 0 }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Hommes 18-59</dt><dd>{{ $ossat->h_18_59 ?? 0 }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Femmes 18-59</dt><dd>{{ $ossat->f_18_59 ?? 0 }}</dd></div>
            </dl>
        </div>

        {{-- Besoins prioritaires --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-700 mb-3 border-b pb-2">Besoins prioritaires</h3>
            <ol class="list-decimal list-inside text-sm text-gray-700 space-y-1">
                @if($ossat->besoin_prioritaire_1)<li>{{ $ossat->besoin_prioritaire_1 }}</li>@endif
                @if($ossat->besoin_prioritaire_2)<li>{{ $ossat->besoin_prioritaire_2 }}</li>@endif
                @if($ossat->besoin_prioritaire_3)<li>{{ $ossat->besoin_prioritaire_3 }}</li>@endif
            </ol>
        </div>

        {{-- Accès services --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-700 mb-3 border-b pb-2">Accès aux services</h3>
            <dl class="grid grid-cols-2 gap-1 text-sm">
                @foreach([
                    'acces_education'=>'Éducation','acces_vivres'=>'Vivres',
                    'acces_sante'=>'Santé','acces_nfi'=>'NFI/AME',
                    'acces_wash'=>'WASH','acces_protection'=>'Protection',
                    'acces_abri'=>'Abri','acces_nutrition'=>'Nutrition',
                ] as $field => $label)
                <div class="flex justify-between">
                    <dt class="text-gray-500">{{ $label }}</dt>
                    <dd>{{ $ossat->$field ?? '–' }}</dd>
                </div>
                @endforeach
            </dl>
        </div>
    </div>
</div>
@endsection
