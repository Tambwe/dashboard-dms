@extends('layouts.app')

@section('title', 'Import – Cadre de programmation')

@section('content')
<div class="space-y-6">

    {{-- ── En-tête ─────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Import du cadre de programmation</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Charge les indicateurs, activités et sous-activités depuis
                <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">{{ $filePath }}</code>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.programme.import.template') }}"
               class="filter-button flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Télécharger le modèle Excel
            </a>
        </div>
    </div>

    {{-- ── Alertes ─────────────────────────────────────────────────── --}}
    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 flex">
            <svg class="h-5 w-5 text-green-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="ml-3 text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 flex">
            <svg class="h-5 w-5 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="ml-3 text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
        </div>
    @endif

    @if(session('warning'))
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
            <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-2">{{ session('warning') }}</p>
            @if(session('import_errors'))
                <ul class="mt-2 text-sm text-yellow-700 dark:text-yellow-300 list-disc list-inside space-y-1 max-h-60 overflow-y-auto">
                    @foreach(session('import_errors') as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    {{-- ── Statut du fichier ───────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Fichier source</h3>
        <div class="flex items-center gap-3">
            @if($fileExists)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                    <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Fichier détecté
                </span>
                <span class="text-sm text-gray-600 dark:text-gray-400 font-mono">{{ $filePath }}</span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                    <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    Fichier introuvable
                </span>
                <span class="text-sm text-gray-600 dark:text-gray-400 font-mono">{{ $filePath }}</span>
            @endif
        </div>
        @unless($fileExists)
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Placez le fichier <code class="font-mono">activites.xlsx</code> à l'emplacement indiqué, puis relancez l'import.
                Téléchargez le modèle ci-dessus pour respecter la structure attendue.
            </p>
        @endunless
    </div>

    {{-- ── Structure attendue ──────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Structure du fichier Excel</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">

            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <p class="font-semibold text-emerald-600 dark:text-emerald-400 mb-2">Feuille : Indicateurs</p>
                <ul class="space-y-0.5 text-gray-600 dark:text-gray-400">
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">A</span> Indicateur_ID</li>
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">B</span> Code_Indicateur <span class="text-red-500">*</span></li>
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">C</span> Libelle_Indicateur <span class="text-red-500">*</span></li>
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">D</span> Unite_Mesure</li>
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">E</span> Frequence</li>
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">F</span> Responsable</li>
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">G</span> Source_Verification</li>
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">H</span> Actif (Oui/Non)</li>
                </ul>
            </div>

            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <p class="font-semibold text-emerald-600 dark:text-emerald-400 mb-2">Feuille : Activites</p>
                <ul class="space-y-0.5 text-gray-600 dark:text-gray-400">
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">A</span> Activite_ID</li>
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">B</span> Code_Activite <span class="text-red-500">*</span></li>
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">C</span> Libelle_Activite <span class="text-red-500">*</span></li>
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">D</span> Indicateur_ID <span class="text-red-500">*</span></li>
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">E</span> Axe_Programme</li>
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">F</span> Chef_Projet</li>
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">G</span> Statut</li>
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">H</span> Date_Debut_Prevue</li>
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">I</span> Date_Fin_Prevue</li>
                </ul>
            </div>

            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <p class="font-semibold text-emerald-600 dark:text-emerald-400 mb-2">Feuille : Sous_activites</p>
                <ul class="space-y-0.5 text-gray-600 dark:text-gray-400">
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">A</span> Sous_Activite_ID</li>
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">B</span> Code_Sous_Activite <span class="text-red-500">*</span></li>
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">C</span> Libelle_Sous_Activite <span class="text-red-500">*</span></li>
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">D</span> Activite_ID <span class="text-red-500">*</span></li>
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">E</span> Site</li>
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">F</span> Province</li>
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">G</span> Territoire</li>
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">H</span> Zone_Sante</li>
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">I</span> Date_Debut_Prevue</li>
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">J</span> Date_Fin_Prevue</li>
                    <li><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">K</span> Statut</li>
                </ul>
            </div>
        </div>
        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400"><span class="text-red-500">*</span> Champ obligatoire — la ligne sera ignorée si vide. La colonne A (ID) fait office de clé de mise à jour (upsert par Code).</p>
    </div>

    {{-- ── Bouton de lancement ──────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Lancer l'import</h3>
        @if($fileExists)
            <form method="POST" action="{{ route('admin.programme.import.process') }}">
                @csrf
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    L'import lira le fichier <code class="font-mono text-xs">{{ $filePath }}</code>,
                    créera ou mettra à jour les enregistrements (upsert par code) et affichera un récapitulatif.
                </p>
                <button type="submit" class="primary-button">
                    <svg class="w-5 h-5 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    Importer maintenant
                </button>
            </form>
        @else
            <p class="text-sm text-red-600 dark:text-red-400">Impossible de lancer l'import : le fichier <code class="font-mono text-xs">{{ $filePath }}</code> est introuvable.</p>
        @endif
    </div>

    {{-- ── Liens rapides ───────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('admin.programme.indicateurs.index') }}"
           class="block bg-white dark:bg-gray-800 rounded-lg shadow-md p-5 hover:shadow-lg transition-shadow group">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg group-hover:bg-emerald-200 transition-colors">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">Indicateurs</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Consulter la liste</p>
                </div>
            </div>
        </a>
        <a href="{{ route('admin.programme.activites.index') }}"
           class="block bg-white dark:bg-gray-800 rounded-lg shadow-md p-5 hover:shadow-lg transition-shadow group">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg group-hover:bg-emerald-200 transition-colors">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h7" />
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">Activités</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Consulter la liste</p>
                </div>
            </div>
        </a>
        <a href="{{ route('admin.programme.sous-activites.index') }}"
           class="block bg-white dark:bg-gray-800 rounded-lg shadow-md p-5 hover:shadow-lg transition-shadow group">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg group-hover:bg-emerald-200 transition-colors">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h7" />
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">Sous-activités</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Consulter la liste</p>
                </div>
            </div>
        </a>
    </div>

</div>
@endsection
