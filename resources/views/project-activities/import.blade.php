@extends('layouts.app')

@section('title', 'Import Excel des activités projets')
@section('subtitle', 'Téléchargement du template et import des activités réalisées')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    @if(session('success'))
    <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-200">
        {{ session('success') }}
    </div>
    @endif

    @if(session('import_errors'))
    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-900/20">
        <p class="text-sm font-medium text-amber-900 dark:text-amber-200">Certaines lignes n'ont pas pu être importées.</p>
        <div class="mt-2 max-h-64 overflow-y-auto text-sm text-amber-800 dark:text-amber-100 space-y-1">
            @foreach(session('import_errors') as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6 space-y-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">1. Télécharger le template Excel</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Le classeur contient les listes déroulantes en cascade pour les projets, la géographie et le cadre programmatique.
                </p>
            </div>

            <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-4 text-sm text-blue-900 dark:text-blue-100 space-y-2">
                @if($isSuperAdmin)
                    <p>Le template super administrateur inclut la sélection de l'organisation, du cluster et du projet pour chaque ligne.</p>
                    <p>En choisissant une organisation, le template sera limité à ses clusters, projets et sites.</p>
                @else
                    <p>Le template est limité à votre organisation: <strong>{{ $organisationName }}</strong>.</p>
                @endif
                <p>Les cascades disponibles sont: organisation → cluster → projet, province → territoire → zone de santé → site, cluster → indicateur → activité → sous-activité.</p>
            </div>

            @if($isSuperAdmin)
                <form id="template-download-form" class="space-y-3">
                    <div>
                        <label for="organisation_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Organisation (optionnel)
                        </label>
                        <select id="organisation_id"
                                name="organisation_id"
                                class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500">
                            <option value="">-- Toutes les organisations --</option>
                            @foreach($organisations as $org)
                                <option value="{{ $org->id }}">{{ $org->code ? $org->code . ' - ' : '' }}{{ $org->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Laissez vide pour obtenir le template global avec toutes les organisations.</p>
                    </div>
                    <button id="download-template-btn" type="button" data-template-url="{{ route('project-activities-import.template') }}"
                            class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                        Télécharger le template
                    </button>
                </form>
            @else
                <button id="download-template-btn" type="button" data-template-url="{{ route('project-activities-import.template') }}"
                   class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                    Télécharger le template
                </button>
            @endif

            <p id="template-download-status" class="hidden text-sm text-gray-600 dark:text-gray-300"></p>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6 space-y-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">2. Importer les activités réalisées</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Importez un fichier complété à partir du template généré par la plateforme.
                </p>
            </div>

            <form action="{{ route('project-activities-import.process') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label for="fichier_excel" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Fichier Excel
                    </label>
                    <input type="file"
                           id="fichier_excel"
                           name="fichier_excel"
                           accept=".xlsx,.xls"
                           required
                           class="block w-full text-sm text-gray-700 dark:text-gray-300 file:mr-4 file:rounded-lg file:border-0 file:bg-primary-600 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-primary-700">
                </div>

                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30 p-4 text-sm text-gray-600 dark:text-gray-300 space-y-1">
                    <p>Le fichier doit conserver la feuille <strong>IMPORTATION</strong>.</p>
                    <p>Utilisez les listes déroulantes du template pour éviter les erreurs d'association.</p>
                    <p>Pour plusieurs statuts bénéficiaires sur la même activité, créez plusieurs lignes.</p>
                </div>

                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Lancer l'import
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('download-template-btn');
    const statusEl = document.getElementById('template-download-status');
    const organisationSelect = document.getElementById('organisation_id');

    if (!button || !statusEl) {
        return;
    }

    const baseUrl = button.dataset.templateUrl;

    const setStatus = function (message, isError) {
        statusEl.textContent = message;
        statusEl.classList.remove('hidden', 'text-red-600', 'dark:text-red-400', 'text-gray-600', 'dark:text-gray-300');
        if (isError) {
            statusEl.classList.add('text-red-600', 'dark:text-red-400');
        } else {
            statusEl.classList.add('text-gray-600', 'dark:text-gray-300');
        }
    };

    let templateIframe = document.getElementById('template-download-iframe');
    if (!templateIframe) {
        templateIframe = document.createElement('iframe');
        templateIframe.id = 'template-download-iframe';
        templateIframe.className = 'hidden';
        document.body.appendChild(templateIframe);
    }

    button.addEventListener('click', function () {
        button.disabled = true;
        setStatus('Generation du template en cours. Vous pouvez continuer a utiliser la page...', false);

        const params = new URLSearchParams();
        if (organisationSelect && organisationSelect.value) {
            params.set('organisation_id', organisationSelect.value);
        }

        const requestUrl = params.toString() ? `${baseUrl}?${params.toString()}` : baseUrl;
        let completed = false;

        templateIframe.onload = function () {
            if (completed) {
                return;
            }
            completed = true;
            setStatus('Template pret: le telechargement a demarre.', false);
            alert('Le template Excel est pret et le telechargement a commence.');
            button.disabled = false;
        };

        // Fallback: some browsers do not fire onload reliably for attachment downloads.
        setTimeout(function () {
            if (!completed) {
                setStatus('Telechargement lance. Vous pouvez continuer a travailler pendant la generation.', false);
                button.disabled = false;
            }
        }, 4000);

        templateIframe.src = requestUrl;
    });
});
</script>
@endpush