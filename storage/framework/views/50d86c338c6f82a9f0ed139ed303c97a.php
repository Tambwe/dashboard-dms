

<?php $__env->startSection('title', 'Tableau de bord'); ?>
<?php $__env->startSection('subtitle', 'Suivi des personnes déplacées internes en République Démocratique du Congo'); ?>

<?php $__env->startSection('content'); ?>
<style>
@page { size: A4 portrait; margin: 1.5cm; }
@page landscape-page { size: A4 landscape; margin: 1cm; }

/* Layout des cartes démographiques : 4 colonnes compactes */
#demographics-section .stat-card {
    padding: 0.85rem !important;
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    gap: 0.85rem !important;
}
#demographics-section .stat-icon {
    width: 2.75rem !important;
    height: 2.75rem !important;
    flex-shrink: 0 !important;
    margin-bottom: 0 !important;
}
#demographics-section .stat-icon svg {
    width: 1.2rem !important;
    height: 1.2rem !important;
}
#demographics-section .stat-value {
    font-size: 1.25rem !important;
    line-height: 1.2 !important;
}
#demographics-section .stat-content {
    width: 100%;
    min-width: 0;
}

@media print {
    #demographics-section { page: landscape-page; }
    #demographics-section .stat-card {
        padding: 0.4rem 0.6rem !important;
        gap: 0.5rem !important;
        flex-direction: row !important;
    }
    #demographics-section .stat-value {
        font-size: 0.9rem !important;
    }
    #demographics-section .stat-icon {
        width: 1.75rem !important;
        height: 1.75rem !important;
    }
    #demographics-section .stat-icon svg {
        width: 0.9rem !important;
        height: 0.9rem !important;
    }
    /* Ne pas imprimer le menu */
    #sidebar,
    aside,
    nav {
        display: none !important;
    }

    /* Reprendre toute la largeur quand le menu est masqué */
    main,
    #main-content,
    .ml-64,
    .pl-64 {
        margin-left: 0 !important;
        padding-left: 0 !important;
    }

    .print-only { display: block !important; }

    /* Masquer les boutons d'action uniquement à l'impression */
    #filters-section,
    #dashboard-action-buttons,
    #reset-filters,
    .filter-button,
    .primary-button,
    #reset-view {
        display: none !important;
    }

    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}

.print-only {
    display: none;
}
</style>
<?php
    $formatDelta = function($delta) {
        $value = $delta['value'] ?? 0;
        $percent = $delta['percent'] ?? null;

        if ($value > 0) {
            return '↑ Différence: +' . number_format($value, 0, ',', ' ') . ($percent !== null ? ' (+' . $percent . '%)' : '') . ' vs mois précédent';
        }

        if ($value < 0) {
            return '↓ Différence: ' . number_format($value, 0, ',', ' ') . ($percent !== null ? ' (' . $percent . '%)' : '') . ' vs mois précédent';
        }

        return '→ Différence: 0 vs mois précédent';
    };

    $deltaClass = function($delta) {
        $value = $delta['value'] ?? 0;
        if ($value > 0) return 'text-xs text-green-600 dark:text-green-400 mt-1';
        if ($value < 0) return 'text-xs text-red-600 dark:text-red-400 mt-1';
        return 'text-xs text-gray-500 dark:text-gray-400 mt-1';
    };
?>
<div class="space-y-6">
    <!-- Filters Section -->
    <div id="filters-section" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filtres les données
            </h3>
            <button id="reset-filters" class="text-sm text-primary-600 dark:text-primary-400 hover:underline font-medium">
                Réinitialiser
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Province -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Province</label>
                <select id="province-select" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                    <option value="">Sélectionnez une province</option>
                </select>
            </div>

            <!-- Territoire -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Territoire</label>
                <select id="territoire-select" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500" disabled>
                    <option value="">Sélectionnez d'abord une province</option>
                </select>
            </div>

            <!-- Zone de santé -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Zone de santé</label>
                <select id="commune-select" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500" disabled>
                    <option value="">Sélectionnez d'abord un territoire</option>
                </select>
            </div>

            <!-- Site -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Site</label>
                <select id="site-select" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500" disabled>
                    <option value="">Sélectionnez d'abord une zone de santé</option>
                </select>
            </div>

            <!-- Coordinateur -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Coordinateur</label>
                <select id="coordinateur-select" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                    <option value="">Chargement...</option>
                </select>
            </div>

            <!-- Gestionnaire -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gestionnaire</label>
                <select id="gestionnaire-select" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                    <option value="">Chargement...</option>
                </select>
            </div>

            <!-- Mécanisme CCCM -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Mécanisme CCCM</label>
                <select id="categorie-site-select" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                    <option value="">Chargement...</option>
                </select>
            </div>

            <!-- Date -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Situation au</label>
                <input
                    type="text"
                    id="periode-select"
                    value="<?php echo e($selectedPeriod); ?>"
                    inputmode="numeric"
                    placeholder="mm/aaaa"
                    pattern="^(0[1-9]|1[0-2])\/\d{4}$"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500"
                >
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div id="dashboard-action-buttons" class="flex items-center space-x-3">
        <button class="filter-button">
            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Actualiser
        </button>
        <button class="primary-button">
            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Tableau de bord
        </button>
        <a href="<?php echo e(url('/about')); ?>" class="filter-button">
            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            A propos
        </a>
        <button id="print-dashboard-btn" onclick="window.print()" class="filter-button">
            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Imprimer
        </button>
    </div>

    <div class="print-only bg-white rounded-lg border border-gray-300 p-4" id="print-filter-summary">
        <h4 class="text-base font-semibold text-gray-900 mb-3">Synthèse des filtres</h4>
        <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm text-gray-700">
            <div><span class="font-medium">Province:</span> <span id="print-filter-province">Toutes</span></div>
            <div><span class="font-medium">Territoire:</span> <span id="print-filter-territoire">Tous</span></div>
            <div><span class="font-medium">Zone de santé:</span> <span id="print-filter-commune">Toutes</span></div>
            <div><span class="font-medium">Site:</span> <span id="print-filter-site">Tous</span></div>
            <div><span class="font-medium">Coordinateur:</span> <span id="print-filter-coordinateur">Tous</span></div>
            <div><span class="font-medium">Gestionnaire:</span> <span id="print-filter-gestionnaire">Tous</span></div>
            <div><span class="font-medium">Mécanisme CCCM:</span> <span id="print-filter-categorie">Tous</span></div>
            <div><span class="font-medium">Situation au:</span> <span id="print-filter-periode"><?php echo e($selectedPeriod); ?></span></div>
        </div>
        <p class="mt-3 text-xs text-gray-500">Généré le <span id="print-generated-at"></span></p>
    </div>

    <!-- Map Section -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6" id="map-section">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                </svg>
                Localisation géographique des sites (Nord-Kivu, RDC)
            </h3>
            <div class="flex items-center space-x-2">
                <span id="site-count" class="text-sm text-gray-600 dark:text-gray-400">Chargement...</span>
                <button id="reset-view" class="text-sm text-primary-600 dark:text-primary-400 hover:underline font-medium">
                    Recentrer
                </button>
            </div>
        </div>
        <div id="dashboard-map" class="rounded-lg h-96 border border-gray-200 dark:border-gray-700" style="height: 500px;"></div>
    </div>

    <!-- Lien vers Master List -->
    <div id="master-list-section" class="hidden bg-gradient-to-r from-primary-50 to-blue-50 dark:from-primary-900/20 dark:to-blue-900/20 rounded-lg shadow-md p-8 border border-primary-200 dark:border-primary-800">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <div class="flex items-center mb-3">
                    <svg class="w-8 h-8 text-primary-600 dark:text-primary-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Master List des Sites</h3>
                </div>
                <p class="text-gray-700 dark:text-gray-300 mb-4">
                    Consultez la liste complète de tous les sites avec les variations mensuelles, recherche avancée et export Excel
                </p>
                <div class="flex flex-wrap gap-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 shadow-sm">
                        <svg class="w-4 h-4 mr-1.5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Variations mensuelles
                    </span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 shadow-sm">
                        <svg class="w-4 h-4 mr-1.5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Recherche avancée
                    </span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 shadow-sm">
                        <svg class="w-4 h-4 mr-1.5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Export Excel
                    </span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 shadow-sm">
                        <svg class="w-4 h-4 mr-1.5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Pagination
                    </span>
                </div>
            </div>
            <div class="ml-8">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                    <a href="<?php echo e(route('sites.master-list')); ?>" class="inline-flex items-center px-8 py-4 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-lg shadow-lg transition-all transform hover:scale-105">
                        <span class="text-lg">Voir la Master List</span>
                        <svg class="w-6 h-6 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                <?php else: ?>
                    <a href="<?php echo e(route('login')); ?>" class="inline-flex items-center px-8 py-4 bg-gray-600 hover:bg-gray-700 text-white font-bold rounded-lg shadow-lg transition-all">
                        <span class="text-lg">Se connecter</span>
                        <svg class="w-6 h-6 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                    </a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Statistics Section -->
    <div id="demographics-section" class="bg-white dark:bg-gray-800 shadow-md p-6 -mx-8 px-8">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Profil démographique de la population dans les sites
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Données sexe, âge et personnes vivantes avec handicap enregistrés</p>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Total PDI -->
            <div class="stat-card">
                <div class="stat-icon bg-primary-100 dark:bg-primary-900/30">
                    <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="stat-total-pdi"><?php echo e(number_format($stats['total_pdi'])); ?></div>
                    <div class="<?php echo e($deltaClass($deltas['total_pdi'] ?? [])); ?>" id="stat-total-pdi-delta"><?php echo e($formatDelta($deltas['total_pdi'] ?? [])); ?></div>
                    <div class="stat-label">Total PDI</div>
                </div>
            </div>

            <!-- Hommes -->
            <div class="stat-card">
                <div class="stat-icon bg-indigo-100 dark:bg-indigo-900/30">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="stat-hommes"><?php echo e(number_format($stats['hommes'])); ?></div>
                    <div class="<?php echo e($deltaClass($deltas['hommes'] ?? [])); ?>" id="stat-hommes-delta"><?php echo e($formatDelta($deltas['hommes'] ?? [])); ?></div>
                    <div class="stat-label">Hommes</div>
                </div>
            </div>

            <!-- Femmes -->
            <div class="stat-card">
                <div class="stat-icon bg-pink-100 dark:bg-pink-900/30">
                    <svg class="w-6 h-6 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="stat-femmes"><?php echo e(number_format($stats['femmes'])); ?></div>
                    <div class="<?php echo e($deltaClass($deltas['femmes'] ?? [])); ?>" id="stat-femmes-delta"><?php echo e($formatDelta($deltas['femmes'] ?? [])); ?></div>
                    <div class="stat-label">Femmes</div>
                </div>
            </div>

            <!-- Personnes vivantes avec handicap -->
            <div class="stat-card">
                <div class="stat-icon bg-purple-100 dark:bg-purple-900/30">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="stat-handicap"><?php echo e(number_format($stats['personnes_handicap'])); ?></div>
                    <div class="<?php echo e($deltaClass($deltas['personnes_handicap'] ?? [])); ?>" id="stat-handicap-delta"><?php echo e($formatDelta($deltas['personnes_handicap'] ?? [])); ?></div>
                    <div class="stat-label">Personnes vivantes avec handicap</div>
                </div>
            </div>

            <!-- Ménages -->
            <div class="stat-card">
                <div class="stat-icon bg-green-100 dark:bg-green-900/30">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="stat-menages"><?php echo e(number_format($stats['menages'])); ?></div>
                    <div class="<?php echo e($deltaClass($deltas['menages'] ?? [])); ?>" id="stat-menages-delta"><?php echo e($formatDelta($deltas['menages'] ?? [])); ?></div>
                    <div class="stat-label">Ménages</div>
                </div>
            </div>

            <!-- Enfants -->
            <div class="stat-card">
                <div class="stat-icon bg-yellow-100 dark:bg-yellow-900/30">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="stat-enfants"><?php echo e(number_format($stats['enfants'])); ?></div>
                    <div class="<?php echo e($deltaClass($deltas['enfants'] ?? [])); ?>" id="stat-enfants-delta"><?php echo e($formatDelta($deltas['enfants'] ?? [])); ?></div>
                    <div class="stat-label">Enfants</div>
                </div>
            </div>

            <!-- Adultes -->
            <div class="stat-card">
                <div class="stat-icon bg-orange-100 dark:bg-orange-900/30">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="stat-adultes"><?php echo e(number_format($stats['adultes'])); ?></div>
                    <div class="<?php echo e($deltaClass($deltas['adultes'] ?? [])); ?>" id="stat-adultes-delta"><?php echo e($formatDelta($deltas['adultes'] ?? [])); ?></div>
                    <div class="stat-label">Adultes</div>
                </div>
            </div>

            <!-- Personnes âgées -->
            <div class="stat-card">
                <div class="stat-icon bg-gray-100 dark:bg-gray-700">
                    <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="stat-ages"><?php echo e(number_format($stats['personnes_agees'])); ?></div>
                    <div class="<?php echo e($deltaClass($deltas['personnes_agees'] ?? [])); ?>" id="stat-ages-delta"><?php echo e($formatDelta($deltas['personnes_agees'] ?? [])); ?></div>
                    <div class="stat-label">Personnes âgées</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Age Distribution Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Répartition de la population par tranche d'âge</h3>
            <div id="ageChart" class="h-80"></div>
        </div>

        <!-- Gender Distribution Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Distribution de la population par sexe</h3>
            <div id="genderChart" class="h-80"></div>
        </div>
    </div>

    <!-- Province Distribution Chart -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Distribution de la population dans les provinces touchées par la crise</h3>
        <div id="provinceChart" class="h-96"></div>
    </div>

    <!-- Trend Chart -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Tendances de déplacement de la population</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Les entrées, sorties, naissances et décès mensuelles des personnes déplacées les 12 derniers mois</p>
        <div id="trendChart" class="h-96"></div>
    </div>
</div>

<!-- Charts Initialization Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Age Distribution Chart avec données PHP
        const ageChart = new ApexCharts(document.querySelector("#ageChart"), {
            series: [{
                name: 'Population',
                data: [
                    <?php echo e($ageDistribution['0-5 ans'] ?? 0); ?>,
                    <?php echo e($ageDistribution['6-17 ans'] ?? 0); ?>,
                    <?php echo e($ageDistribution['18-59 ans'] ?? 0); ?>,
                    <?php echo e($ageDistribution['60+ ans'] ?? 0); ?>,
                    <?php echo e($ageDistribution['Non spécifié'] ?? 0); ?>

                ]
            }],
            chart: {
                type: 'bar',
                height: 320,
                background: 'transparent',
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    borderRadius: 8,
                }
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: ['0-5 ans', '6-17 ans', '18-59 ans', '60+ ans', 'Non spécifié'],
                labels: {
                    style: {
                        colors: document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#6B7280'
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#6B7280'
                    }
                }
            },
            colors: ['#3B82F6'],
            theme: {
                mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
            }
        });
        ageChart.render();

        // Gender Distribution Chart avec données PHP
        const genderChart = new ApexCharts(document.querySelector("#genderChart"), {
            series: [<?php echo e($stats['femmes']); ?>, <?php echo e($stats['hommes']); ?>],
            chart: {
                type: 'donut',
                height: 320,
                background: 'transparent',
            },
            labels: ['Femmes', 'Hommes'],
            colors: ['#EC4899', '#3B82F6'],
            legend: {
                position: 'bottom',
                labels: {
                    colors: document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#6B7280'
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return val.toFixed(0) + "%"
                }
            },
            theme: {
                mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
            }
        });
        genderChart.render();

        // Province Distribution Chart avec données PHP
        const provinceChartData = <?php echo json_encode($provinceDistribution, 15, 512) ?>;
        const provinceNames = Object.keys(provinceChartData);
        const provinceValues = Object.values(provinceChartData);
        
        const provinceChart = new ApexCharts(document.querySelector("#provinceChart"), {
            series: [{
                name: 'Population',
                data: provinceValues
            }],
            chart: {
                type: 'bar',
                height: 384,
                background: 'transparent',
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 8,
                }
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: provinceNames,
                labels: {
                    style: {
                        colors: document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#6B7280'
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#6B7280'
                    }
                }
            },
            colors: ['#8B5CF6'],
            theme: {
                mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
            }
        });
        provinceChart.render();

        // Trend Chart - Charger via API
        let trendChart;
        
        function initTrendChart() {
            fetch('/api/dashboard/trends')
                .then(response => response.json())
                .then(data => {
                    trendChart = new ApexCharts(document.querySelector("#trendChart"), {
                        series: [
                            {
                                name: 'Entrées',
                                data: data.entrees
                            },
                            {
                                name: 'Sorties',
                                data: data.sorties
                            },
                            {
                                name: 'Naissances',
                                data: data.naissances
                            },
                            {
                                name: 'Décès',
                                data: data.deces
                            }
                        ],
                        chart: {
                            type: 'line',
                            height: 384,
                            background: 'transparent',
                            toolbar: {
                                show: false
                            }
                        },
                        stroke: {
                            curve: 'smooth',
                            width: 3
                        },
                        xaxis: {
                            categories: data.months,
                            labels: {
                                style: {
                                    colors: document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#6B7280'
                                }
                            }
                        },
                        yaxis: {
                            labels: {
                                style: {
                                    colors: document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#6B7280'
                                }
                            }
                        },
                        colors: ['#10B981', '#EF4444', '#3B82F6', '#F59E0B'],
                        legend: {
                            position: 'top',
                            labels: {
                                colors: document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#6B7280'
                            }
                        },
                        theme: {
                            mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                        }
                    });
                    trendChart.render();
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des tendances:', error);
                });
        }
        
        // Initialiser le graphique de tendances
        initTrendChart();

        // ===== Gestion des sélecteurs géographiques en cascade =====
        const provinceSelect = document.getElementById('province-select');
        const territoireSelect = document.getElementById('territoire-select');
        const communeSelect = document.getElementById('commune-select');
        const siteSelect = document.getElementById('site-select');
        const coordinateurSelect = document.getElementById('coordinateur-select');
        const gestionnaireSelect = document.getElementById('gestionnaire-select');
        const categorieSiteSelect = document.getElementById('categorie-site-select');
        const periodeSelect = document.getElementById('periode-select');
        const defaultPeriodValue = <?php echo json_encode($selectedPeriod, 15, 512) ?>;

        const printSummaryEls = {
            province: document.getElementById('print-filter-province'),
            territoire: document.getElementById('print-filter-territoire'),
            commune: document.getElementById('print-filter-commune'),
            site: document.getElementById('print-filter-site'),
            coordinateur: document.getElementById('print-filter-coordinateur'),
            gestionnaire: document.getElementById('print-filter-gestionnaire'),
            categorie: document.getElementById('print-filter-categorie'),
            periode: document.getElementById('print-filter-periode'),
            generatedAt: document.getElementById('print-generated-at'),
        };

        function getSelectedLabel(selectEl, fallback) {
            if (!selectEl) return fallback;
            const selected = selectEl.options[selectEl.selectedIndex];
            if (!selected) return fallback;
            const text = (selected.textContent || '').trim();
            if (!selectEl.value || !text || text.toLowerCase().includes('chargement') || text.toLowerCase().includes('sélectionnez')) {
                return fallback;
            }
            return text;
        }

        function updatePrintFilterSummary() {
            if (printSummaryEls.province) printSummaryEls.province.textContent = getSelectedLabel(provinceSelect, 'Toutes');
            if (printSummaryEls.territoire) printSummaryEls.territoire.textContent = getSelectedLabel(territoireSelect, 'Tous');
            if (printSummaryEls.commune) printSummaryEls.commune.textContent = getSelectedLabel(communeSelect, 'Toutes');
            if (printSummaryEls.site) printSummaryEls.site.textContent = getSelectedLabel(siteSelect, 'Tous');
            if (printSummaryEls.coordinateur) printSummaryEls.coordinateur.textContent = getSelectedLabel(coordinateurSelect, 'Tous');
            if (printSummaryEls.gestionnaire) printSummaryEls.gestionnaire.textContent = getSelectedLabel(gestionnaireSelect, 'Tous');
            if (printSummaryEls.categorie) printSummaryEls.categorie.textContent = getSelectedLabel(categorieSiteSelect, 'Tous');

            const rawPeriodValue = (periodeSelect?.value || '').trim();
            const periodValue = rawPeriodValue || defaultPeriodValue || 'N/A';
            if (printSummaryEls.periode) printSummaryEls.periode.textContent = periodValue;
            if (printSummaryEls.generatedAt) {
                printSummaryEls.generatedAt.textContent = new Date().toLocaleString('fr-FR');
            }
        }

        [provinceSelect, territoireSelect, communeSelect, siteSelect, coordinateurSelect, gestionnaireSelect, categorieSiteSelect, periodeSelect]
            .forEach((el) => el?.addEventListener('change', updatePrintFilterSummary));
        window.addEventListener('beforeprint', updatePrintFilterSummary);
        updatePrintFilterSummary();

        const normalizePeriodInput = (value) => {
            if (!value) return '';

            const raw = value.trim();
            if (/^(0[1-9]|1[0-2])\/\d{4}$/.test(raw)) {
                return raw;
            }

            if (/^\d{6}$/.test(raw)) {
                return `${raw.slice(0, 2)}/${raw.slice(2)}`;
            }

            if (/^(0[1-9]|1[0-2])[- ]\d{4}$/.test(raw)) {
                return raw.replace(/[- ]/, '/');
            }

            return raw;
        };

        // Charger les provinces au chargement de la page
        loadProvinces();
        
        // Charger les coordinateurs et gestionnaires au chargement de la page
        loadCoordinateurs();
        loadGestionnaires();
        loadCategoriesSites();

        // Écouteur pour le changement de province
        provinceSelect.addEventListener('change', function() {
            const provinceId = this.value;
            
            // Réinitialiser les sélecteurs dépendants
            territoireSelect.innerHTML = '<option value="">Chargement...</option>';
            territoireSelect.disabled = true;
            communeSelect.innerHTML = '<option value="">Sélectionnez d\'abord un territoire</option>';
            communeSelect.disabled = true;
            siteSelect.innerHTML = '<option value="">Sélectionnez d\'abord une zone de santé</option>';
            siteSelect.disabled = true;

            if (provinceId) {
                loadTerritoires(provinceId);
            } else {
                territoireSelect.innerHTML = '<option value="">Sélectionnez d\'abord une province</option>';
            }
        });

        // Écouteur pour le changement de territoire
        territoireSelect.addEventListener('change', function() {
            const territoireId = this.value;
            
            // Réinitialiser les sélecteurs dépendants
            communeSelect.innerHTML = '<option value="">Chargement...</option>';
            communeSelect.disabled = true;
            siteSelect.innerHTML = '<option value="">Sélectionnez d\'abord une zone de santé</option>';
            siteSelect.disabled = true;

            if (territoireId) {
                loadCommunes(territoireId);
            } else {
                communeSelect.innerHTML = '<option value="">Sélectionnez d\'abord un territoire</option>';
            }
        });

        // Écouteur pour le changement de zone de santé (commune)
        communeSelect.addEventListener('change', function() {
            const communeId = this.value;
            
            // Réinitialiser le sélecteur de sites
            siteSelect.innerHTML = '<option value="">Chargement...</option>';
            siteSelect.disabled = true;

            if (communeId) {
                loadSites(communeId);
            } else {
                siteSelect.innerHTML = '<option value="">Sélectionnez d\'abord une zone de santé</option>';
            }
        });

        // Fonction pour charger les provinces
        function loadProvinces() {
            fetch('/api/geographic/provinces')
                .then(response => response.json())
                .then(data => {
                    provinceSelect.innerHTML = '<option value="">Sélectionnez une province</option>';
                    data.forEach(province => {
                        const option = document.createElement('option');
                        option.value = province.id;
                        option.textContent = province.name;
                        provinceSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des provinces:', error);
                    provinceSelect.innerHTML = '<option value="">Erreur de chargement</option>';
                });
        }

        // Fonction pour charger les territoires d'une province
        function loadTerritoires(provinceId) {
            fetch(`/api/geographic/territoires?province_id=${provinceId}`)
                .then(response => response.json())
                .then(data => {
                    territoireSelect.innerHTML = '<option value="">Sélectionnez un territoire</option>';
                    
                    if (data.length === 0) {
                        territoireSelect.innerHTML = '<option value="">Aucun territoire disponible</option>';
                        territoireSelect.disabled = true;
                    } else {
                        data.forEach(territoire => {
                            const option = document.createElement('option');
                            option.value = territoire.id;
                            option.textContent = territoire.name;
                            territoireSelect.appendChild(option);
                        });
                        territoireSelect.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des territoires:', error);
                    territoireSelect.innerHTML = '<option value="">Erreur de chargement</option>';
                    territoireSelect.disabled = true;
                });
        }

        // Fonction pour charger les communes (zones de santé) d'un territoire
        function loadCommunes(territoireId) {
            fetch(`/api/geographic/communes?territoire_id=${territoireId}`)
                .then(response => response.json())
                .then(data => {
                    communeSelect.innerHTML = '<option value="">Sélectionnez une zone de santé</option>';
                    
                    if (data.length === 0) {
                        communeSelect.innerHTML = '<option value="">Aucune zone de santé disponible</option>';
                        communeSelect.disabled = true;
                    } else {
                        data.forEach(commune => {
                            const option = document.createElement('option');
                            option.value = commune.id;
                            option.textContent = commune.name;
                            communeSelect.appendChild(option);
                        });
                        communeSelect.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des zones de santé:', error);
                    communeSelect.innerHTML = '<option value="">Erreur de chargement</option>';
                    communeSelect.disabled = true;
                });
        }

        // Fonction pour charger les sites d'une zone de santé (commune)
        function loadSites(communeId) {
            fetch(`/api/geographic/sites?commune_id=${communeId}`)
                .then(response => response.json())
                .then(data => {
                    siteSelect.innerHTML = '<option value="">Sélectionnez un site</option>';
                    
                    if (data.length === 0) {
                        siteSelect.innerHTML = '<option value="">Aucun site disponible</option>';
                        siteSelect.disabled = true;
                    } else {
                        data.forEach(site => {
                            const option = document.createElement('option');
                            option.value = site.id;
                            option.textContent = site.nom + (site.code_site ? ` (${site.code_site})` : '');
                            siteSelect.appendChild(option);
                        });
                        siteSelect.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des sites:', error);
                    siteSelect.innerHTML = '<option value="">Erreur de chargement</option>';
                    siteSelect.disabled = true;
                });
        }

        // Fonction pour charger tous les coordinateurs
        function loadCoordinateurs() {
            fetch('/api/geographic/coordinateurs')
                .then(response => response.json())
                .then(data => {
                    coordinateurSelect.innerHTML = '<option value="">Tous les coordinateurs</option>';
                    data.forEach(coordinateur => {
                        const option = document.createElement('option');
                        option.value = coordinateur.id;
                        option.textContent = coordinateur.name;
                        coordinateurSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des coordinateurs:', error);
                    coordinateurSelect.innerHTML = '<option value="">Erreur de chargement</option>';
                });
        }

        // Fonction pour charger tous les gestionnaires
        function loadGestionnaires() {
            fetch('/api/geographic/gestionnaires')
                .then(response => response.json())
                .then(data => {
                    gestionnaireSelect.innerHTML = '<option value="">Tous les gestionnaires</option>';
                    data.forEach(gestionnaire => {
                        const option = document.createElement('option');
                        option.value = gestionnaire.id;
                        option.textContent = gestionnaire.name;
                        gestionnaireSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des gestionnaires:', error);
                    gestionnaireSelect.innerHTML = '<option value="">Erreur de chargement</option>';
                });
        }

        // Fonction pour charger toutes les catégories de sites (Mécanisme CCCM)
        function loadCategoriesSites() {
            fetch('/api/geographic/categories-sites')
                .then(response => response.json())
                .then(data => {
                    categorieSiteSelect.innerHTML = '<option value="">Toutes les catégories</option>';
                    data.forEach(categorie => {
                        const option = document.createElement('option');
                        option.value = categorie.id;
                        option.textContent = categorie.name;
                        categorieSiteSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des catégories:', error);
                    categorieSiteSelect.innerHTML = '<option value="">Erreur de chargement</option>';
                });
        }

        // ===== Initialisation de la carte Leaflet =====
        let dashboardMap = null;
        let currentSitesData = []; // Pour stocker les données des sites pour l'export
        let lastMapRequestId = 0;

        async function focusDashboardOnSite(site) {
            if (!site || !site.id) {
                return;
            }

            provinceSelect.value = '';
            territoireSelect.value = '';
            territoireSelect.disabled = true;
            territoireSelect.innerHTML = '<option value="">Sélectionnez d\'abord une province</option>';
            communeSelect.value = '';
            communeSelect.disabled = true;
            communeSelect.innerHTML = '<option value="">Sélectionnez d\'abord un territoire</option>';

            if (!siteSelect.querySelector(`option[value="${site.id}"]`)) {
                const option = document.createElement('option');
                option.value = site.id;
                option.textContent = site.nom + (site.code_site ? ` (${site.code_site})` : '');
                siteSelect.appendChild(option);
            }

            siteSelect.disabled = false;
            siteSelect.value = String(site.id);

            coordinateurSelect.value = '';
            gestionnaireSelect.value = '';
            categorieSiteSelect.value = '';

            try {
                const response = await fetch(`/api/dashboard/stats?site_id=${site.id}`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (periodeSelect && data?.periode && /^(0[1-9]|1[0-2])\/\d{4}$/.test(data.periode)) {
                        periodeSelect.value = data.periode;
                    }
                }
            } catch (error) {
                console.error('Erreur lors de la récupération de la dernière période du site:', error);
            }

            applyFilters();
        }
        
        // Initialiser la carte après un court délai pour s'assurer que le DOM est prêt
        setTimeout(() => {
            if (typeof DashboardMap !== 'undefined') {
                dashboardMap = new DashboardMap('dashboard-map');
                dashboardMap.setOnSiteClick(focusDashboardOnSite);
                
                // Initialiser la carte et charger les sites
                dashboardMap.init().then(() => {
                    applyFilters();
                });
            } else {
                console.error('DashboardMap n\'est pas défini. Vérifiez que map.js est chargé correctement.');
            }
        }, 500);

        // Fonction pour mettre à jour la carte avec les filtres actuels
        function updateMapWithFilters() {
            if (!dashboardMap) return;

            const requestId = ++lastMapRequestId;
            
            const filters = {
                province_id: provinceSelect.value || null,
                territoire_id: territoireSelect.value || null,
                commune_id: communeSelect.value || null,
                site_id: siteSelect.value || null,
                coordinateur_id: coordinateurSelect.value || null,
                gestionnaire_id: gestionnaireSelect.value || null,
                categorie_site_id: categorieSiteSelect.value || null,
                periode: normalizePeriodInput(periodeSelect?.value || '') || null
            };
            
            dashboardMap.loadSites(filters).then(sites => {
                if (requestId !== lastMapRequestId) {
                    return;
                }

                currentSitesData = sites; // Stocker pour l'export
                updateSiteCount(sites.length);
                updateSitesTable(sites);
            });
        }

        // Fonction pour mettre à jour le compteur de sites
        function updateSiteCount(count) {
            const siteCountEl = document.getElementById('site-count');
            const tableSiteCountEl = document.getElementById('table-site-count');
            
            if (siteCountEl) {
                if (count !== undefined) {
                    siteCountEl.textContent = `${count} site${count > 1 ? 's' : ''} affiché${count > 1 ? 's' : ''}`;
                } else {
                    siteCountEl.textContent = 'Chargement...';
                }
            }
            
            if (tableSiteCountEl) {
                tableSiteCountEl.textContent = count !== undefined ? count : '0';
            }
        }

        // Fonction pour mettre à jour le tableau des sites
        function updateSitesTable(sites) {
            const tableBody = document.getElementById('sites-table-body');
            
            if (!tableBody) return;
            
            // Effacer le contenu actuel
            tableBody.innerHTML = '';
            
            if (!sites || sites.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-sm font-medium">Aucun site trouvé</p>
                            <p class="text-xs mt-1">Modifiez vos critères de filtrage</p>
                        </td>
                    </tr>
                `;
                return;
            }
            
            // Ajouter une ligne pour chaque site
            sites.forEach(site => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors';
                
                const individus = site.individus === null || site.individus === undefined ? '-' : site.individus.toLocaleString('fr-FR');
                const menages = site.menages === null || site.menages === undefined ? '-' : site.menages.toLocaleString('fr-FR');
                const gestionnaire = site.gestionnaire ? site.gestionnaire.name : 'Non spécifié';
                
                row.innerHTML = `
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                        ${site.code_site || 'N/A'}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                        ${site.nom}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                        ${site.province || '-'}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                        ${site.territoire || '-'}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                        ${site.zone_sante || '-'}
                    </td>
                    <td class="px-4 py-3 text-sm text-right font-semibold text-primary-600 dark:text-primary-400">
                        ${individus}
                    </td>
                    <td class="px-4 py-3 text-sm text-right font-semibold text-green-600 dark:text-green-400">
                        ${menages}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                        ${gestionnaire}
                    </td>
                `;
                
                tableBody.appendChild(row);
            });
        }

        // Mettre à jour carte + statistiques avec les filtres actifs
        function applyFilters() {
            updateMapWithFilters();
            updateStatsWithFilters();
        }

        // Ajouter des écouteurs d'événements sur tous les filtres
        provinceSelect.addEventListener('change', function() {
            applyFilters();
        });

        territoireSelect.addEventListener('change', function() {
            applyFilters();
        });

        communeSelect.addEventListener('change', function() {
            applyFilters();
        });

        siteSelect.addEventListener('change', function() {
            applyFilters();
        });

        coordinateurSelect.addEventListener('change', function() {
            applyFilters();
        });

        gestionnaireSelect.addEventListener('change', function() {
            applyFilters();
        });

        categorieSiteSelect.addEventListener('change', function() {
            applyFilters();
        });

        periodeSelect?.addEventListener('change', function() {
            const normalized = normalizePeriodInput(this.value);
            if (!/^(0[1-9]|1[0-2])\/\d{4}$/.test(normalized)) {
                this.value = defaultPeriodValue;
                return;
            }

            this.value = normalized;
            applyFilters();
        });

        periodeSelect?.addEventListener('blur', function() {
            const normalized = normalizePeriodInput(this.value);
            this.value = /^(0[1-9]|1[0-2])\/\d{4}$/.test(normalized) ? normalized : defaultPeriodValue;
        });

        // Bouton pour recentrer la carte
        document.getElementById('reset-view')?.addEventListener('click', function() {
            if (dashboardMap) {
                dashboardMap.resetView();
            }
        });

        // Bouton "Actualiser" pour recharger la carte et les statistiques
        document.querySelector('.filter-button')?.addEventListener('click', function() {
            applyFilters();
        });

        // Bouton "Réinitialiser" pour réinitialiser tous les filtres
        document.getElementById('reset-filters')?.addEventListener('click', function() {
            // Réinitialiser tous les sélecteurs
            provinceSelect.value = '';
            territoireSelect.value = '';
            territoireSelect.disabled = true;
            territoireSelect.innerHTML = '<option value="">Sélectionnez d\'abord une province</option>';
            communeSelect.value = '';
            communeSelect.disabled = true;
            communeSelect.innerHTML = '<option value="">Sélectionnez d\'abord un territoire</option>';
            siteSelect.value = '';
            siteSelect.disabled = true;
            siteSelect.innerHTML = '<option value="">Sélectionnez d\'abord une zone de santé</option>';
            coordinateurSelect.value = '';
            gestionnaireSelect.value = '';
            categorieSiteSelect.value = '';
            if (periodeSelect) {
                periodeSelect.value = defaultPeriodValue;
            }
            
            // Recharger la carte et les stats avec tous les sites
            applyFilters();
        });

        // Fonction pour mettre à jour les statistiques avec les filtres actifs
        function updateStatsWithFilters() {
            const formatDeltaText = (delta) => {
                const value = delta?.value ?? 0;
                const percent = delta?.percent;

                if (value > 0) {
                    return `↑ Différence: +${value.toLocaleString('fr-FR')}${percent !== null && percent !== undefined ? ` (+${percent}%)` : ''} vs mois précédent`;
                }

                if (value < 0) {
                    return `↓ Différence: ${value.toLocaleString('fr-FR')}${percent !== null && percent !== undefined ? ` (${percent}%)` : ''} vs mois précédent`;
                }

                return '→ Différence: 0 vs mois précédent';
            };

            const updateDeltaElement = (id, delta) => {
                const el = document.getElementById(id);
                if (!el) return;

                const value = delta?.value ?? 0;
                el.textContent = formatDeltaText(delta);

                el.classList.remove('text-green-600', 'dark:text-green-400', 'text-red-600', 'dark:text-red-400', 'text-gray-500', 'dark:text-gray-400');
                if (value > 0) {
                    el.classList.add('text-green-600', 'dark:text-green-400');
                } else if (value < 0) {
                    el.classList.add('text-red-600', 'dark:text-red-400');
                } else {
                    el.classList.add('text-gray-500', 'dark:text-gray-400');
                }
            };

            const params = new URLSearchParams();
            
            if (provinceSelect.value) params.append('province_id', provinceSelect.value);
            if (territoireSelect.value) params.append('territoire_id', territoireSelect.value);
            if (communeSelect.value) params.append('commune_id', communeSelect.value);
            if (siteSelect.value) params.append('site_id', siteSelect.value);
            if (coordinateurSelect.value) params.append('coordinateur_id', coordinateurSelect.value);
            if (gestionnaireSelect.value) params.append('gestionnaire_id', gestionnaireSelect.value);
            if (categorieSiteSelect.value) params.append('categorie_site_id', categorieSiteSelect.value);
            const normalizedPeriod = normalizePeriodInput(periodeSelect?.value || '');
            if (normalizedPeriod) params.append('periode', normalizedPeriod);

            fetch(`/api/dashboard/stats?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    // Mettre à jour les cartes de statistiques
                    document.getElementById('stat-total-pdi').textContent = data.stats.total_pdi.toLocaleString('fr-FR');
                    document.getElementById('stat-hommes').textContent = data.stats.hommes.toLocaleString('fr-FR');
                    document.getElementById('stat-femmes').textContent = data.stats.femmes.toLocaleString('fr-FR');
                    document.getElementById('stat-handicap').textContent = data.stats.personnes_handicap.toLocaleString('fr-FR');
                    document.getElementById('stat-menages').textContent = data.stats.menages.toLocaleString('fr-FR');
                    document.getElementById('stat-enfants').textContent = data.stats.enfants.toLocaleString('fr-FR');
                    document.getElementById('stat-adultes').textContent = data.stats.adultes.toLocaleString('fr-FR');
                    document.getElementById('stat-ages').textContent = data.stats.personnes_agees.toLocaleString('fr-FR');

                    updateDeltaElement('stat-total-pdi-delta', data.deltas?.total_pdi);
                    updateDeltaElement('stat-hommes-delta', data.deltas?.hommes);
                    updateDeltaElement('stat-femmes-delta', data.deltas?.femmes);
                    updateDeltaElement('stat-handicap-delta', data.deltas?.personnes_handicap);
                    updateDeltaElement('stat-menages-delta', data.deltas?.menages);
                    updateDeltaElement('stat-enfants-delta', data.deltas?.enfants);
                    updateDeltaElement('stat-adultes-delta', data.deltas?.adultes);
                    updateDeltaElement('stat-ages-delta', data.deltas?.personnes_agees);

                    // Mettre à jour le graphique par âge
                    ageChart.updateSeries([{
                        name: 'Population',
                        data: [
                            data.age_distribution['0-5 ans'],
                            data.age_distribution['6-17 ans'],
                            data.age_distribution['18-59 ans'],
                            data.age_distribution['60+ ans']
                        ]
                    }]);

                    // Mettre à jour le graphique par genre
                    genderChart.updateSeries([
                        data.gender_distribution.femmes,
                        data.gender_distribution.hommes
                    ]);
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des statistiques:', error);
                });

            // Mettre à jour la distribution provinciale
            fetch(`/api/dashboard/province-distribution?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    provinceChart.updateOptions({
                        xaxis: {
                            categories: data.provinces
                        }
                    });
                    provinceChart.updateSeries([{
                        name: 'Population',
                        data: data.values
                    }]);
                })
                .catch(error => {
                    console.error('Erreur lors du chargement de la distribution provinciale:', error);
                });
        }

        // Bouton "Exporter" pour exporter les sites en CSV
        document.getElementById('export-sites')?.addEventListener('click', function() {
            if (!currentSitesData || currentSitesData.length === 0) {
                alert('Aucune donnée à exporter. Veuillez d\'abord sélectionner des filtres.');
                return;
            }
            
            // Créer le contenu CSV
            let csvContent = "Code Site,Nom du Site,Province,Territoire,Zone de Santé,Individus,Ménages,Gestionnaire,Coordinateur\n";
            
            currentSitesData.forEach(site => {
                const row = [
                    site.code_site || '',
                    site.nom || '',
                    site.province || '',
                    site.territoire || '',
                    site.zone_sante || '',
                    site.individus || 0,
                    site.menages || 0,
                    site.gestionnaire ? site.gestionnaire.name : '',
                    site.coordinateur ? site.coordinateur.name : ''
                ].map(field => `"${String(field).replace(/"/g, '""')}"`).join(',');
                
                csvContent += row + "\n";
            });
            
            // Créer un blob et télécharger
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', `sites_${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\Benoit\dashboard-dms\resources\views/dashboard.blade.php ENDPATH**/ ?>