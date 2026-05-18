{{--
    Partial : Tableau de bord OSSAT pour le profil d'un site
    Variables requises : $site (Site), $ossatReport (OssatReport|null), $populationMouvement (SiteMouvementPopulation|null)
--}}
<div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
    <div class="flex justify-between items-start mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                État des lieux OSSAT
            </h3>
            @if($ossatReport)
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    Dernier rapport : {{ $ossatReport->today?->format('d/m/Y') ?? '—' }}
                    &nbsp;·&nbsp;
                    @php
                        $badgeMap = ['brouillon'=>'bg-gray-100 text-gray-700','soumis'=>'bg-yellow-100 text-yellow-800','valide'=>'bg-green-100 text-green-800','rejete'=>'bg-red-100 text-red-700'];
                        $labelMap = ['brouillon'=>'Brouillon','soumis'=>'Soumis','valide'=>'Validé','rejete'=>'Rejeté'];
                        $sv = $ossatReport->statut_validation ?? 'brouillon';
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $badgeMap[$sv] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ $labelMap[$sv] ?? $sv }}
                    </span>
                    &nbsp;·&nbsp; Enquêteur : {{ $ossatReport->enumerator_name ?? '—' }}
                </p>
            @else
                <p class="text-sm text-gray-400 mt-0.5">Aucun rapport OSSAT disponible pour ce site.</p>
            @endif
        </div>
        <div class="flex gap-2 flex-wrap flex-shrink-0">
            <a href="{{ route('public.site.show', $site) }}" target="_blank"
               class="px-3 py-1.5 text-xs font-medium bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg border border-gray-200 dark:border-gray-600 transition-colors flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                Profil public
            </a>
            @if($ossatReport)
                <a href="{{ route('ossat.show', $ossatReport) }}"
                   class="px-3 py-1.5 text-xs font-medium bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg border border-blue-200 transition-colors">
                    Voir le rapport complet
                </a>
            @endif
            <a href="{{ route('ossat.create', ['site_id' => $site->id]) }}"
               class="px-3 py-1.5 text-xs font-medium bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors">
                + Nouveau rapport
            </a>
        </div>
    </div>

    @if(!$ossatReport)
        <div class="rounded-lg bg-gray-50 dark:bg-gray-700 border border-dashed border-gray-300 dark:border-gray-600 p-8 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-sm text-gray-500 dark:text-gray-400">Aucune donnée OSSAT collectée pour ce site.</p>
            <a href="{{ route('ossat.create', ['site_id' => $site->id]) }}"
               class="mt-3 inline-flex px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg">
                Créer le premier rapport
            </a>
        </div>
    @else
        @php
            $r = $ossatReport;

            /* ── helpers ── */
            $yes  = fn($v) => $v === true  || $v === 1  || $v === '1'  || strtolower((string)$v) === 'oui';
            $no   = fn($v) => $v === false || $v === 0  || $v === '0'  || strtolower((string)$v) === 'non';

            /* ── indicateurs couleur ── */
            // vert = OK, rouge = alerte, gris = inconnu
            $ind = function($condition, $label, $ok=true) {
                // $condition true → indicateur positif ($ok=true → vert, $ok=false → rouge)
                if ($condition === null) return ['color'=>'gray','label'=>$label,'val'=>'—'];
                $good = $ok ? $condition : !$condition;
                return ['color' => $good?'green':'red', 'label'=>$label, 'val'=>$condition?'Oui':'Non'];
            };

            /* ── Population (source : mouvements de population si disponible, sinon OSSAT) ── */
            $pm = $populationMouvement ?? null;
            $pop    = $pm ? (int)($pm->individus  ?? 0) : (int)($r->nb_individus ?? 0);
            $menage = $pm ? (int)($pm->menages    ?? 0) : (int)($r->nb_familles  ?? 0);
            $enfants5  = $pm
                ? (int)($pm->h_0_5  ?? 0) + (int)($pm->f_0_5  ?? 0)
                : (int)($r->h_0_4   ?? 0) + (int)($r->f_0_4   ?? 0);
            $enfants17 = $pm
                ? (int)($pm->h_6_17 ?? 0) + (int)($pm->f_6_17 ?? 0)
                : (int)($r->h_5_17  ?? 0) + (int)($r->f_5_17  ?? 0);
            $adultes   = $pm
                ? (int)($pm->h_18_59  ?? 0) + (int)($pm->f_18_59  ?? 0)
                : (int)($r->h_18_59   ?? 0) + (int)($r->f_18_59   ?? 0);
            $aines     = $pm
                ? (int)($pm->h_60_plus ?? 0) + (int)($pm->f_60_plus ?? 0)
                : (int)($r->h_60plus   ?? 0) + (int)($r->f_60plus   ?? 0);
            $femChef   = (int)($r->menages_femme_chef       ?? 0);
            $enfNonAcc = (int)($r->enfants_non_accompagnes  ?? 0);
            $handicap  = (int)($r->handicap_physique ?? 0) + (int)($r->handicap_mental ?? 0);

            /* ── Écart logement ── */
            $capacite = (int)($r->capacite_accueil ?? 0);
            $enAttente= (int)($r->familles_attente ?? 0);
        @endphp

        {{-- ═══════════ SECTION 1 : Population ═══════════ --}}
        <div class="mb-6">
            <div class="flex items-center gap-3 mb-3">
                <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Population</h4>
                @if($populationMouvement ?? null)
                    <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700">
                        📊 Mouvements · {{ ($populationMouvement->date_mouvement)?->format('d/m/Y') ?? '—' }}
                    </span>
                @elseif($ossatReport)
                    <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-700">
                        📋 OSSAT · {{ $ossatReport->today?->format('d/m/Y') ?? '—' }}
                    </span>
                @endif
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                @php
                    $popCards = [
                        ['label'=>'Individus',         'val'=> number_format($pop),    'icon'=>'👥', 'color'=>'blue'],
                        ['label'=>'Ménages',            'val'=> number_format($menage), 'icon'=>'🏠', 'color'=>'blue'],
                        ['label'=>'Enfants &lt;5 ans',   'val'=> number_format($enfants5),  'icon'=>'👶', 'color'=>'amber'],
                        ['label'=>'Enfants 5-17 ans',  'val'=> number_format($enfants17), 'icon'=>'🧒', 'color'=>'amber'],
                        ['label'=>'Adultes 18-59',     'val'=> number_format($adultes),   'icon'=>'🧑', 'color'=>'slate'],
                        ['label'=>'Personnes âgées',   'val'=> number_format($aines),     'icon'=>'👴', 'color'=>'slate'],
                    ];
                    $colorMap = ['blue'=>'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300','amber'=>'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300','slate'=>'bg-slate-50 dark:bg-slate-700 text-slate-700 dark:text-slate-300'];
                @endphp
                @foreach($popCards as $card)
                <div class="rounded-lg p-3 {{ $colorMap[$card['color']] }} text-center">
                    <div class="text-xl font-bold">{!! $card['val'] !!}</div>
                    <div class="text-xs mt-0.5">{!! $card['label'] !!}</div>
                </div>
                @endforeach
            </div>

            @if($femChef > 0 || $enfNonAcc > 0 || $handicap > 0)
            <div class="mt-3 flex flex-wrap gap-2">
                @if($femChef > 0)
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs bg-pink-50 dark:bg-pink-900/20 text-pink-700 dark:text-pink-300 border border-pink-200 dark:border-pink-800">
                    👩 {{ number_format($femChef) }} ménages dirigés par une femme
                </span>
                @endif
                @if($enfNonAcc > 0)
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs bg-orange-50 dark:bg-orange-900/20 text-orange-700 border border-orange-200">
                    ⚠️ {{ number_format($enfNonAcc) }} enfants non accompagnés
                </span>
                @endif
                @if($handicap > 0)
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs bg-purple-50 dark:bg-purple-900/20 text-purple-700 border border-purple-200">
                    ♿ {{ number_format($handicap) }} personnes en situation de handicap
                </span>
                @endif
            </div>
            @endif
        </div>

        {{-- ═══════════ SECTION GESTION & COORDINATION ═══════════ --}}
        @php
            // Agence gestionnaire : OSSAT en priorité, sinon Site model
            $agenceGestion = $r->agence_gestion_nom
                ?: ($r->agence_gestion_autre ?: ($site->organisation->name ?? null));

            // Agence administrative
            $agenceAdmin = $r->agence_admin_nom
                ?: ($r->agence_admin_autre ?? null);

            // Agence coordinatrice
            $agenceCoord = $r->agence_coord_nom
                ?: ($site->coordinateur->name ?? null);

            // Sources d'information / annonces distributions (déjà casté en array dans OssatReport)
            $infoSources = $r->info_source ?? [];
            if (!empty($r->info_source_autre)) {
                $infoSources[] = $r->info_source_autre;
            }

            // Comités (déjà castés en array)
            $comitesList = $r->comites ?? [];
            if (!empty($r->autres_comites)) {
                $comitesList[] = $r->autres_comites;
            }

            $showGestion = $agenceGestion || $r->gestionnaire_nom || $r->nb_hommes_staff !== null || $r->nb_femmes_staff !== null;
            $showCoord   = $agenceCoord || $agenceAdmin || $r->reunions_coordination !== null || $r->presence_comite !== null;
            $showComm    = !empty($infoSources) || $r->regularite_assistance_alimentaire || $r->cci !== null || $r->mgp !== null;
        @endphp

        @if($showGestion || $showCoord || $showComm)
        <div class="mb-6">
            <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                Gestion &amp; Coordination du site
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                {{-- ── Agence gestionnaire ── --}}
                @if($showGestion)
                <div class="rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="px-3 py-2 bg-slate-50 dark:bg-slate-800/50 font-semibold text-sm text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                        🏢 Agence gestionnaire
                    </div>
                    <div class="p-3 space-y-3">
                        @if($agenceGestion)
                        <div>
                            <span class="text-xs text-gray-400 block mb-0.5">Organisation</span>
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $agenceGestion }}</span>
                        </div>
                        @endif

                        @if($r->gestionnaire_nom)
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-2">
                            <span class="text-xs text-gray-400 block mb-1">Gestionnaire dédié</span>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                {{ $r->gestionnaire_nom }}
                                @if($r->gestionnaire_sexe)
                                    <span class="text-xs text-gray-400">({{ $r->gestionnaire_sexe }})</span>
                                @endif
                            </span>
                            @if($r->gestionnaire_telephone)
                            <a href="tel:{{ $r->gestionnaire_telephone }}"
                               class="mt-0.5 text-xs text-blue-600 hover:underline flex items-center gap-1">
                                📞 {{ $r->gestionnaire_telephone }}
                            </a>
                            @endif
                            @if($r->gestionnaire_email)
                            <a href="mailto:{{ $r->gestionnaire_email }}"
                               class="text-xs text-blue-600 hover:underline flex items-center gap-1">
                                ✉️ {{ $r->gestionnaire_email }}
                            </a>
                            @endif
                        </div>
                        @endif

                        @if($r->nb_hommes_staff !== null || $r->nb_femmes_staff !== null)
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-2">
                            <span class="text-xs text-gray-400 block mb-1">Staff sur site</span>
                            <div class="flex gap-2">
                                @if($r->nb_hommes_staff !== null)
                                <span class="text-xs px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300">
                                    👨 {{ $r->nb_hommes_staff }} H
                                </span>
                                @endif
                                @if($r->nb_femmes_staff !== null)
                                <span class="text-xs px-2 py-0.5 rounded bg-pink-50 dark:bg-pink-900/20 text-pink-700 dark:text-pink-300">
                                    👩 {{ $r->nb_femmes_staff }} F
                                </span>
                                @endif
                            </div>
                        </div>
                        @endif

                        @if(!$agenceGestion && !$r->gestionnaire_nom && $r->nb_hommes_staff === null)
                        <p class="text-xs text-gray-400 italic">Non renseigné</p>
                        @endif
                    </div>
                </div>
                @endif

                {{-- ── Coordination & Administration ── --}}
                @if($showCoord)
                <div class="rounded-lg border border-indigo-200 dark:border-indigo-800 overflow-hidden">
                    <div class="px-3 py-2 bg-indigo-50 dark:bg-indigo-900/20 font-semibold text-sm text-indigo-700 dark:text-indigo-300 flex items-center gap-1.5">
                        🔗 Coordination &amp; Administration
                    </div>
                    <div class="p-3 space-y-3">
                        @if($agenceCoord)
                        <div>
                            <span class="text-xs text-gray-400 block mb-0.5">Agence coordinatrice</span>
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $agenceCoord }}</span>
                        </div>
                        @endif

                        @if($agenceAdmin)
                        <div @if($agenceCoord) class="border-t border-gray-100 dark:border-gray-700 pt-2" @endif>
                            <span class="text-xs text-gray-400 block mb-0.5">Agence administrative</span>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $agenceAdmin }}</span>
                            @if($r->admin_nom)
                            <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                {{ $r->admin_nom }}
                                @if($r->admin_sexe)<span class="text-gray-400"> ({{ $r->admin_sexe }})</span>@endif
                                @if($r->admin_telephone)
                                    — <a href="tel:{{ $r->admin_telephone }}" class="text-blue-600 hover:underline">{{ $r->admin_telephone }}</a>
                                @endif
                            </div>
                            @endif
                        </div>
                        @endif

                        @if($r->reunions_coordination !== null)
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-2">
                            <span class="text-xs text-gray-400 block mb-1">Réunions de coordination</span>
                            <div class="flex flex-wrap gap-1.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                    {{ $r->reunions_coordination
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'
                                        : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' }}">
                                    {{ $r->reunions_coordination ? 'Organisées' : 'Non organisées' }}
                                </span>
                                @if($r->reunions_coordination && $r->periodicite_reunions)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    {{ $r->periodicite_reunions }}
                                </span>
                                @endif
                            </div>
                        </div>
                        @endif

                        @if($r->presence_comite !== null)
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-2">
                            <span class="text-xs text-gray-400 block mb-1">Comités de site</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                {{ $r->presence_comite
                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'
                                    : 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300' }}">
                                {{ $r->presence_comite ? 'Présents' : 'Absents' }}
                            </span>
                            @if($r->presence_comite && !empty($comitesList))
                            <div class="mt-1.5 flex flex-wrap gap-1">
                                @foreach($comitesList as $comite)
                                <span class="px-1.5 py-0.5 text-xs bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-300 rounded border border-indigo-100 dark:border-indigo-800">
                                    {{ $comite }}
                                </span>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @endif

                        @if(!$agenceCoord && !$agenceAdmin && $r->reunions_coordination === null && $r->presence_comite === null)
                        <p class="text-xs text-gray-400 italic">Non renseigné</p>
                        @endif
                    </div>
                </div>
                @endif

                {{-- ── Communication & Distributions ── --}}
                @if($showComm)
                <div class="rounded-lg border border-emerald-200 dark:border-emerald-800 overflow-hidden">
                    <div class="px-3 py-2 bg-emerald-50 dark:bg-emerald-900/20 font-semibold text-sm text-emerald-700 dark:text-emerald-300 flex items-center gap-1.5">
                        📢 Communication &amp; Distributions
                    </div>
                    <div class="p-3 space-y-3">
                        @if(!empty($infoSources))
                        <div>
                            <span class="text-xs text-gray-400 block mb-1.5">Comment les informations sont annoncées</span>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($infoSources as $source)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700">
                                    {{ $source }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($r->regularite_assistance_alimentaire)
                        <div @if(!empty($infoSources)) class="border-t border-gray-100 dark:border-gray-700 pt-2" @endif>
                            <span class="text-xs text-gray-400 block mb-0.5">Régularité de l'aide alimentaire</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                {{ $r->regularite_assistance_alimentaire }}
                            </span>
                        </div>
                        @endif

                        @if($r->cci !== null || $r->mgp !== null)
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-2">
                            <span class="text-xs text-gray-400 block mb-1">Mécanismes de plainte (AAP)</span>
                            <div class="flex flex-wrap gap-2">
                                @if($r->cci !== null)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium
                                    {{ $r->cci ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}">
                                    CCI {{ $r->cci ? '✓' : '✗' }}
                                </span>
                                @endif
                                @if($r->mgp !== null)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium
                                    {{ $r->mgp ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}">
                                    MGP {{ $r->mgp ? '✓' : '✗' }}
                                </span>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

            </div>
        </div>
        @endif

        {{-- ═══════════ SECTION 2 : Indicateurs sectoriels ═══════════ --}}
        @php
            $sectors = [
                [
                    'label' => 'Abri', 'icon' => '🏕️',
                    'color' => 'indigo',
                    'items' => [
                        ['Capacité d\'accueil', $capacite > 0 ? number_format($capacite).' familles' : '—', 'gray'],
                        ['Familles en attente', $enAttente > 0 ? number_format($enAttente) : '0', $enAttente > 0 ? 'red' : 'green'],
                        ['Réduction prévue', $r->reduction_prevue ? 'Oui' : 'Non', $r->reduction_prevue ? 'orange' : 'green'],
                    ]
                ],
                [
                    'label' => 'WASH', 'icon' => '💧',
                    'color' => 'cyan',
                    'items' => [
                        ['Litres/pers/jour', ($r->litres_eau_jour ?? null) !== null ? $r->litres_eau_jour.'L' : '—', ($r->litres_eau_jour ?? 0) >= 15 ? 'green' : (($r->litres_eau_jour ?? 0) > 0 ? 'orange' : 'red')],
                        ['Eau potable', $r->qualite_eau ? 'Oui' : ($r->qualite_eau === false ? 'Non' : '—'), $r->qualite_eau === true ? 'green' : ($r->qualite_eau === false ? 'red' : 'gray')],
                        ['Défécation à ciel ouvert', $r->defecation_plein_air ? 'Oui' : ($r->defecation_plein_air === false ? 'Non' : '—'), $r->defecation_plein_air === false ? 'green' : ($r->defecation_plein_air === true ? 'red' : 'gray')],
                        ['Savon disponible', $r->savon_disponible ? 'Oui' : ($r->savon_disponible === false ? 'Non' : '—'), $r->savon_disponible === true ? 'green' : ($r->savon_disponible === false ? 'red' : 'gray')],
                        ['Nb latrines', ($r->nb_latrines ?? null) !== null ? $r->nb_latrines : '—', 'gray'],
                        ['WASH adapté handicapés', $r->wash_adapte_handicapes ? 'Oui' : ($r->wash_adapte_handicapes === false ? 'Non' : '—'), $r->wash_adapte_handicapes === true ? 'green' : ($r->wash_adapte_handicapes === false ? 'orange' : 'gray')],
                    ]
                ],
                [
                    'label' => 'Santé', 'icon' => '🏥',
                    'color' => 'red',
                    'items' => [
                        ['Prestataire fonctionnel', $r->soin_sante_fonctionnel ? 'Oui' : ($r->soin_sante_fonctionnel === false ? 'Non' : '—'), $r->soin_sante_fonctionnel === true ? 'green' : ($r->soin_sante_fonctionnel === false ? 'red' : 'gray')],
                        ['Soins à l\'intérieur', $r->soin_sante_interieur ? 'Oui' : ($r->soin_sante_interieur === false ? 'Non' : '—'), $r->soin_sante_interieur === true ? 'green' : ($r->soin_sante_interieur === false ? 'orange' : 'gray')],
                        ['Urgences chirurgicales', $r->services_urgences ? 'Oui' : ($r->services_urgences === false ? 'Non' : '—'), $r->services_urgences === true ? 'green' : 'gray'],
                        ['Enfants non vaccinés', $r->enfants_non_vaccines ? 'Oui' : 'Non', $r->enfants_non_vaccines ? 'red' : 'green'],
                        ['Ambulance', $r->ambulance ? 'Oui' : ($r->ambulance === false ? 'Non' : '—'), $r->ambulance === true ? 'green' : ($r->ambulance === false ? 'orange' : 'gray')],
                    ]
                ],
                [
                    'label' => 'Sécurité', 'icon' => '🛡️',
                    'color' => 'orange',
                    'items' => [
                        ['Sentiment de sécurité', $r->sentiment_securite ? 'Oui' : ($r->sentiment_securite === false ? 'Non' : '—'), $r->sentiment_securite === true ? 'green' : ($r->sentiment_securite === false ? 'red' : 'gray')],
                        ['Incidents signalés', $r->incidents_securitaires ? $r->incidents_securitaires : '—', $r->incidents_securitaires && $r->incidents_securitaires !== 'Non' ? 'red' : 'green'],
                        ['Restrictions mouvement', $r->restrictions_mouvement ? 'Oui' : ($r->restrictions_mouvement === false ? 'Non' : '—'), $r->restrictions_mouvement === false ? 'green' : ($r->restrictions_mouvement === true ? 'red' : 'gray')],
                        ['Zones dangereuses (femmes)', !empty($r->zones_dangereuses_femmes) ? 'Signalées' : 'Non', !empty($r->zones_dangereuses_femmes) ? 'red' : 'green'],
                    ]
                ],
                [
                    'label' => 'Éducation', 'icon' => '📚',
                    'color' => 'violet',
                    'items' => [
                        ['École primaire', $r->ecole_primaire_presente ? 'Présente' : ($r->ecole_primaire_presente === false ? 'Absente' : '—'), $r->ecole_primaire_presente === true ? 'green' : ($r->ecole_primaire_presente === false ? 'orange' : 'gray')],
                        ['École secondaire', $r->ecole_secondaire_presente ? 'Présente' : ($r->ecole_secondaire_presente === false ? 'Absente' : '—'), $r->ecole_secondaire_presente === true ? 'green' : ($r->ecole_secondaire_presente === false ? 'orange' : 'gray')],
                        ['Enfants scolarisés', ($r->nb_enfants_scolarises ?? null) !== null ? number_format($r->nb_enfants_scolarises) : '—', 'gray'],
                        ['Éducation informelle', $r->education_informelle ? 'Oui' : ($r->education_informelle === false ? 'Non' : '—'), 'gray'],
                    ]
                ],
                [
                    'label' => 'Subsistance', 'icon' => '🛒',
                    'color' => 'emerald',
                    'items' => [
                        ['Marché intérieur', $r->marche_interieur ? 'Oui' : ($r->marche_interieur === false ? 'Non' : '—'), $r->marche_interieur === true ? 'green' : 'gray'],
                        ['Familles avec revenu', ($r->nb_familles_avec_revenu ?? null) !== null ? number_format($r->nb_familles_avec_revenu) : '—', 'gray'],
                        ['Repas/jour', ($r->repas_par_jour ?? null) !== null ? $r->repas_par_jour : '—', ($r->repas_par_jour ?? 0) >= 3 ? 'green' : (($r->repas_par_jour ?? 0) >= 2 ? 'orange' : 'red')],
                        ['Stock magasin', $r->stockage_magasin ? 'Oui' : ($r->stockage_magasin === false ? 'Non' : '—'), $r->stockage_magasin === true ? 'green' : 'gray'],
                    ]
                ],
                [
                    'label' => 'Éclairage & Énergie', 'icon' => '💡',
                    'color' => 'yellow',
                    'items' => [
                        ['Éclairage existant', $r->eclairage_existant ? 'Oui' : ($r->eclairage_existant === false ? 'Non' : '—'), $r->eclairage_existant === true ? 'green' : ($r->eclairage_existant === false ? 'orange' : 'gray')],
                        ['Éclairage latrines', $r->eclairage_latrines ? 'Oui' : ($r->eclairage_latrines === false ? 'Non' : '—'), $r->eclairage_latrines === true ? 'green' : ($r->eclairage_latrines === false ? 'red' : 'gray')],
                        ['Sources', !empty($r->sources_electricite) ? implode(', ', (array)$r->sources_electricite) : '—', 'gray'],
                    ]
                ],
                [
                    'label' => 'Accès aux services', 'icon' => '🤝',
                    'color' => 'teal',
                    'items' => [
                        ['Vivres', $r->acces_vivres ?? '—', 'gray'],
                        ['NFI', $r->acces_nfi ?? '—', 'gray'],
                        ['Protection', $r->acces_protection ?? '—', 'gray'],
                        ['Cash', $r->acces_cash ?? '—', 'gray'],
                        ['Nutrition', $r->acces_nutrition ?? '—', 'gray'],
                        ['Santé mentale', $r->acces_sante_mentale ?? '—', 'gray'],
                    ]
                ],
            ];
            $sectorColors = [
                'indigo'  => 'border-indigo-200 dark:border-indigo-800',
                'cyan'    => 'border-cyan-200 dark:border-cyan-800',
                'red'     => 'border-red-200 dark:border-red-800',
                'orange'  => 'border-orange-200 dark:border-orange-800',
                'violet'  => 'border-violet-200 dark:border-violet-800',
                'emerald' => 'border-emerald-200 dark:border-emerald-800',
                'yellow'  => 'border-yellow-200 dark:border-yellow-800',
                'teal'    => 'border-teal-200 dark:border-teal-800',
            ];
            $sectorHeaderColors = [
                'indigo'  => 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300',
                'cyan'    => 'bg-cyan-50 dark:bg-cyan-900/20 text-cyan-700 dark:text-cyan-300',
                'red'     => 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300',
                'orange'  => 'bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300',
                'violet'  => 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300',
                'emerald' => 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300',
                'yellow'  => 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300',
                'teal'    => 'bg-teal-50 dark:bg-teal-900/20 text-teal-700 dark:text-teal-300',
            ];
            $badgeColors = [
                'green'  => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                'red'    => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
                'orange' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',
                'gray'   => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
            ];
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
            @foreach($sectors as $sector)
            <div class="rounded-lg border {{ $sectorColors[$sector['color']] }} overflow-hidden">
                <div class="px-3 py-2 {{ $sectorHeaderColors[$sector['color']] }} font-semibold text-sm flex items-center gap-1.5">
                    <span>{{ $sector['icon'] }}</span> {{ $sector['label'] }}
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($sector['items'] as $item)
                    <div class="px-3 py-2 flex justify-between items-center gap-2 text-xs">
                        <span class="text-gray-600 dark:text-gray-400">{{ $item[0] }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $badgeColors[$item[2]] ?? $badgeColors['gray'] }} whitespace-nowrap">
                            {{ $item[1] }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        {{-- ═══════════ SECTION 3 : Évaluation qualité des services ═══════════ --}}
        @php
        /*
         * ─── Scores qualité basés sur les standards internationaux ───
         * Référentiels : Sphere 2018, UNHCR Site Planning, INEE Minimum Standards,
         *               WHO Health Cluster, HTF Infrastructure Guidelines
         */

        /* ── helper : score pondéré sécurisé ── */
        $ws = function(array $items) {
            $totalW = 0; $totalS = 0;
            foreach ($items as [$score, $weight]) {
                if ($score !== null) { $totalS += $score * $weight; $totalW += $weight; }
            }
            return $totalW > 0 ? round($totalS / $totalW) : null;
        };

        /* ── helper : note état ── */
        $etatScore = function($val) {
            if ($val === null) return null;
            $v = mb_strtolower(trim((string)$val));
            if (in_array($v, ['bon','bonne','bons','bonnes','good'])) return 100;
            if (in_array($v, ['passable','moyen','moyenne','acceptable'])) return 55;
            if (in_array($v, ['mauvais','mauvaise','mauvaises','bad','poor','dégradé'])) return 15;
            return null;
        };

        /* ── helper : note distance km ── */
        $distScore = function($km, $seuil1, $seuil2, $seuil3) {
            if ($km === null) return null;
            $km = (float)$km;
            if ($km <= $seuil1) return 100;
            if ($km <= $seuil2) return 65;
            if ($km <= $seuil3) return 30;
            return 5;
        };

        /* ══════════════════════════════════════════════
           SCORE WASH  (Sphere 2018 – WASH chapter)
           ══════════════════════════════════════════════ */
        $scoreWashItems = [
            // Quantité d'eau: 0-100. Sphere min=15L/j, optimal≥20L/j. Poids 35%
            [
                (function() use ($r) {
                    $l = $r->litres_eau_jour ?? null;
                    if ($l === null) return null;
                    $l = (float)$l;
                    if ($l >= 20) return 100;
                    if ($l >= 15) return 75;   // Sphere minimum atteint
                    if ($l >=  7) return 40;   // seuil survie urgence
                    return 10;
                })(),
                35
            ],
            // Qualité eau potable. Poids 25%
            [($r->qualite_eau === true ? 100 : ($r->qualite_eau === false ? 0 : null)), 25],
            // Absence défécation à l'air libre. Poids 20%
            [($r->defecation_plein_air === false ? 100 : ($r->defecation_plein_air === true ? 0 : null)), 20],
            // Savon disponible (hygiène mains). Poids 12%
            [($r->savon_disponible === true ? 100 : ($r->savon_disponible === false ? 0 : null)), 12],
            // WASH adapté handicapés. Poids 5%
            [($r->wash_adapte_handicapes === true ? 100 : ($r->wash_adapte_handicapes === false ? 20 : null)), 5],
            // Latrines présentes. Poids 3%
            [(($r->nb_latrines ?? 0) > 0 ? 100 : 0), 3],
        ];
        $scoreWash = $ws($scoreWashItems);

        /* ══════════════════════════════════════════════
           SCORE SANTÉ  (Sphere 2018 + UNHCR Health)
           ══════════════════════════════════════════════ */
        $scoreSanteItems = [
            // Prestataire de soins fonctionnel. Poids 30%
            [($r->soin_sante_fonctionnel === true ? 100 : ($r->soin_sante_fonctionnel === false ? 0 : null)), 30],
            // Soins à l'intérieur du site. Poids 20%
            [($r->soin_sante_interieur === true ? 100 : ($r->soin_sante_interieur === false ? 30 : null)), 20],
            // Distance prestataire (UNHCR: <5km). Poids 20%
            [$distScore($r->distance_soin_sante, 2, 5, 10), 20],
            // Enfants non vaccinés (absence = bon). Poids 15%
            [($r->enfants_non_vaccines === false ? 100 : ($r->enfants_non_vaccines === true ? 0 : null)), 15],
            // Services urgences disponibles. Poids 10%
            [($r->services_urgences === true ? 100 : ($r->services_urgences === false ? 10 : null)), 10],
            // Ambulance disponible. Poids 5%
            [($r->ambulance === true ? 100 : ($r->ambulance === false ? 20 : null)), 5],
        ];
        $scoreSante = $ws($scoreSanteItems);

        /* ══════════════════════════════════════════════
           SCORE ÉDUCATION  (INEE Minimum Standards 2010 + Sphere)
           ══════════════════════════════════════════════ */
        $totalEnfants1217 = $pm
            ? (int)($pm->h_6_17 ?? 0) + (int)($pm->f_6_17 ?? 0)
            : (int)($r->h_5_17 ?? 0) + (int)($r->f_5_17 ?? 0);

        $tauxScol = null;
        if ($totalEnfants1217 > 0 && ($r->nb_enfants_scolarises ?? null) !== null) {
            $taux = min(100, round(($r->nb_enfants_scolarises / $totalEnfants1217) * 100));
            $tauxScol = $taux >= 80 ? 100 : ($taux >= 60 ? 70 : ($taux >= 40 ? 40 : 15));
        }

        $scoreEducItems = [
            // École primaire présente. Poids 35%
            [($r->ecole_primaire_presente === true ? 100 : ($r->ecole_primaire_presente === false ? 5 : null)), 35],
            // Distance école primaire (INEE: ≤3km). Poids 20%
            [$distScore($r->distance_ecole_primaire, 1, 3, 7), 20],
            // École secondaire. Poids 20%
            [($r->ecole_secondaire_presente === true ? 100 : ($r->ecole_secondaire_presente === false ? 5 : null)), 20],
            // Taux de scolarisation. Poids 15%
            [$tauxScol, 15],
            // Éducation informelle (filet de sécurité). Poids 10%
            [($r->education_informelle === true ? 70 : ($r->education_informelle === false ? 20 : null)), 10],
        ];
        $scoreEduc = $ws($scoreEducItems);

        /* ══════════════════════════════════════════════
           SCORE INFRASTRUCTURE (Sphere Shelter + HTF Site Planning)
           ══════════════════════════════════════════════ */
        $risqueScore = null;
        if ($r->risque_inondation !== null) {
            $rv = mb_strtolower(trim((string)$r->risque_inondation));
            if (in_array($rv, ['aucun','non','faible','low','none'])) $risqueScore = 100;
            elseif (in_array($rv, ['moyen','modéré','moderate','medium'])) $risqueScore = 45;
            elseif (in_array($rv, ['élevé','haut','high','fort','severe'])) $risqueScore = 5;
        }

        $scoreInfraItems = [
            // État routes. Poids 30%
            [$etatScore($r->etat_routes), 30],
            // État parcelles. Poids 25%
            [$etatScore($r->etat_parcelles), 25],
            // État canaux drainage. Poids 15%
            [$etatScore($r->etat_canaux), 15],
            // Risque inondation (inversé). Poids 20%
            [$risqueScore, 20],
            // Mesures prévention incendie. Poids 10%
            [($r->mesures_incendie === true ? 100 : ($r->mesures_incendie === false ? 0 : null)), 10],
        ];
        $scoreInfra = $ws($scoreInfraItems);

        /* ── score global (uniquement domaines avec données) ── */
        $domaines = array_filter([$scoreWash, $scoreSante, $scoreEduc, $scoreInfra], fn($v) => $v !== null);
        $scoreGlobal = count($domaines) > 0 ? round(array_sum($domaines) / count($domaines)) : null;

        /* ── labels & couleurs ── */
        $ratingLabel = function($s) {
            if ($s === null) return ['—', 'gray', 'Données insuffisantes', ''];
            if ($s >= 80) return ['Adéquat',              'green',  'Répond aux standards internationaux',                'bg-green-500'];
            if ($s >= 60) return ['Partiellement adéquat','yellow', 'En dessous des standards, améliorations requises',   'bg-yellow-400'];
            if ($s >= 40) return ['Insuffisant',          'orange', 'Lacunes importantes, intervention prioritaire',      'bg-orange-500'];
            return           ['Critique',              'red',    'Non conforme aux standards minimaux, urgence humanitaire','bg-red-600'];
        };
        $ratingBg = ['green'=>'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-700','yellow'=>'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-700','orange'=>'bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-700','red'=>'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-700','gray'=>'bg-gray-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600'];
        $ratingText = ['green'=>'text-green-700 dark:text-green-300','yellow'=>'text-yellow-700 dark:text-yellow-300','orange'=>'text-orange-700 dark:text-orange-300','red'=>'text-red-700 dark:text-red-300','gray'=>'text-gray-500 dark:text-gray-400'];
        $ratingDot = ['green'=>'bg-green-500','yellow'=>'bg-yellow-400','orange'=>'bg-orange-500','red'=>'bg-red-600','gray'=>'bg-gray-400'];
        @endphp

        <div class="mb-6 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
            {{-- en-tête ──────────────────── --}}
            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-700/60">
                <h4 class="text-sm font-bold text-gray-700 dark:text-gray-200 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                    Évaluation de la qualité des services
                </h4>
                <div class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
                    <span>Sphere 2018</span><span>·</span><span>INEE</span><span>·</span><span>UNHCR</span><span>·</span><span>WHO</span>
                </div>
            </div>

            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                @foreach([
                    ['💧 WASH',         $scoreWash,  'Normes Sphere 2018 (≥15L/pers/j, eau potable, assainissement)'],
                    ['🏥 Santé',        $scoreSante, 'Sphere 2018 + UNHCR (prestataire fonctionnel, ≤5km, vaccination)'],
                    ['📚 Éducation',    $scoreEduc,  'INEE Minimum Standards (école primaire ≤3km, taux scolarisation)'],
                    ['🛤️ Infrastructure',$scoreInfra, 'Sphere Site Planning + HTF (routes, drainage, risque inondation)'],
                ] as [$label, $score, $ref])
                @php [$rl, $rc, $rdesc, $rbar] = $ratingLabel($score); @endphp
                <div class="rounded-lg border p-3 {{ $ratingBg[$rc] }}">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $label }}</span>
                        <span class="text-xs font-bold {{ $ratingText[$rc] }}">
                            {{ $score !== null ? $score.'%' : '—' }}
                        </span>
                    </div>
                    {{-- barre de progression ──────── --}}
                    <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full mb-2">
                        @if($score !== null)
                        <div class="h-2 rounded-full {{ $ratingDot[$rc] }} transition-all" style="width:{{ $score }}%"></div>
                        @endif
                    </div>
                    <div class="flex items-center gap-1.5 mb-1">
                        <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $ratingDot[$rc] }}"></span>
                        <span class="text-xs font-semibold {{ $ratingText[$rc] }}">{{ $rl }}</span>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 leading-tight">{{ $ref }}</p>
                </div>
                @endforeach
            </div>

            {{-- Score global ──────────────────── --}}
            @if($scoreGlobal !== null)
            @php [$grl, $grc, $grdesc, $grbar] = $ratingLabel($scoreGlobal); @endphp
            <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3 flex flex-col sm:flex-row items-start sm:items-center gap-3">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wide">Score global</span>
                        <span class="text-lg font-bold {{ $ratingText[$grc] }}">{{ $scoreGlobal }}%</span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold
                            {{ $grc==='green'  ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' : '' }}
                            {{ $grc==='yellow' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300' : '' }}
                            {{ $grc==='orange' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300' : '' }}
                            {{ $grc==='red'    ? 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300' : '' }}">
                            {{ $grl }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $grdesc }}</p>
                </div>
                <div class="w-full sm:w-48">
                    <div class="w-full h-3 bg-gray-200 dark:bg-gray-700 rounded-full">
                        <div class="h-3 rounded-full {{ $grbar }} transition-all" style="width:{{ $scoreGlobal }}%"></div>
                    </div>
                </div>
            </div>
            @endif

            {{-- légende ──────────────────── --}}
            <div class="border-t border-gray-100 dark:border-gray-700/50 px-4 py-2 flex flex-wrap gap-x-4 gap-y-1">
                @foreach([
                    ['bg-green-500','≥ 80% — Adéquat'],
                    ['bg-yellow-400','60–79% — Partiellement adéquat'],
                    ['bg-orange-500','40–59% — Insuffisant'],
                    ['bg-red-600','< 40% — Critique'],
                ] as [$dot,$lbl])
                <span class="inline-flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
                    <span class="w-2.5 h-2.5 rounded-full {{ $dot }}"></span>{{ $lbl }}
                </span>
                @endforeach
            </div>
        </div>

        {{-- ═══════════ SECTION 3 : Besoins prioritaires ═══════════ --}}
        @if($r->besoin_prioritaire_1 || $r->besoin_prioritaire_2 || $r->besoin_prioritaire_3)
        <div class="mb-4">
            <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Besoins prioritaires identifiés</h4>
            <div class="flex flex-wrap gap-2">
                @foreach(array_filter([$r->besoin_prioritaire_1, $r->besoin_prioritaire_2, $r->besoin_prioritaire_3]) as $k => $besoin)
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 border border-red-200 dark:border-red-800">
                    <span class="w-5 h-5 flex items-center justify-center bg-red-600 text-white rounded-full text-xs font-bold flex-shrink-0">{{ $k + 1 }}</span>
                    {{ $besoin }}
                </span>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ═══════════ SECTION 4 : Partenaires présents ═══════════ --}}
        @php
            $partenaires = array_filter([
                $r->partenaires_protection_presence   ? 'Protection'    : null,
                $r->partenaires_gbv_presence          ? 'GBV'           : null,
                $r->partenaires_enfance_presence      ? 'Protection enfance' : null,
                $r->partenaires_education_presence    ? 'Éducation'     : null,
                $r->partenaires_abri_presence         ? 'Abri'          : null,
                $r->partenaires_eau_presence          ? 'Eau'           : null,
                $r->partenaires_assainissement_presence ? 'Assainissement' : null,
                $r->partenaires_sante_primaire_presence ? 'Santé primaire' : null,
                $r->partenaires_sante_secondaire_presence ? 'Santé secondaire' : null,
                $r->partenaires_mhpss_presence        ? 'MHPSS'         : null,
                $r->partenaires_nutrition_presence    ? 'Nutrition'      : null,
                $r->partenaires_alimentaire_presence  ? 'Aide alimentaire' : null,
                $r->partenaires_subsistance_presence  ? 'Subsistance'   : null,
                $r->partenaires_cohesion_presence     ? 'Cohésion sociale' : null,
            ]);
        @endphp
        @if(count($partenaires) > 0)
        <div>
            <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Secteurs couverts par des partenaires</h4>
            <div class="flex flex-wrap gap-1.5">
                @foreach($partenaires as $p)
                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-700">
                    {{ $p }}
                </span>
                @endforeach
            </div>
        </div>
        @endif
    @endif
</div>
