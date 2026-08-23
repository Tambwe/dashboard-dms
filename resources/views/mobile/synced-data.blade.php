@extends('layouts.app')

@section('content')
<div class="max-w-[1500px] mx-auto p-4 md:p-8">
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-primary-600">Mobile</p>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Données synchronisées</h1>
        <p class="mt-1 text-sm text-gray-600">Liste des formulaires synchronisés depuis l'application mobile.</p>
        </div>
        <a href="{{ route('mobile.synced-data.export') }}" class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">
            Exporter en Excel
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @php
        $allSynced = $mobileSynced->concat($questionnaireSynced);
        $validatedCount = $allSynced->where('validation_status', 'validated')->count();
    @endphp
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4 mb-8">
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <p class="text-xs uppercase tracking-[0.2em] text-gray-500">Collectes synchronisées</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ $mobileSynced->count() }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <p class="text-xs uppercase tracking-[0.2em] text-gray-500">Questionnaires synchronisés</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ $questionnaireSynced->count() }}</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
            <p class="text-xs uppercase tracking-[0.2em] text-amber-700">En attente de validation</p>
            <p class="mt-2 text-3xl font-bold text-amber-900">{{ $allSynced->count() - $validatedCount }}</p>
        </div>
        <div class="rounded-xl border border-green-200 bg-green-50 p-5">
            <p class="text-xs uppercase tracking-[0.2em] text-green-700">Validés</p>
            <p class="mt-2 text-3xl font-bold text-green-900">{{ $validatedCount }}</p>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-gray-200 bg-gray-50/70">
            <h2 class="font-semibold text-gray-900">Collectes (sector/geography/ossat) synchronisées</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left">ID</th>
                        <th class="px-4 py-3 text-left">Type</th>
                        <th class="px-4 py-3 text-left">Province</th>
                        <th class="px-4 py-3 text-left">Territoire</th>
                        <th class="px-4 py-3 text-left">Commune</th>
                        <th class="px-4 py-3 text-left">Site</th>
                        <th class="px-4 py-3 text-left">Utilisateur</th>
                        <th class="px-4 py-3 text-left">Date sync</th>
                        <th class="px-4 py-3 text-left">Validation</th>
                        <th class="px-4 py-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($mobileSynced as $item)
                        @php
                            $payload = is_array($item->payload) ? $item->payload : [];
                            $provinceName = $provinceMap[(int)($payload['province_id'] ?? 0)] ?? '-';
                            $territoireName = $territoireMap[(int)($payload['territoire_id'] ?? 0)] ?? '-';
                            $communeName = $communeMap[(int)($payload['commune_id'] ?? 0)] ?? '-';
                            $siteLabel = $item->site ? ($item->site->nom.' ('.($item->site->code_site ?? 'N/A').')') : ('Site '.($item->site_id ?? '-'));
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $item->id }}</td>
                            <td class="px-4 py-3">{{ $item->type }}</td>
                            <td class="px-4 py-3">{{ $provinceName }}</td>
                            <td class="px-4 py-3">{{ $territoireName }}</td>
                            <td class="px-4 py-3">{{ $communeName }}</td>
                            <td class="px-4 py-3">{{ $siteLabel }}</td>
                            <td class="px-4 py-3">{{ $item->user->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ optional($item->synced_at)->format('Y-m-d H:i') ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @if($item->validation_status === 'validated')
                                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">Validé</span>
                                @else
                                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">En attente</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('mobile.synced-data.show', ['source' => 'mobile', 'id' => $item->id]) }}" class="rounded-md border border-gray-300 px-2 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-100">Voir</a>
                                    <a href="{{ route('mobile.synced-data.edit', ['source' => 'mobile', 'id' => $item->id]) }}" class="rounded-md border border-primary-200 px-2 py-1 text-xs font-semibold text-primary-700 hover:bg-primary-50">Éditer</a>
                                    @if($item->validation_status !== 'validated')
                                        <form method="POST" action="{{ route('mobile.synced-data.validate', ['source' => 'mobile', 'id' => $item->id]) }}" onsubmit="return confirm('Valider ce formulaire synchronisé ?');">
                                            @csrf
                                            <button type="submit" class="rounded-md border border-green-200 px-2 py-1 text-xs font-semibold text-green-700 hover:bg-green-50">Valider</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('mobile.synced-data.destroy', ['source' => 'mobile', 'id' => $item->id]) }}" onsubmit="return confirm('Supprimer ce formulaire synchronisé ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md border border-red-200 px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-50">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-6 text-center text-gray-500">Aucune collecte synchronisée.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 bg-gray-50/70">
            <h2 class="font-semibold text-gray-900">Questionnaires synchronisés</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left">ID</th>
                        <th class="px-4 py-3 text-left">Questionnaire</th>
                        <th class="px-4 py-3 text-left">Province</th>
                        <th class="px-4 py-3 text-left">Territoire</th>
                        <th class="px-4 py-3 text-left">Commune</th>
                        <th class="px-4 py-3 text-left">Site</th>
                        <th class="px-4 py-3 text-left">Utilisateur</th>
                        <th class="px-4 py-3 text-left">Date sync</th>
                        <th class="px-4 py-3 text-left">Validation</th>
                        <th class="px-4 py-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($questionnaireSynced as $item)
                        @php
                            $siteLabel = $item->site ? ($item->site->nom.' ('.($item->site->code_site ?? 'N/A').')') : ('Site '.($item->site_id ?? '-'));
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $item->id }}</td>
                            <td class="px-4 py-3">{{ $item->questionnaire->title ?? ($item->questionnaire->code ?? '-') }}</td>
                            <td class="px-4 py-3">{{ $provinceMap[(int)($item->province_id ?? 0)] ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $territoireMap[(int)($item->territoire_id ?? 0)] ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $communeMap[(int)($item->commune_id ?? 0)] ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $siteLabel }}</td>
                            <td class="px-4 py-3">{{ $item->user->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ optional($item->synced_at)->format('Y-m-d H:i') ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @if($item->validation_status === 'validated')
                                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">Validé</span>
                                @else
                                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">En attente</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('mobile.synced-data.show', ['source' => 'questionnaire', 'id' => $item->id]) }}" class="rounded-md border border-gray-300 px-2 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-100">Voir</a>
                                    <a href="{{ route('mobile.synced-data.edit', ['source' => 'questionnaire', 'id' => $item->id]) }}" class="rounded-md border border-primary-200 px-2 py-1 text-xs font-semibold text-primary-700 hover:bg-primary-50">Éditer</a>
                                    @if($item->validation_status !== 'validated')
                                        <form method="POST" action="{{ route('mobile.synced-data.validate', ['source' => 'questionnaire', 'id' => $item->id]) }}" onsubmit="return confirm('Valider ce formulaire synchronisé ?');">
                                            @csrf
                                            <button type="submit" class="rounded-md border border-green-200 px-2 py-1 text-xs font-semibold text-green-700 hover:bg-green-50">Valider</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('mobile.synced-data.destroy', ['source' => 'questionnaire', 'id' => $item->id]) }}" onsubmit="return confirm('Supprimer ce formulaire synchronisé ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md border border-red-200 px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-50">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-6 text-center text-gray-500">Aucun questionnaire synchronisé.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
