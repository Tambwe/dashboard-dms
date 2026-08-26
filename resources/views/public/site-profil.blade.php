<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil des services du site – DMS CCCM</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <x-vite-manifest-loader :assets="['resources/css/app.css', 'resources/js/app.js']" />
    <x-sweetalert-flash />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <style>
        .section-tab { scroll-margin-top: 80px; }
        .tab-btn.active { @apply bg-primary-600 text-white; }

        @page { size: A4 portrait; margin: 1.5cm; }
        @media print {
            body { background: #fff !important; font-size: 10px !important; }
            nav, footer, #site-selector-card, .sticky { display: none !important; }
            main { padding-top: 0 !important; padding-bottom: 0 !important; }
            .max-w-7xl { max-width: 100% !important; }
            .rounded-2xl, .rounded-xl { border-radius: 0 !important; }
            .shadow, .shadow-lg { box-shadow: none !important; }
            #site-map { height: 280px !important; }
            a { text-decoration: none !important; color: inherit !important; }
            .section-tab { page-break-inside: avoid; }
            .section-tab:first-of-type { page-break-before: avoid; }
            h2, h3 { font-size: 12px !important; }
            p, li, td, th, span, div { font-size: 10px !important; }
        }
    </style>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900 transition-colors duration-300">

{{-- ══════════ NAVBAR ══════════ --}}
<nav class="fixed top-0 left-0 right-0 z-50 bg-white/90 dark:bg-gray-800/90 backdrop-blur-md border-b border-gray-200 dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <a href="{{ route('home') }}" class="flex items-center space-x-3">
                <img src="{{ asset('images/logo-dms-cccm.avif') }}" alt="Logo DMS CCCM" class="h-10 w-auto">
                <div>
                    <span class="text-lg font-bold text-gray-900 dark:text-white">DMS CCCM</span>
                </div>
            </a>
            <div class="hidden md:flex items-center space-x-6">
                <a href="{{ route('home') }}" class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary-600">Accueil</a>
                <a href="{{ url('/about') }}" class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary-600">A propos</a>
                <a href="{{ url('/profil-site') }}" class="text-sm font-medium text-primary-600 dark:text-primary-400 border-b-2 border-primary-600">Profil des sites</a>
                <a href="{{ url('/cartographie') }}" class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary-600">Cartographie</a>
                <a href="{{ route('dashboard') }}" class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary-600">Tableau de bord</a>
            </div>
            <div class="flex items-center space-x-3">
                <button id="btn-print-profile" type="button" class="px-3 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm" title="Imprimer le profil du site">
                    🖨️ Imprimer
                </button>
                <button onclick="toggleDarkMode()" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                </button>
                <a href="{{ route('login') }}" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                    Se connecter
                </a>
            </div>
        </div>
    </div>
</nav>

<main class="pt-20 pb-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ══════════ SÉLECTEUR ══════════ --}}
        <div id="site-selector-card" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">Profil des sites d'accueil</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Sélectionnez un site pour consulter les données collectées sur ses services</p>

            <form method="GET" action="#" id="site-selector-form" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                {{-- Province --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Province</label>
                    <select id="sel_province" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500">
                        <option value="">— Toutes les provinces —</option>
                        @foreach($provinces as $p)
                            <option value="{{ $p->id }}" {{ isset($site) && $site->commune?->territoire?->province_id == $p->id ? 'selected' : '' }}>
                                {{ $p->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                {{-- Territoire --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Territoire</label>
                    <select id="sel_territoire" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500">
                        <option value="">— Sélectionner la province —</option>
                    </select>
                </div>
                {{-- Zone de sante --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Zone de santé</label>
                    <select id="sel_commune" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500">
                        <option value="">— Sélectionner le territoire —</option>
                    </select>
                </div>
                {{-- Site --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Site</label>
                    <select id="sel_site" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500">
                        <option value="">— Sélectionner la zone de santé —</option>
                    </select>
                </div>
                {{-- Bouton --}}
                <div>
                    <button type="submit" id="btn_voir_profil" disabled
                        class="w-full px-4 py-2.5 bg-primary-600 hover:bg-primary-700 disabled:opacity-40 disabled:cursor-not-allowed text-white font-semibold rounded-lg transition-colors shadow">
                        Voir le profil
                    </button>
                </div>
            </form>
        </div>

        @if(!isset($site))
        {{-- ══════════ ÉTAT VIDE ══════════ --}}
        <div class="rounded-2xl bg-white dark:bg-gray-800 border-2 border-dashed border-gray-200 dark:border-gray-700 p-16 text-center">
            <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <h3 class="text-lg font-semibold text-gray-500 dark:text-gray-400">Sélectionnez un site pour voir son profil</h3>
            <p class="text-sm text-gray-400 mt-1">Utilisez les filtres ci-dessus pour naviguer par province, territoire, zone de santé et site.</p>
        </div>

        @else
        {{-- ══════════ EN-TÊTE DU SITE ══════════ --}}
        <div class="bg-gradient-to-r from-primary-600 to-primary-800 dark:from-primary-800 dark:to-primary-950 rounded-2xl shadow-lg p-6 mb-6 text-white">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-primary-200 text-sm mb-1">
                        <span>{{ $site->commune?->territoire?->province?->name ?? '—' }}</span>
                        <span>›</span>
                        <span>{{ $site->commune?->territoire?->name ?? '—' }}</span>
                        @if($site->commune)
                        <span>›</span>
                        <span>{{ $site->commune->nom }}</span>
                        @endif
                    </div>
                    <h2 class="text-3xl font-bold">{{ $site->nom }}</h2>
                    <div class="flex flex-wrap gap-3 mt-2 text-sm">
                        @if($site->code_site)
                        <span class="inline-flex items-center gap-1 bg-white/20 px-2.5 py-0.5 rounded-full">
                            📍 {{ $site->code_site }}
                        </span>
                        @endif
                        @if($site->typeSite)
                        <span class="inline-flex items-center gap-1 bg-white/20 px-2.5 py-0.5 rounded-full">
                            🏕️ {{ $site->typeSite->name }}
                        </span>
                        @endif
                        @if($site->organisation)
                        <span class="inline-flex items-center gap-1 bg-white/20 px-2.5 py-0.5 rounded-full">
                            🏢 {{ $site->organisation->name }}
                        </span>
                        @endif
                    </div>
                </div>
                @if($site->latitude && $site->longitude)
                <div class="text-right text-sm text-primary-200">
                    <p>GPS</p>
                    <p class="font-mono text-white">{{ $site->latitude }}, {{ $site->longitude }}</p>
                </div>
                @endif
            </div>
        </div>

        {{-- ══════════ CARTE ══════════ --}}
        @if($site->latitude && $site->longitude)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-4 mb-6">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Localisation du site
            </h3>
            <div id="site-map" style="height: 320px; border-radius: 0.75rem; z-index: 1;" class="w-full border border-gray-200 dark:border-gray-700"></div>
            <p class="text-xs text-gray-400 mt-2 text-right font-mono">{{ $site->latitude }}, {{ $site->longitude }}</p>
        </div>
        @endif

        @include('public.partials.service-profile')

        @if(false)
        @if(!$ossatReport)
        {{-- ══════════ PAS DE RAPPORT ══════════ --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-10 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="text-lg font-semibold text-gray-600 dark:text-gray-300">Aucun rapport OSSAT disponible</h3>
            <p class="text-sm text-gray-400 mt-1">Les données de ce site n'ont pas encore été collectées.</p>
        </div>

        @else
        @php $r = $ossatReport; @endphp

        {{-- ══════════ BANDEAU META RAPPORT ══════════ --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow px-5 py-4 mb-6 flex flex-wrap gap-4 text-sm">
            <div>
                <span class="text-gray-400">Date de collecte</span>
                <span class="ml-2 font-semibold text-gray-800 dark:text-white">{{ $r->today?->format('d/m/Y') ?? '—' }}</span>
            </div>
            <div class="border-l border-gray-200 dark:border-gray-600 pl-4">
                <span class="text-gray-400">Enquêteur</span>
                <span class="ml-2 font-semibold text-gray-800 dark:text-white">{{ $r->enumerator_name ?? '—' }}</span>
            </div>
            @if($r->agence_enqueteur)
            <div class="border-l border-gray-200 dark:border-gray-600 pl-4">
                <span class="text-gray-400">Agence</span>
                <span class="ml-2 font-semibold text-gray-800 dark:text-white">{{ $r->agence_enqueteur }}</span>
            </div>
            @endif
            <div class="border-l border-gray-200 dark:border-gray-600 pl-4">
                <span class="text-gray-400">Statut rapport</span>
                @php
                    $svMap = ['brouillon'=>['bg-gray-100 text-gray-700','Brouillon'],'soumis'=>['bg-yellow-100 text-yellow-800','Soumis'],'valide'=>['bg-green-100 text-green-700','Validé'],'rejete'=>['bg-red-100 text-red-700','Rejeté']];
                    [$svCls,$svLbl] = $svMap[$r->statut_validation ?? 'brouillon'] ?? ['bg-gray-100 text-gray-700','—'];
                @endphp
                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $svCls }}">{{ $svLbl }}</span>
            </div>
            <div class="border-l border-gray-200 dark:border-gray-600 pl-4">
                <span class="text-gray-400">Statut site</span>
                <span class="ml-2 font-semibold text-gray-800 dark:text-white">{{ $r->statut ?? '—' }}</span>
            </div>
        </div>

        {{-- ══════════ NAVIGATION RAPIDE ══════════ --}}
        <div class="flex flex-wrap gap-2 mb-6 sticky top-16 z-30 bg-gray-50 dark:bg-gray-900 py-2 -mx-4 px-4">
            @foreach([
                ['pop','👥 Population'],
                ['gestion','🏢 Gestion'],
                ['mouvements','📊 Mouvements'],
                ['abri','🏕️ Abri'],
                ['infra','🛤️ Infrastructure'],
                ['wash','💧 WASH'],
                ['sante','🏥 Santé'],
                ['securite','🛡️ Sécurité'],
                ['education','📚 Éducation'],
                ['subsistance','🛒 Subsistance'],
                ['qualite','✅ Qualité services'],
                ['besoins','⚠️ Besoins'],
                ['partenaires','🤝 Partenaires'],
            ] as [$id,$label])
            <a href="#sec_{{ $id }}" class="px-3 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 text-xs font-medium text-gray-600 dark:text-gray-300 rounded-full hover:bg-primary-50 hover:border-primary-300 hover:text-primary-700 transition-colors shadow-sm whitespace-nowrap">
                {{ $label }}
            </a>
            @endforeach
        </div>

        {{-- ══════════ SECTION POPULATION ══════════ --}}
        <div id="sec_pop" class="section-tab bg-white dark:bg-gray-800 rounded-2xl shadow p-6 mb-6">
            <div class="flex items-center gap-3 mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="text-2xl">👥</span> Population
                </h3>
                @if($populationMouvement ?? null)
                    <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700">
                        📊 Source : Mouvements de population · {{ ($populationMouvement->date_mouvement)?->format('d/m/Y') ?? '—' }}
                    </span>
                @elseif($ossatReport)
                    <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-700">
                        📋 Source : OSSAT · {{ $ossatReport->today?->format('d/m/Y') ?? '—' }}
                    </span>
                @endif
            </div>
            @php
                $pm = $populationMouvement ?? null;
                $popMenages   = $pm ? ($pm->menages   ?? 0) : ($r->nb_familles ?? 0);
                $popIndividus = $pm ? ($pm->individus ?? 0) : ($r->nb_individus ?? 0);
                $stats = [
                    ['Ménages',          number_format($popMenages),   'blue'],
                    ['Individus',        number_format($popIndividus), 'blue'],
                    ['Ménages / femme chef', number_format($r->menages_femme_chef ?? 0), 'pink'],
                    ['Enfants non accomp.', number_format($r->enfants_non_accompagnes ?? 0), 'orange'],
                ];
                $cls = ['blue'=>'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300','pink'=>'bg-pink-50 dark:bg-pink-900/20 text-pink-700 dark:text-pink-300','orange'=>'bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300'];
                // Tranches d'âge selon la source
                $pyramide = $pm ? [
                    ['0-5 ans',   $pm->h_0_5   ?? 0, $pm->f_0_5   ?? 0],
                    ['6-17 ans',  $pm->h_6_17  ?? 0, $pm->f_6_17  ?? 0],
                    ['18-59 ans', $pm->h_18_59 ?? 0, $pm->f_18_59 ?? 0],
                    ['60+ ans',   $pm->h_60_plus ?? 0, $pm->f_60_plus ?? 0],
                ] : [
                    ['0-4 ans',   $r->h_0_4    ?? 0, $r->f_0_4    ?? 0],
                    ['5-17 ans',  $r->h_5_17   ?? 0, $r->f_5_17   ?? 0],
                    ['18-59 ans', $r->h_18_59  ?? 0, $r->f_18_59  ?? 0],
                    ['60+ ans',   $r->h_60plus ?? 0, $r->f_60plus ?? 0],
                ];
            @endphp
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
                @foreach($stats as [$lbl,$val,$color])
                <div class="rounded-xl p-4 {{ $cls[$color] }} text-center">
                    <div class="text-2xl font-bold">{{ $val }}</div>
                    <div class="text-xs mt-1 font-medium">{{ $lbl }}</div>
                </div>
                @endforeach
            </div>
            {{-- Pyramide --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-center border-collapse">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700">
                            <th class="px-3 py-2 text-left text-gray-500 dark:text-gray-400 font-medium">Tranche d'âge</th>
                            <th class="px-3 py-2 text-blue-600 dark:text-blue-400 font-medium">♂ Hommes</th>
                            <th class="px-3 py-2 text-pink-600 dark:text-pink-400 font-medium">♀ Femmes</th>
                            <th class="px-3 py-2 text-gray-600 dark:text-gray-300 font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($pyramide as [$tranche,$hv,$fv])
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-3 py-2 text-left text-gray-700 dark:text-gray-300">{{ $tranche }}</td>
                            <td class="px-3 py-2 text-blue-700 dark:text-blue-300 font-mono">{{ number_format($hv) }}</td>
                            <td class="px-3 py-2 text-pink-700 dark:text-pink-300 font-mono">{{ number_format($fv) }}</td>
                            <td class="px-3 py-2 font-semibold text-gray-800 dark:text-white font-mono">{{ number_format($hv + $fv) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{-- Vulnérabilités --}}
            @if(($r->handicap_physique ?? 0) || ($r->handicap_mental ?? 0) || ($r->maladies_chroniques_nb ?? 0) || ($r->personnes_agees_isolees ?? 0) || ($r->menages_enfant_chef ?? 0))
            <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                @foreach([
                    ['Handicap physique',$r->handicap_physique ?? 0,'purple'],
                    ['Handicap mental',$r->handicap_mental ?? 0,'purple'],
                    ['Maladies chroniques',$r->maladies_chroniques_nb ?? 0,'red'],
                    ['Pers. âgées isolées',$r->personnes_agees_isolees ?? 0,'amber'],
                    ['Ménages / enfant chef',$r->menages_enfant_chef ?? 0,'orange'],
                ] as [$lbl,$val,$color])
                <div class="rounded-lg p-3 bg-{{ $color }}-50 dark:bg-{{ $color }}-900/20 text-{{ $color }}-700 dark:text-{{ $color }}-300 text-center text-xs">
                    <div class="text-xl font-bold">{{ number_format($val) }}</div>
                    <div class="mt-0.5">{{ $lbl }}</div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- ══════════ GESTION DU SITE ══════════ --}}
        <div id="sec_gestion" class="section-tab bg-white dark:bg-gray-800 rounded-2xl shadow p-6 mb-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="text-2xl">🏢</span> Gestion du site
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Gestionnaire --}}
                <div>
                    <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Gestionnaire</h4>
                    @include('public.partials.dl', ['rows' => [
                        ['Agence de gestion', $r->agence_gestion ? ($r->agence_gestion_nom ?? 'Oui') : 'Non'],
                        ['Nom gestionnaire', $r->gestionnaire_nom ?? '—'],
                        ['Sexe', $r->gestionnaire_sexe ?? '—'],
                        ['Téléphone', $r->gestionnaire_telephone ?? '—'],
                        ['Email', $r->gestionnaire_email ?? '—'],
                    ]])
                </div>
                {{-- Administration --}}
                <div>
                    <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Administration</h4>
                    @include('public.partials.dl', ['rows' => [
                        ['Agence admin', $r->agence_admin ? ($r->agence_admin_nom ?? 'Oui') : 'Non'],
                        ['Nom admin', $r->admin_nom ?? '—'],
                        ['Sexe', $r->admin_sexe ?? '—'],
                        ['Téléphone', $r->admin_telephone ?? '—'],
                        ['Coordinateur/agence', $r->agence_coord ? ($r->agence_coord_nom ?? 'Oui') : 'Non'],
                    ]])
                </div>
                {{-- Comités --}}
                <div>
                    <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Comités & coordination</h4>
                    @include('public.partials.dl', ['rows' => [
                        ['Comités présents', $r->presence_comite ? 'Oui' : 'Non'],
                        ['Types de comités', !empty($r->comites) ? implode(', ', (array)$r->comites) : '—'],
                        ['Comités élus', $r->comites_elus ? 'Oui' : 'Non'],
                        ['Comités fonctionnels', $r->nb_comites_fonctionnels ?? '—'],
                        ['Réunions coordination', $r->reunions_coordination ? 'Oui' : 'Non'],
                        ['Périodicité réunions', $r->periodicite_reunions ?? '—'],
                    ]])
                </div>
                {{-- Équipe mobile --}}
                <div>
                    <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Plaintes & équipe mobile</h4>
                    @include('public.partials.dl', ['rows' => [
                        ['Bureau dédié', $r->bureau_dedie ? 'Oui' : 'Non'],
                        ['Nb hommes staff', $r->nb_hommes_staff ?? '—'],
                        ['Nb femmes staff', $r->nb_femmes_staff ?? '—'],
                        ['Équipe mobile soutien', $r->equipe_mobile_soutien ? 'Oui' : 'Non'],
                        ['MGP (mécanisme plainte)', $r->mgp ? 'Oui' : 'Non'],
                        ['Feedback CCI', $r->cci ? 'Oui' : 'Non'],
                    ]])
                </div>
            </div>
        </div>

        {{-- ══════════ MOUVEMENTS ══════════ --}}
        <div id="sec_mouvements" class="section-tab bg-white dark:bg-gray-800 rounded-2xl shadow p-6 mb-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="text-2xl">📊</span> Mouvements de PDI & Capacité
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    @include('public.partials.dl', ['rows' => [
                        ['Nouvelles arrivées', $r->pdi_nouvelles_arrivees ? 'Oui' : 'Non'],
                        ['Quantité', $r->pdi_nouvelles_qtite ?? '—'],
                        ['Retours', $r->pdi_retours ? 'Oui' : 'Non'],
                        ['Quantité retours', $r->pdi_retours_qtite ?? '—'],
                        ['Raisons retours', !empty($r->raisons_retours) ? implode(', ', (array)$r->raisons_retours) : '—'],
                    ]])
                </div>
                <div>
                    @include('public.partials.dl', ['rows' => [
                        ['Capacité accueil', $r->capacite_accueil ? number_format($r->capacite_accueil).' familles' : '—'],
                        ['Familles en attente', $r->familles_attente ? number_format($r->familles_attente) : '0'],
                        ['Réduction prévue', $r->reduction_prevue ? 'Oui' : 'Non'],
                        ['Nouveau site', $r->nouveau_site ? 'Oui' : 'Non'],
                    ]])
                </div>
            </div>
        </div>

        {{-- ══════════ ABRI ══════════ --}}
        <div id="sec_abri" class="section-tab bg-white dark:bg-gray-800 rounded-2xl shadow p-6 mb-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="text-2xl">🏕️</span> Abri & AME
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Types d'abri</h4>
                    @php $typesAbri = (array)($r->types_abri ?? []); @endphp
                    @if(!empty($typesAbri))
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($typesAbri as $type)
                        <span class="px-2.5 py-1 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 text-xs rounded-full border border-indigo-200 dark:border-indigo-700">{{ $type }}</span>
                        @endforeach
                    </div>
                    @else <p class="text-sm text-gray-400">—</p> @endif
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Besoins AME prioritaires</h4>
                    @php $ame = (array)($r->ame_prioritaires ?? []); @endphp
                    @if(!empty($ame))
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($ame as $item)
                        <span class="px-2.5 py-1 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 text-xs rounded-full border border-amber-200">{{ $item }}</span>
                        @endforeach
                    </div>
                    @else <p class="text-sm text-gray-400">—</p> @endif
                </div>
            </div>
            {{-- Tableau abris --}}
            @php
                $abrisTypes = [
                    ['Tente bâche','bache_installees','bache_occupees','bache_maintenance','bache_remplacement'],
                    ['Matériaux','materiaux_installes','materiaux_occupes','materiaux_maintenance','materiaux_remplacement'],
                    ['Planches','planches_installes','planches_occupees','planches_maintenance','planches_remplacement'],
                    ['Feuilles (RHU)','feuilles_installees','feuilles_occupees','feuilles_maintenance','feuilles_remplacement'],
                    ['Construites','construites_nb',null,'construites_maintenance',null],
                    ['Fortune','fortune_installees','fortune_occupees','fortune_maintenance','fortune_remplacement'],
                ];
                $hasAbriData = false;
                foreach ($abrisTypes as $a) { if ($r->{$a[1]} ?? null) { $hasAbriData = true; break; } }
            @endphp
            @if($hasAbriData)
            <div class="mt-5 overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                            <th class="px-3 py-2 text-left">Type</th>
                            <th class="px-3 py-2 text-center">Installées</th>
                            <th class="px-3 py-2 text-center">Occupées</th>
                            <th class="px-3 py-2 text-center">Maintenance</th>
                            <th class="px-3 py-2 text-center">Remplacement</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($abrisTypes as [$nom,$i,$o,$m,$remp])
                        @if($r->$i ?? null)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="px-3 py-2 font-medium text-gray-700 dark:text-gray-300">{{ $nom }}</td>
                            <td class="px-3 py-2 text-center font-mono text-gray-800 dark:text-gray-200">{{ $r->$i ?? '—' }}</td>
                            <td class="px-3 py-2 text-center font-mono text-gray-800 dark:text-gray-200">{{ $o ? ($r->$o ?? '—') : '—' }}</td>
                            <td class="px-3 py-2 text-center font-mono text-gray-800 dark:text-gray-200">{{ $r->$m ?? '—' }}</td>
                            <td class="px-3 py-2 text-center font-mono text-gray-800 dark:text-gray-200">{{ $remp ? ($r->$remp ?? '—') : '—' }}</td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- ══════════ INFRASTRUCTURE ══════════ --}}
        <div id="sec_infra" class="section-tab bg-white dark:bg-gray-800 rounded-2xl shadow p-6 mb-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="text-2xl">🛤️</span> Infrastructure & Éclairage
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">État des infrastructures</h4>
                    @include('public.partials.dl', ['rows' => [
                        ['Parcelles', $r->etat_parcelles ?? '—'],
                        ['Routes', $r->etat_routes ?? '—'],
                        ['Canaux', $r->etat_canaux ?? '—'],
                        ['Risque inondation', $r->risque_inondation ?? '—'],
                        ['Nb incendies', $r->nb_incendies ?? '0'],
                        ['Mesures incendie', $r->mesures_incendie ? 'Oui' : 'Non'],
                    ]])
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Éclairage & énergie</h4>
                    @include('public.partials.dl', ['rows' => [
                        ['Éclairage existant', $r->eclairage_existant ? 'Oui' : 'Non'],
                        ['Sources électricité', !empty($r->sources_electricite) ? implode(', ', (array)$r->sources_electricite) : '—'],
                    ]])
                </div>
            </div>
        </div>

        {{-- ══════════ WASH ══════════ --}}
        <div id="sec_wash" class="section-tab bg-white dark:bg-gray-800 rounded-2xl shadow p-6 mb-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="text-2xl">💧</span> WASH
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Eau</h4>
                    @include('public.partials.dl', ['rows' => [
                        ['Litres/pers./jour', ($r->litres_eau_jour ?? null) !== null ? $r->litres_eau_jour.'L' : '—'],
                        ['Sources d\'eau', !empty($r->sources_eau) ? implode(', ', (array)$r->sources_eau) : '—'],
                        ['Eau potable (qualité)', $r->qualite_eau ? 'Oui' : 'Non'],
                        ['Jours sans eau', $r->jours_sans_eau ?? '0'],
                        ['Inondations (6 mois)', $r->inondations_6mois ? 'Oui' : 'Non'],
                    ]])
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Assainissement</h4>
                    @include('public.partials.dl', ['rows' => [
                        ['Défécation à l\'air libre', $r->defecation_plein_air ? 'Oui' : 'Non'],
                        ['Savon disponible', $r->savon_disponible ? 'Oui' : 'Non'],
                        ['Types latrines', !empty($r->types_latrines) ? implode(', ', (array)$r->types_latrines) : '—'],
                        ['Nb latrines', $r->nb_latrines ?? '—'],
                        ['Latrines vidangées', $r->latrines_vidangees ? 'Oui' : 'Non'],
                        ['Date vidange', $r->date_derniere_vidange?->format('d/m/Y') ?? '—'],
                        ['Éclairage latrines', $r->eclairage_latrines ? 'Oui' : 'Non'],
                        ['Douches séparées', $r->douches_separees ? 'Oui' : 'Non'],
                        ['Nb douches', $r->nb_douches ?? '—'],
                        ['WASH adapté handicapés', $r->wash_adapte_handicapes ? 'Oui' : 'Non'],
                        ['Élimination déchets', $r->methode_elimination_dechets ?? '—'],
                    ]])
                </div>
            </div>
        </div>

        {{-- ══════════ SANTÉ ══════════ --}}
        <div id="sec_sante" class="section-tab bg-white dark:bg-gray-800 rounded-2xl shadow p-6 mb-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="text-2xl">🏥</span> Santé
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    @include('public.partials.dl', ['rows' => [
                        ['Problèmes de santé', !empty($r->problemes_sante) ? implode(', ', (array)$r->problemes_sante) : '—'],
                        ['Prestataire fonctionnel', $r->soin_sante_fonctionnel ? 'Oui' : 'Non'],
                        ['Soins à l\'intérieur du site', $r->soin_sante_interieur ? 'Oui' : 'Non'],
                        ['Distance prestataire', $r->distance_soin_sante ? $r->distance_soin_sante.' km' : '—'],
                    ]])
                </div>
                <div>
                    @include('public.partials.dl', ['rows' => [
                        ['Services urgences', $r->services_urgences ? 'Oui' : 'Non'],
                        ['Services chirurgicaux', $r->services_chirurgicaux ? 'Oui' : 'Non'],
                        ['Services pédiatriques', $r->services_pediatriques ? 'Oui' : 'Non'],
                        ['Services prénataux', $r->services_prenataux ? 'Oui' : 'Non'],
                        ['Ambulance', $r->ambulance ? 'Oui' : 'Non'],
                        ['Enfants non vaccinés', $r->enfants_non_vaccines ? 'Oui' : 'Non'],
                        ['Problèmes accès santé', !empty($r->problemes_acces_sante) ? implode(', ', (array)$r->problemes_acces_sante) : '—'],
                    ]])
                </div>
                @if($r->repas_par_jour || !empty($r->defis_alimentation))
                <div class="md:col-span-2">
                    <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Alimentation</h4>
                    @include('public.partials.dl', ['rows' => [
                        ['Repas/jour', $r->repas_par_jour ?? '—'],
                        ['Défis alimentation', !empty($r->defis_alimentation) ? implode(', ', (array)$r->defis_alimentation) : '—'],
                        ['Régularité aide alimentaire', $r->regularite_assistance_alimentaire ?? '—'],
                        ['Stockage magasin', $r->stockage_magasin ? 'Oui' : 'Non'],
                    ]])
                </div>
                @endif
            </div>
        </div>

        {{-- ══════════ SÉCURITÉ ══════════ --}}
        <div id="sec_securite" class="section-tab bg-white dark:bg-gray-800 rounded-2xl shadow p-6 mb-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="text-2xl">🛡️</span> Sécurité & Protection
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    @include('public.partials.dl', ['rows' => [
                        ['Incidents sécuritaires', $r->incidents_securitaires ?? '—'],
                        ['Nature incident', $r->nature_incident ?? '—'],
                        ['Acteurs incidents', !empty($r->acteurs_incidents) ? implode(', ', (array)$r->acteurs_incidents) : '—'],
                        ['Sentiment de sécurité', $r->sentiment_securite ? 'Oui' : 'Non'],
                        ['Menaces sur le site', !empty($r->menaces_site) ? implode(', ', (array)$r->menaces_site) : '—'],
                    ]])
                </div>
                <div>
                    @include('public.partials.dl', ['rows' => [
                        ['Restrictions mouvement', $r->restrictions_mouvement ? 'Oui' : 'Non'],
                        ['Types restrictions', !empty($r->types_restrictions) ? implode(', ', (array)$r->types_restrictions) : '—'],
                        ['Zones dangereuses (femmes)', !empty($r->zones_dangereuses_femmes) ? implode(', ', (array)$r->zones_dangereuses_femmes) : '—'],
                        ['Zones dangereuses (hommes)', !empty($r->zones_dangereuses_hommes) ? implode(', ', (array)$r->zones_dangereuses_hommes) : '—'],
                        ['Familles sans documents', number_format($r->familles_sans_documents ?? 0)],
                        ['Distance tribunaux', $r->distance_tribunaux ? $r->distance_tribunaux.' km' : '—'],
                        ['Accès aux tribunaux', $r->acces_tribunaux ? 'Oui' : 'Non'],
                        ['Services handicapés', $r->services_handicapes ? 'Oui' : 'Non'],
                        ['Support psy', !empty($r->types_support_psy) ? implode(', ', (array)$r->types_support_psy) : '—'],
                    ]])
                </div>
            </div>
        </div>

        {{-- ══════════ ÉDUCATION ══════════ --}}
        <div id="sec_education" class="section-tab bg-white dark:bg-gray-800 rounded-2xl shadow p-6 mb-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="text-2xl">📚</span> Éducation
            </h3>
            @include('public.partials.dl', ['rows' => [
                ['École primaire (intérieur)', $r->ecole_primaire_presente ? 'Oui' : 'Non'],
                ['Distance école primaire', $r->distance_ecole_primaire ? $r->distance_ecole_primaire.' km' : '—'],
                ['École secondaire (intérieur)', $r->ecole_secondaire_presente ? 'Oui' : 'Non'],
                ['Distance école secondaire', $r->distance_ecole_secondaire ? $r->distance_ecole_secondaire.' km' : '—'],
                ['Enfants scolarisés', number_format($r->nb_enfants_scolarises ?? 0)],
                ['Obstacles à l\'éducation', !empty($r->obstacles_education) ? implode(', ', (array)$r->obstacles_education) : '—'],
                ['Éducation informelle', $r->education_informelle ? 'Oui' : 'Non'],
                ['Enfants (éducation informelle)', number_format($r->nb_enfants_education_informelle ?? 0)],
            ]])
        </div>

        {{-- ══════════ SUBSISTANCE ══════════ --}}
        <div id="sec_subsistance" class="section-tab bg-white dark:bg-gray-800 rounded-2xl shadow p-6 mb-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="text-2xl">🛒</span> Subsistance & Moyens de vie
            </h3>
            @include('public.partials.dl', ['rows' => [
                ['Marché intérieur', $r->marche_interieur ? 'Oui' : 'Non'],
                ['Distance marché', $r->distance_marche ? $r->distance_marche.' km' : '—'],
                ['Articles non disponibles', !empty($r->articles_non_disponibles) ? implode(', ', (array)$r->articles_non_disponibles) : '—'],
                ['Sources subsistance', !empty($r->sources_subsistance) ? implode(', ', (array)$r->sources_subsistance) : '—'],
                ['Familles avec revenu', number_format($r->nb_familles_avec_revenu ?? 0)],
                ['Jeunes en activité', number_format($r->nb_jeunes_travaillant ?? 0)],
                ['Enclos bétail', $r->enclos_betail ? 'Oui' : 'Non'],
            ]])
        </div>

        {{-- ══════════ ÉVALUATION QUALITÉ DES SERVICES ══════════ --}}
        @php
        /*
         * ─── Scores qualité – standards internationaux ───
         * Sphere 2018 · UNHCR Site Planning · INEE Minimum Standards · WHO · HTF
         */
        $ws2 = function(array $items) {
            $tw = 0; $ts = 0;
            foreach ($items as [$s, $w]) { if ($s !== null) { $ts += $s * $w; $tw += $w; } }
            return $tw > 0 ? round($ts / $tw) : null;
        };
        $etatSc = function($v) {
            if ($v === null) return null;
            $v = mb_strtolower(trim((string)$v));
            if (in_array($v,['bon','bonne','bons','bonnes'])) return 100;
            if (in_array($v,['passable','moyen','moyenne'])) return 55;
            if (in_array($v,['mauvais','mauvaise','dégradé'])) return 15;
            return null;
        };
        $distSc = function($km,$s1,$s2,$s3) {
            if ($km === null) return null;
            $km = (float)$km;
            if ($km <= $s1) return 100;
            if ($km <= $s2) return 65;
            if ($km <= $s3) return 30;
            return 5;
        };

        // WASH (Sphere 2018)
        $qWash = $ws2([
            [(function() use ($r){ $l=$r->litres_eau_jour??null; if($l===null)return null; $l=(float)$l; if($l>=20)return 100; if($l>=15)return 75; if($l>=7)return 40; return 10; })(), 35],
            [($r->qualite_eau===true?100:($r->qualite_eau===false?0:null)), 25],
            [($r->defecation_plein_air===false?100:($r->defecation_plein_air===true?0:null)), 20],
            [($r->savon_disponible===true?100:($r->savon_disponible===false?0:null)), 12],
            [($r->wash_adapte_handicapes===true?100:($r->wash_adapte_handicapes===false?20:null)), 5],
            [(($r->nb_latrines??0)>0?100:0), 3],
        ]);
        // Santé (Sphere + UNHCR)
        $qSante = $ws2([
            [($r->soin_sante_fonctionnel===true?100:($r->soin_sante_fonctionnel===false?0:null)), 30],
            [($r->soin_sante_interieur===true?100:($r->soin_sante_interieur===false?30:null)), 20],
            [$distSc($r->distance_soin_sante,2,5,10), 20],
            [($r->enfants_non_vaccines===false?100:($r->enfants_non_vaccines===true?0:null)), 15],
            [($r->services_urgences===true?100:($r->services_urgences===false?10:null)), 10],
            [($r->ambulance===true?100:($r->ambulance===false?20:null)), 5],
        ]);
        // Éducation (INEE)
        $totEnf = $pm ? (int)($pm->h_6_17??0)+(int)($pm->f_6_17??0) : (int)($r->h_5_17??0)+(int)($r->f_5_17??0);
        $tScol2 = null;
        if ($totEnf>0 && ($r->nb_enfants_scolarises??null)!==null) {
            $tx = min(100,round(($r->nb_enfants_scolarises/$totEnf)*100));
            $tScol2 = $tx>=80?100:($tx>=60?70:($tx>=40?40:15));
        }
        $qEduc = $ws2([
            [($r->ecole_primaire_presente===true?100:($r->ecole_primaire_presente===false?5:null)), 35],
            [$distSc($r->distance_ecole_primaire,1,3,7), 20],
            [($r->ecole_secondaire_presente===true?100:($r->ecole_secondaire_presente===false?5:null)), 20],
            [$tScol2, 15],
            [($r->education_informelle===true?70:($r->education_informelle===false?20:null)), 10],
        ]);
        // Infrastructure (Sphere + HTF)
        $riskSc = null;
        if ($r->risque_inondation!==null) {
            $rv = mb_strtolower(trim((string)$r->risque_inondation));
            if (in_array($rv,['aucun','non','faible','low','none'])) $riskSc=100;
            elseif (in_array($rv,['moyen','modéré','moderate','medium'])) $riskSc=45;
            elseif (in_array($rv,['élevé','haut','high','fort','severe'])) $riskSc=5;
        }
        $qInfra = $ws2([
            [$etatSc($r->etat_routes),   30],
            [$etatSc($r->etat_parcelles),25],
            [$etatSc($r->etat_canaux),   15],
            [$riskSc,                    20],
            [($r->mesures_incendie===true?100:($r->mesures_incendie===false?0:null)), 10],
        ]);
        // Global
        $domQ = array_filter([$qWash,$qSante,$qEduc,$qInfra], fn($v)=>$v!==null);
        $qGlobal = count($domQ)>0 ? round(array_sum($domQ)/count($domQ)) : null;

        $qlabel = function($s) {
            if ($s===null) return['—','gray','Données insuffisantes','bg-gray-400'];
            if ($s>=80) return['Adéquat','green','Répond aux standards internationaux','bg-green-500'];
            if ($s>=60) return['Partiellement adéquat','yellow','En dessous des standards, améliorations requises','bg-yellow-400'];
            if ($s>=40) return['Insuffisant','orange','Lacunes importantes, intervention prioritaire','bg-orange-500'];
            return          ['Critique','red','Non conforme aux standards minimaux – urgence humanitaire','bg-red-600'];
        };
        $qbg   = ['green'=>'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-700','yellow'=>'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-700','orange'=>'bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-700','red'=>'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-700','gray'=>'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700'];
        $qtxt  = ['green'=>'text-green-700 dark:text-green-300','yellow'=>'text-yellow-700 dark:text-yellow-300','orange'=>'text-orange-700 dark:text-orange-300','red'=>'text-red-700 dark:text-red-300','gray'=>'text-gray-500 dark:text-gray-400'];
        @endphp

        <div id="sec_qualite" class="section-tab bg-white dark:bg-gray-800 rounded-2xl shadow p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                    Évaluation de la qualité des services
                </h3>
                <div class="flex gap-1.5 text-xs text-gray-400">
                    <span class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700">Sphere 2018</span>
                    <span class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700">INEE</span>
                    <span class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700">UNHCR</span>
                    <span class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700">WHO</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
                @foreach([
                    ['💧 WASH',          $qWash,  'Sphere 2018 – ≥15L/pers/j, eau potable, assainissement, hygiène'],
                    ['🏥 Santé',         $qSante, 'Sphere + UNHCR – prestataire fonctionnel ≤5km, vaccination'],
                    ['📚 Éducation',     $qEduc,  'INEE Minimum Standards – école primaire ≤3km, taux scolarisation'],
                    ['🛤️ Infrastructure',$qInfra, 'Sphere Site Planning – routes, drainage, risque inondation'],
                ] as [$lbl, $score, $ref])
                @php [$rl,$rc,$rdesc,$rbar] = $qlabel($score); @endphp
                <div class="rounded-xl border p-4 {{ $qbg[$rc] }}">
                    <div class="flex justify-between items-start mb-3">
                        <span class="text-sm font-bold text-gray-800 dark:text-gray-100">{{ $lbl }}</span>
                        <span class="text-xl font-extrabold {{ $qtxt[$rc] }}">{{ $score !== null ? $score.'%' : '—' }}</span>
                    </div>
                    <div class="w-full h-2.5 bg-gray-200 dark:bg-gray-700 rounded-full mb-3">
                        @if($score !== null)
                        <div class="h-2.5 rounded-full {{ $rbar }}" style="width:{{ $score }}%"></div>
                        @endif
                    </div>
                    <span class="inline-flex items-center gap-1 text-xs font-semibold {{ $qtxt[$rc] }}">
                        <span class="w-2 h-2 rounded-full {{ $rbar }}"></span>{{ $rl }}
                    </span>
                    <p class="mt-1.5 text-xs text-gray-400 dark:text-gray-500 leading-snug">{{ $ref }}</p>
                </div>
                @endforeach
            </div>

            @if($qGlobal !== null)
            @php [$grl,$grc,$grdesc,$grbar] = $qlabel($qGlobal); @endphp
            <div class="rounded-xl border {{ $qbg[$grc] }} p-4 flex flex-col sm:flex-row items-start sm:items-center gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-1">
                        <span class="text-sm font-bold text-gray-700 dark:text-gray-200">Score global</span>
                        <span class="text-2xl font-extrabold {{ $qtxt[$grc] }}">{{ $qGlobal }}%</span>
                        <span class="px-3 py-1 rounded-full text-xs font-bold
                            {{ $grc==='green'  ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' : '' }}
                            {{ $grc==='yellow' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300' : '' }}
                            {{ $grc==='orange' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300' : '' }}
                            {{ $grc==='red'    ? 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300' : '' }}">
                            {{ $grl }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $grdesc }}</p>
                </div>
                <div class="w-full sm:w-56">
                    <div class="w-full h-4 bg-gray-200 dark:bg-gray-700 rounded-full">
                        <div class="h-4 rounded-full {{ $grbar }}" style="width:{{ $qGlobal }}%"></div>
                    </div>
                </div>
            </div>
            @endif

            <div class="mt-4 flex flex-wrap gap-x-5 gap-y-1.5">
                @foreach([['bg-green-500','≥ 80% — Adéquat'],['bg-yellow-400','60–79% — Partiellement adéquat'],['bg-orange-500','40–59% — Insuffisant'],['bg-red-600','< 40% — Critique']] as [$d,$l])
                <span class="inline-flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
                    <span class="w-2.5 h-2.5 rounded-full {{ $d }}"></span>{{ $l }}
                </span>
                @endforeach
            </div>
        </div>

        {{-- ══════════ BESOINS PRIORITAIRES ══════════ --}}
        <div id="sec_besoins" class="section-tab bg-white dark:bg-gray-800 rounded-2xl shadow p-6 mb-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="text-2xl">⚠️</span> Besoins prioritaires & Accès aux services
            </h3>
            @if($r->besoin_prioritaire_1 || $r->besoin_prioritaire_2 || $r->besoin_prioritaire_3)
            <div class="flex flex-wrap gap-3 mb-6">
                @foreach(array_filter([$r->besoin_prioritaire_1, $r->besoin_prioritaire_2, $r->besoin_prioritaire_3]) as $k => $besoin)
                <div class="flex items-center gap-2 px-4 py-2.5 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                    <span class="flex items-center justify-center w-6 h-6 bg-red-600 text-white text-xs font-bold rounded-full flex-shrink-0">{{ $k + 1 }}</span>
                    <span class="text-sm font-semibold text-red-800 dark:text-red-300">{{ $besoin }}</span>
                </div>
                @endforeach
            </div>
            @endif
            {{-- Accès services --}}
            <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Accès aux services</h4>
            @php
                $acces = [
                    'acces_education'=>'Éducation','acces_vivres'=>'Vivres','acces_sante'=>'Santé',
                    'acces_sante_mentale'=>'Santé mentale','acces_subsistance'=>'Subsistance',
                    'acces_cash'=>'Cash/Monnaie','acces_nfi'=>'NFI/AME','acces_nutrition'=>'Nutrition',
                    'acces_protection'=>'Protection','acces_abri'=>'Abri',
                    'acces_wash'=>'WASH','acces_dechets'=>'Gestion déchets',
                ];
                $accesColors = ['Bon'=>'green','Limité'=>'yellow','Très limité'=>'orange','Inexistant'=>'red'];
            @endphp
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                @foreach($acces as $field => $label)
                @php $val = $r->$field ?? null; $color = $val ? ($accesColors[$val] ?? 'gray') : 'gray'; @endphp
                <div class="rounded-lg p-3 border text-center text-xs
                    {{ $color === 'green' ? 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800' : '' }}
                    {{ $color === 'yellow' ? 'bg-yellow-50 border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800' : '' }}
                    {{ $color === 'orange' ? 'bg-orange-50 border-orange-200 dark:bg-orange-900/20 dark:border-orange-800' : '' }}
                    {{ $color === 'red' ? 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800' : '' }}
                    {{ $color === 'gray' ? 'bg-gray-50 border-gray-200 dark:bg-gray-700 dark:border-gray-600' : '' }}">
                    <div class="text-gray-500 dark:text-gray-400 font-medium mb-0.5">{{ $label }}</div>
                    <div class="font-bold
                        {{ $color === 'green' ? 'text-green-700 dark:text-green-300' : '' }}
                        {{ $color === 'yellow' ? 'text-yellow-700 dark:text-yellow-300' : '' }}
                        {{ $color === 'orange' ? 'text-orange-700 dark:text-orange-300' : '' }}
                        {{ $color === 'red' ? 'text-red-700 dark:text-red-300' : '' }}
                        {{ $color === 'gray' ? 'text-gray-500 dark:text-gray-400' : '' }}">
                        {{ $val ?? '—' }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ══════════ PARTENAIRES ══════════ --}}
        <div id="sec_partenaires" class="section-tab bg-white dark:bg-gray-800 rounded-2xl shadow p-6 mb-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="text-2xl">🤝</span> Partenaires présents
            </h3>
            @php
                $partenairesMap = [
                    'Protection'          => ['partenaires_protection_presence','partenaires_protection_autre'],
                    'GBV'                 => ['partenaires_gbv_presence','partenaires_gbv_autre'],
                    'Protection enfance'  => ['partenaires_enfance_presence','partenaires_enfance_autre'],
                    'Éducation'          => ['partenaires_education_presence','partenaires_education_autre'],
                    'Abri'               => ['partenaires_abri_presence','partenaires_abri_autre'],
                    'Eau'                => ['partenaires_eau_presence','partenaires_eau_autre'],
                    'Assainissement'     => ['partenaires_assainissement_presence','partenaires_assainissement_autre'],
                    'Gestion déchets'    => ['partenaires_dechets_presence','partenaires_dechets_autre'],
                    'Santé primaire'     => ['partenaires_sante_primaire_presence','partenaires_sante_primaire_autre'],
                    'Santé secondaire'   => ['partenaires_sante_secondaire_presence','partenaires_sante_secondaire_autre'],
                    'MHPSS'              => ['partenaires_mhpss_presence','partenaires_mhpss_autre'],
                    'Nutrition'          => ['partenaires_nutrition_presence','partenaires_nutrition_autre'],
                    'Aide alimentaire'   => ['partenaires_alimentaire_presence','partenaires_alimentaire_autre'],
                    'Cohésion sociale'   => ['partenaires_cohesion_presence','partenaires_cohesion_autre'],
                    'Subsistance'        => ['partenaires_subsistance_presence','partenaires_subsistance_autre'],
                    'Communication'      => ['partenaires_communication_presence','partenaires_communication_autre'],
                ];
                $presents = [];
                $absents = [];
                foreach ($partenairesMap as $sec => [$pField, $aField]) {
                    $presence = $r->$pField ?? false;
                    $autre = $r->$aField ?? null;
                    if ($presence) $presents[$sec] = $autre;
                    else $absents[] = $sec;
                }
            @endphp
            @if(count($presents) > 0)
            <div class="flex flex-wrap gap-2 mb-4">
                @foreach($presents as $sec => $autre)
                <div class="flex items-center gap-1.5 px-3 py-1.5 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg">
                    <svg class="w-3.5 h-3.5 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span class="text-sm font-medium text-green-800 dark:text-green-300">{{ $sec }}</span>
                    @if($autre)
                    <span class="text-xs text-green-600 dark:text-green-400">({{ $autre }})</span>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
            @if(count($absents) > 0)
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Secteurs sans couverture</p>
            <div class="flex flex-wrap gap-1.5">
                @foreach($absents as $sec)
                <span class="px-2.5 py-1 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-xs rounded-full">{{ $sec }}</span>
                @endforeach
            </div>
            @endif
        </div>

        @endif {{-- end ossatReport --}}
        @endif {{-- disabled legacy OSSAT profile --}}
        @endif {{-- end isset($site) --}}

    </div>
</main>

<script>
document.querySelectorAll('[data-question-settings-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        document.getElementById('question-preferences-form')?.classList.toggle('hidden');
    });
});

document.querySelectorAll('[data-hide-question]').forEach((button) => {
    button.addEventListener('click', () => {
        const form = document.getElementById('question-preferences-form');
        const key = button.dataset.hideQuestion;
        const checkbox = form?.querySelector(`[data-question-key="${CSS.escape(key)}"]`);
        if (checkbox) {
            checkbox.checked = false;
            form.submit();
        }
    });
});

document.querySelector('[data-question-search]')?.addEventListener('input', (event) => {
    const query = event.target.value.trim().toLocaleLowerCase();
    document.querySelectorAll('[data-question-group]').forEach((group) => {
        let visibleOptions = 0;
        group.querySelectorAll('[data-question-option]').forEach((option) => {
            const visible = option.textContent.toLocaleLowerCase().includes(query);
            option.classList.toggle('hidden', !visible);
            visibleOptions += visible ? 1 : 0;
        });
        group.classList.toggle('hidden', visibleOptions === 0);
    });
});

document.querySelectorAll('[data-select-questions]').forEach((button) => {
    button.addEventListener('click', () => {
        document.querySelectorAll('#question-preferences-form [data-question-key]').forEach((checkbox) => {
            checkbox.checked = button.dataset.selectQuestions === 'all'
                || (button.dataset.selectQuestions === 'recommended' && checkbox.dataset.defaultVisible === 'true');
        });
    });
});
</script>

{{-- ══════════ FOOTER ══════════ --}}
<footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-6">
    <div class="max-w-7xl mx-auto px-4 text-center text-sm text-gray-400">
        © 2026 DMS · CCCM · HCR · WNH. Tous droits réservés.
    </div>
</footer>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
// ── Dark mode ──
(function() {
    const saved = localStorage.getItem('theme');
    if (saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    }
})();
function toggleDarkMode() {
    const html = document.documentElement;
    const isDark = html.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
}

// ── Carte Leaflet ──
@isset($site)
@if($site->latitude && $site->longitude)
(function() {
    const lat  = {{ $site->latitude }};
    const lng  = {{ $site->longitude }};
    const name = @json($site->nom);
    const map  = L.map('site-map').setView([lat, lng], 13);
    window.__siteProfileMap = map;
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 18
    }).addTo(map);
    L.marker([lat, lng])
        .addTo(map)
        .bindPopup('<b>' + name + '</b><br>' + lat + ', ' + lng)
        .openPopup();
})();
@endif
@endisset

// ── Cascade selectors ──
const selProv  = document.getElementById('sel_province');
const selTerr  = document.getElementById('sel_territoire');
const selComm  = document.getElementById('sel_commune');
const selSite  = document.getElementById('sel_site');
const btnVoir  = document.getElementById('btn_voir_profil');
const form     = document.getElementById('site-selector-form');

@isset($site)
// Pré-remplissage si un site est déjà affiché
const initProvinceId   = {{ $site->commune?->territoire?->province_id ?? 'null' }};
const initTerritoireId = {{ $site->commune?->territoire_id ?? 'null' }};
const initCommuneId    = @json($site->commune_id ? ('commune:' . $site->commune_id) : ($site->zone_sante ? ('zone:' . $site->zone_sante) : null));
const initSiteId       = {{ $site->id }};
@else
const initProvinceId   = null;
const initTerritoireId = null;
const initCommuneId    = null;
const initSiteId       = null;
@endisset

function resetCommuneSelect(placeholder) {
    selComm.innerHTML = `<option value="">${placeholder}</option>`;
}

function resetSiteSelect(placeholder) {
    selSite.innerHTML = `<option value="">${placeholder}</option>`;
    btnVoir.disabled = true;
}

function loadTerritoires(provinceId, selectedTerritoireId = null, callback = null) {
    if (!provinceId) {
        selTerr.innerHTML = '<option value="">— Sélectionner la province —</option>';
        resetCommuneSelect('— Sélectionner le territoire —');
        resetSiteSelect('— Sélectionner la zone de santé —');
        return;
    }
    fetch('/api/public/territoires?province_id=' + provinceId)
        .then(r => r.json())
        .then(function(data) {
            selTerr.innerHTML = '<option value="">— Territoire —</option>';
            data.forEach(function(t) {
                const opt = new Option(t.name, t.id);
                if (selectedTerritoireId && t.id == selectedTerritoireId) opt.selected = true;
                selTerr.appendChild(opt);
            });
            if (callback) callback();
        });
}

function loadCommunes(territoireId, selectedCommuneId = null, callback = null) {
    if (!territoireId) {
        resetCommuneSelect('— Sélectionner le territoire —');
        resetSiteSelect('— Sélectionner la zone de santé —');
        return;
    }

    fetch('/api/public/communes?territoire_id=' + territoireId)
        .then(r => r.json())
        .then(function(data) {
            selComm.innerHTML = '<option value="">— Zone de santé —</option>';
            data.forEach(function(c) {
                const opt = new Option(c.name, c.id);
                if (selectedCommuneId && c.id == selectedCommuneId) opt.selected = true;
                selComm.appendChild(opt);
            });
            if (callback) callback();
        });
}

function loadSites(communeId, territoireId = null, selectedSiteId = null) {
    if (!communeId && !territoireId) {
        resetSiteSelect('— Sélectionner la zone de santé —');
        return;
    }

    const params = new URLSearchParams();
    if (territoireId) {
        params.set('territoire_id', territoireId);
    }

    if (communeId) {
        params.set('commune_id', communeId);
    }

    fetch('/api/public/sites?' + params.toString())
        .then(r => r.json())
        .then(function(data) {
            selSite.innerHTML = '<option value="">— Site —</option>';
            data.forEach(function(s) {
                const opt = new Option(s.nom + (s.code_site ? ' (' + s.code_site + ')' : ''), s.id);
                if (selectedSiteId && s.id == selectedSiteId) opt.selected = true;
                selSite.appendChild(opt);
            });
            btnVoir.disabled = !selSite.value;
        });
}

selProv.addEventListener('change', function() {
    loadTerritoires(this.value);
});

selTerr.addEventListener('change', function() {
    loadCommunes(this.value);
});

selComm.addEventListener('change', function() {
    loadSites(this.value, selTerr.value || null);
});

selSite.addEventListener('change', function() {
    btnVoir.disabled = !this.value;
});

form.addEventListener('submit', function(e) {
    e.preventDefault();
    const siteId = selSite.value;
    if (siteId) window.location.href = '/profil-site/' + siteId;
});

// Initialisation cascade au chargement si pré-sélection
if (initProvinceId) {
    loadTerritoires(initProvinceId, initTerritoireId, function() {
        loadCommunes(initTerritoireId, initCommuneId, function() {
            loadSites(initCommuneId, initTerritoireId, initSiteId);
        });
    });
}

const btnPrintProfile = document.getElementById('btn-print-profile');
if (btnPrintProfile) {
    btnPrintProfile.addEventListener('click', function() {
        if (window.__siteProfileMap) {
            window.__siteProfileMap.invalidateSize();
        }
        setTimeout(function() { window.print(); }, 120);
    });
}

window.addEventListener('beforeprint', function() {
    if (window.__siteProfileMap) {
        window.__siteProfileMap.invalidateSize();
    }
});

window.addEventListener('afterprint', function() {
    if (window.__siteProfileMap) {
        setTimeout(function() { window.__siteProfileMap.invalidateSize(); }, 120);
    }
});
</script>
</body>
</html>
