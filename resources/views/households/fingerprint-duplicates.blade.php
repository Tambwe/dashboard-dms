@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">

    {{-- ── En-tête ──────────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-2">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                Détection de doublons biométriques
            </h1>
            <p class="text-gray-600 mt-1">
                Analyse croisée des empreintes digitales pour identifier les personnes enregistrées plusieurs fois.
            </p>
        </div>
        <a href="{{ route('households.index') }}"
           class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour aux ménages
        </a>
    </div>

    {{-- ── Avertissement DactyMatch manquant ──────────────────────────────── --}}
    @if($dactyMissing)
    <div class="bg-amber-50 border border-amber-300 rounded-xl p-4 mb-6 flex items-start gap-3">
        <svg class="w-6 h-6 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        <div>
            <p class="font-semibold text-amber-800">Librairie DactyMatch non installée (GBFRSW)</p>
            <p class="text-amber-700 text-sm mt-1">
                Le scanner Thales a bien capturé les images, mais la génération des templates FMR_ISO nécessite
                la librairie <strong>DactyMatch</strong> (licence Thales). Sans elle, le matching biométrique minuties est désactivé.
            </p>
            <p class="text-amber-700 text-sm mt-1">
                <strong>Mode actuel :</strong> détection par hash d'image uniquement — identifie les personnes
                scannées deux fois dans la même session (image bit-à-bit identique).
            </p>
        </div>
    </div>
    @endif

    {{-- ── Formulaire de scan ───────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Paramètres du scan
        </h2>

        {{-- Stats rapides --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-50 rounded-lg p-4 flex items-center gap-3">
                <div class="p-2 bg-blue-100 rounded-full">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-blue-600 font-medium">Ménages avec empreintes</p>
                    <p class="text-2xl font-bold text-blue-800">{{ $total }}</p>
                </div>
            </div>

            <div class="bg-purple-50 rounded-lg p-4 flex items-center gap-3">
                <div class="p-2 bg-purple-100 rounded-full">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-purple-600 font-medium">Comparaisons à effectuer</p>
                    <p class="text-2xl font-bold text-purple-800">{{ number_format($comparisons, 0, ',', ' ') }}</p>
                </div>
            </div>

            <div class="bg-{{ isset($duplicates) ? (count($duplicates) > 0 ? 'red' : 'green') : 'gray' }}-50 rounded-lg p-4 flex items-center gap-3">
                <div class="p-2 bg-{{ isset($duplicates) ? (count($duplicates) > 0 ? 'red' : 'green') : 'gray' }}-100 rounded-full">
                    <svg class="w-6 h-6 text-{{ isset($duplicates) ? (count($duplicates) > 0 ? 'red' : 'green') : 'gray' }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @if(!isset($duplicates))
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        @elseif(count($duplicates) > 0)
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        @endif
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-{{ isset($duplicates) ? (count($duplicates) > 0 ? 'red' : 'green') : 'gray' }}-600 font-medium">
                        {{ isset($duplicates) ? 'Doublons trouvés' : 'Scan non lancé' }}
                    </p>
                    <p class="text-2xl font-bold text-{{ isset($duplicates) ? (count($duplicates) > 0 ? 'red' : 'green') : 'gray' }}-800">
                        {{ isset($duplicates) ? count($duplicates) : '—' }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Formulaire --}}
        <form method="POST" action="{{ route('households.fingerprint-duplicates.run') }}" id="scan-form">
            @csrf
            <div class="flex flex-col md:flex-row items-end gap-4">
                <div class="flex-1">
                    <label for="threshold" class="block text-sm font-medium text-gray-700 mb-1">
                        Seuil de similarité
                        <span class="text-gray-400 font-normal">(plus le seuil est bas, plus le scan est sensible)</span>
                    </label>
                    <div class="flex items-center gap-3">
                        <input type="range" id="threshold-range" name="threshold"
                               min="0.10" max="0.90" step="0.05"
                               value="{{ $threshold }}"
                               class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600"
                               oninput="document.getElementById('threshold-display').textContent = Math.round(this.value * 100) + '%'">
                        <span id="threshold-display"
                              class="w-16 text-center text-lg font-bold text-blue-700 bg-blue-50 rounded px-2 py-1">
                            {{ round($threshold * 100) }}%
                        </span>
                    </div>
                    <div class="flex justify-between text-xs text-gray-400 mt-1">
                        <span>10% — très sensible</span>
                        <span class="text-blue-600 font-medium">35% recommandé</span>
                        <span>90% — très strict</span>
                    </div>
                </div>

                <button type="submit" id="scan-btn"
                        class="bg-red-600 hover:bg-red-700 text-white px-8 py-3 rounded-lg font-semibold flex items-center gap-2 transition-colors"
                        @if($total < 2) disabled title="Pas assez de ménages avec empreintes" @endif>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Lancer le scan
                </button>
            </div>

            @if($total < 2)
            <p class="text-amber-600 text-sm mt-3 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                Il faut au moins 2 ménages avec des empreintes enregistrées pour lancer le scan.
            </p>
            @elseif($comparisons > 10000)
            <p class="text-amber-600 text-sm mt-3 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                {{ number_format($comparisons, 0, ',', ' ') }} comparaisons — le scan peut prendre plusieurs minutes.
            </p>
            @endif
        </form>
    </div>

    {{-- ── Spinner pendant le chargement ───────────────────────────────────── --}}
    <div id="loading-overlay" class="hidden fixed inset-0 bg-white bg-opacity-75 flex items-center justify-center z-50">
        <div class="text-center">
            <svg class="animate-spin w-16 h-16 text-blue-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <p class="text-gray-700 font-semibold text-lg">Analyse en cours…</p>
            <p class="text-gray-500 text-sm mt-1">{{ number_format($comparisons, 0, ',', ' ') }} comparaisons biométriques</p>
        </div>
    </div>

    {{-- ── Résultats ────────────────────────────────────────────────────────── --}}
    @if(isset($duplicates))

    @if(count($duplicates) === 0)
    {{-- Aucun doublon --}}
    <div class="bg-green-50 border border-green-200 rounded-xl p-8 text-center">
        <svg class="w-16 h-16 text-green-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h3 class="text-xl font-bold text-green-800 mb-1">Aucun doublon détecté</h3>
        <p class="text-green-600">
            {{ number_format($comparisons, 0, ',', ' ') }} comparaisons effectuées —
            seuil&nbsp;: {{ round($threshold * 100) }}%.
        </p>
        @if($dactyMissing)
        <p class="text-amber-600 text-sm mt-2">
            ⚠ Recherche limitée au mode hash d'image (DactyMatch absent).
            Installez la librairie Thales pour activer le matching biométrique complet.
        </p>
        @endif
    </div>

    @else
    {{-- Tableau des doublons --}}
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                          d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                          clip-rule="evenodd"/>
                </svg>
                {{ count($duplicates) }} doublon(s) trouvé(s)
                <span class="text-sm font-normal text-gray-500">
                    — seuil {{ round($threshold * 100) }}% — {{ number_format($comparisons, 0, ',', ' ') }} comparaisons
                </span>
            </h2>
            <button onclick="window.print()"
                    class="text-sm text-gray-600 hover:text-gray-900 flex items-center gap-1 border border-gray-300 px-3 py-1.5 rounded-lg">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Imprimer
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ménage A</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Site A</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ménage B</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Site B</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($duplicates as $index => $dup)
                    @php
                        $pct   = round($dup['score'] * 100);
                        $color = $pct >= 90 ? 'red' : ($pct >= 65 ? 'orange' : 'yellow');
                        $sameSite = $dup['a']->site_id === $dup['b']->site_id;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        {{-- Rang --}}
                        <td class="px-4 py-3 text-gray-400 font-mono">{{ $index + 1 }}</td>

                        {{-- Score --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="bg-{{ $color }}-500 h-2 rounded-full"
                                         style="width:{{ $pct }}%"></div>
                                </div>
                                <span class="font-bold text-{{ $color }}-700">{{ $pct }}%</span>
                            </div>
                        </td>

                        {{-- Type --}}
                        <td class="px-4 py-3">
                            @if($dup['exact'])
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                Identique
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Similaire
                            </span>
                            @endif
                        </td>

                        {{-- Ménage A --}}
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">
                                {{ trim($dup['a']->chef_nom . ' ' . $dup['a']->chef_postnom . ' ' . $dup['a']->chef_prenom) }}
                            </div>
                            <div class="text-xs text-gray-500 font-mono">{{ $dup['a']->numero_menage }}</div>
                            <div class="text-xs text-gray-400">
                                {{ $dup['a']->date_enregistrement ? \Carbon\Carbon::parse($dup['a']->date_enregistrement)->format('d/m/Y') : '—' }}
                            </div>
                        </td>

                        {{-- Site A --}}
                        <td class="px-4 py-3 text-gray-700">
                            {{ $dup['a']->site->nom ?? '—' }}
                        </td>

                        {{-- Ménage B --}}
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">
                                {{ trim($dup['b']->chef_nom . ' ' . $dup['b']->chef_postnom . ' ' . $dup['b']->chef_prenom) }}
                            </div>
                            <div class="text-xs text-gray-500 font-mono">{{ $dup['b']->numero_menage }}</div>
                            <div class="text-xs text-gray-400">
                                {{ $dup['b']->date_enregistrement ? \Carbon\Carbon::parse($dup['b']->date_enregistrement)->format('d/m/Y') : '—' }}
                            </div>
                        </td>

                        {{-- Site B --}}
                        <td class="px-4 py-3 text-gray-700">
                            {{ $dup['b']->site->nom ?? '—' }}
                            @if($sameSite)
                            <span class="ml-1 text-xs text-red-500 font-semibold">(même site)</span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('households.show', $dup['a']->id) }}"
                                   target="_blank"
                                   class="text-blue-600 hover:text-blue-900 text-xs underline whitespace-nowrap">
                                    Voir A
                                </a>
                                <span class="text-gray-300">|</span>
                                <a href="{{ route('households.show', $dup['b']->id) }}"
                                   target="_blank"
                                   class="text-blue-600 hover:text-blue-900 text-xs underline whitespace-nowrap">
                                    Voir B
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Légende --}}
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex flex-wrap gap-4 text-xs text-gray-500">
            <span class="flex items-center gap-1">
                <span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span>
                ≥ 90% — Très forte probabilité de doublon
            </span>
            <span class="flex items-center gap-1">
                <span class="w-3 h-3 rounded-full bg-orange-500 inline-block"></span>
                65–89% — Doublon probable
            </span>
            <span class="flex items-center gap-1">
                <span class="w-3 h-3 rounded-full bg-yellow-500 inline-block"></span>
                {{ round($threshold * 100) }}–64% — Similarité partielle
            </span>
            <span class="ml-auto">
                <strong>Identique</strong> = mêmes données bit-à-bit (hash SHA-256) &nbsp;|&nbsp;
                <strong>Similaire</strong> = matching minuties FMR_ISO
            </span>
        </div>
    </div>
    @endif

    @endif {{-- isset($duplicates) --}}

</div>

<script>
    document.getElementById('scan-form').addEventListener('submit', function () {
        document.getElementById('loading-overlay').classList.remove('hidden');
        document.getElementById('scan-btn').disabled = true;
    });
</script>
@endsection
