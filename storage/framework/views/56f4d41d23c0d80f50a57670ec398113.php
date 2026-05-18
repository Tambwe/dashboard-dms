

<?php $__env->startSection('title', 'Ajouter un mouvement de population'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
    <!-- En-tête -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Nouveau mouvement de population</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Enregistrer les entrées et sorties de personnes déplacées dans les sites
                <span class="ml-2 px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 text-xs font-semibold rounded-full">
                    ⚠️ Nécessite validation du super admin
                </span>
            </p>
        </div>
        <a href="<?php echo e(route('admin.mouvements.index')); ?>" class="filter-button">
            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Retour à la liste
        </a>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Erreurs de validation</h3>
                    <ul class="mt-2 text-sm text-red-700 dark:text-red-300 list-disc list-inside">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <li><?php echo e($error); ?></li>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <form action="<?php echo e(route('admin.mouvements.store')); ?>" method="POST" class="space-y-6">
        <?php echo csrf_field(); ?>

        <!-- Informations de base -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informations du mouvement</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Site -->
                <div class="md:col-span-2">
                    <label for="site_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Site <span class="text-red-500">*</span>
                    </label>
                    <select id="site_id" name="site_id" required 
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Sélectionnez un site</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $sites; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $site): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <option value="<?php echo e($site->id); ?>" <?php echo e(old('site_id') == $site->id ? 'selected' : ''); ?>>
                                <?php echo e($site->nom); ?> (<?php echo e($site->code_site); ?>)
                            </option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </select>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['site_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <!-- Type de mouvement -->
                <div>
                    <label for="type_mouvement" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Type de mouvement <span class="text-red-500">*</span>
                    </label>
                    <select id="type_mouvement" name="type_mouvement" required 
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Sélectionnez un type</option>
                        <option value="arrivee" <?php echo e(old('type_mouvement') == 'arrivee' ? 'selected' : ''); ?>>
                            ➕ Arrivée / Nouvelle entrée
                        </option>
                        <option value="depart" <?php echo e(old('type_mouvement') == 'depart' ? 'selected' : ''); ?>>
                            ➖ Départ / Sortie
                        </option>
                        <option value="recensement" <?php echo e(old('type_mouvement') == 'recensement' ? 'selected' : ''); ?>>
                            📊 Recensement / Mise à jour complète
                        </option>
                        <option value="ajustement" <?php echo e(old('type_mouvement') == 'ajustement' ? 'selected' : ''); ?>>
                            🔄 Ajustement
                        </option>
                    </select>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['type_mouvement'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <!-- Date du mouvement -->
                <div>
                    <label for="date_mouvement" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Date du mouvement <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="date_mouvement" name="date_mouvement" required 
                           value="<?php echo e(old('date_mouvement', date('Y-m-d'))); ?>"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['date_mouvement'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <!-- Raison du mouvement -->
                <div class="md:col-span-2">
                    <label for="raison_mouvement_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Raison du mouvement
                    </label>
                    <select id="raison_mouvement_id" name="raison_mouvement_id" 
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Aucune raison spécifiée</option>
                        <optgroup label="Raisons d'entrée" id="raisons-entree" style="display: none;">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $raisonsEntree; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $raison): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($raison->id); ?>" <?php echo e(old('raison_mouvement_id') == $raison->id ? 'selected' : ''); ?>>
                                    <?php echo e($raison->name); ?>

                                </option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </optgroup>
                        <optgroup label="Raisons de sortie" id="raisons-sortie" style="display: none;">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $raisonsSortie; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $raison): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($raison->id); ?>" <?php echo e(old('raison_mouvement_id') == $raison->id ? 'selected' : ''); ?>>
                                    <?php echo e($raison->name); ?>

                                </option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </optgroup>
                    </select>
                </div>

                <!-- Période -->
                <div>
                    <label for="periode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Période
                    </label>
                    <input type="text" id="periode" name="periode" value="<?php echo e(old('periode')); ?>"
                           placeholder="Ex: Janvier 2026"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                </div>

                <!-- Source -->
                <div>
                    <label for="source" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Source des données
                    </label>
                    <input type="text" id="source" name="source" value="<?php echo e(old('source')); ?>"
                           placeholder="Ex: DTM, Site Management"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                </div>
            </div>
        </div>

        <!-- Données démographiques -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Données démographiques</h3>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <span id="info-type-mouvement">Les valeurs seront ajoutées à la population actuelle</span>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Ménages -->
                <div class="md:col-span-2 bg-primary-50 dark:bg-primary-900/20 p-4 rounded-lg">
                    <label for="menages" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Nombre de ménages <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="menages" name="menages" required min="0"
                           value="<?php echo e(old('menages', 0)); ?>"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                </div>

                <!-- Total individus (calculé automatiquement) -->
                <div class="md:col-span-2 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <label for="individus" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Total individus (calculé automatiquement) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="individus" name="individus" required min="0" readonly
                           value="<?php echo e(old('individus', 0)); ?>"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white bg-gray-100 dark:bg-gray-600">
                </div>
            </div>

            <!-- Répartition par sexe et âge -->
            <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Femmes -->
                <div class="border border-pink-200 dark:border-pink-800 rounded-lg p-4">
                    <h4 class="text-md font-semibold text-pink-600 dark:text-pink-400 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Femmes
                    </h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">0-5 ans</label>
                            <input type="number" name="f_0_5" min="0" value="<?php echo e(old('f_0_5', 0)); ?>"
                                   class="age-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">6-17 ans</label>
                            <input type="number" name="f_6_17" min="0" value="<?php echo e(old('f_6_17', 0)); ?>"
                                   class="age-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">18-59 ans</label>
                            <input type="number" name="f_18_59" min="0" value="<?php echo e(old('f_18_59', 0)); ?>"
                                   class="age-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">60+ ans</label>
                            <input type="number" name="f_60_plus" min="0" value="<?php echo e(old('f_60_plus', 0)); ?>"
                                   class="age-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                </div>

                <!-- Hommes -->
                <div class="border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <h4 class="text-md font-semibold text-blue-600 dark:text-blue-400 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Hommes
                    </h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">0-5 ans</label>
                            <input type="number" name="h_0_5" min="0" value="<?php echo e(old('h_0_5', 0)); ?>"
                                   class="age-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">6-17 ans</label>
                            <input type="number" name="h_6_17" min="0" value="<?php echo e(old('h_6_17', 0)); ?>"
                                   class="age-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">18-59 ans</label>
                            <input type="number" name="h_18_59" min="0" value="<?php echo e(old('h_18_59', 0)); ?>"
                                   class="age-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">60+ ans</label>
                            <input type="number" name="h_60_plus" min="0" value="<?php echo e(old('h_60_plus', 0)); ?>"
                                   class="age-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes et description -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informations complémentaires</h3>
            
            <div class="space-y-4">
                <div>
                    <label for="raison" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Raison détaillée
                    </label>
                    <input type="text" id="raison" name="raison" value="<?php echo e(old('raison')); ?>"
                           placeholder="Précisions sur la raison du mouvement"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Description / Notes
                    </label>
                    <textarea id="description" name="description" rows="4"
                              placeholder="Ajoutez des notes ou des observations sur ce mouvement de population..."
                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500"><?php echo e(old('description')); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Boutons d'action -->
        <div class="flex items-center justify-end space-x-4">
            <a href="<?php echo e(route('admin.mouvements.index')); ?>" class="filter-button">
                Annuler
            </a>
            <button type="submit" class="primary-button">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Enregistrer le mouvement
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeMouvementSelect = document.getElementById('type_mouvement');
    const raisonMouvementSelect = document.getElementById('raison_mouvement_id');
    const raisonsEntree = document.getElementById('raisons-entree');
    const raisonsSortie = document.getElementById('raisons-sortie');
    const infoTypeMouvement = document.getElementById('info-type-mouvement');
    const ageInputs = document.querySelectorAll('.age-input');
    const individusInput = document.getElementById('individus');

    // Gérer l'affichage des raisons selon le type de mouvement
    typeMouvementSelect.addEventListener('change', function() {
        const type = this.value;
        
        // Cacher toutes les raisons
        raisonsEntree.style.display = 'none';
        raisonsSortie.style.display = 'none';
        raisonMouvementSelect.value = '';

        // Afficher les raisons appropriées
        if (type === 'arrivee') {
            raisonsEntree.style.display = 'block';
            infoTypeMouvement.textContent = 'Les valeurs seront ajoutées à la population actuelle';
        } else if (type === 'depart') {
            raisonsSortie.style.display = 'block';
            infoTypeMouvement.textContent = 'Les valeurs seront soustraites de la population actuelle (utilisez des nombres positifs)';
        } else if (type === 'recensement') {
            infoTypeMouvement.textContent = 'Les valeurs remplaceront complètement la population actuelle';
        } else if (type === 'ajustement') {
            infoTypeMouvement.textContent = 'Les valeurs seront ajoutées ou soustraites (utilisez des nombres négatifs pour diminuer)';
        }
    });

    // Calculer automatiquement le total d'individus
    function calculateTotal() {
        let total = 0;
        ageInputs.forEach(input => {
            const value = parseInt(input.value) || 0;
            total += value;
        });
        individusInput.value = total;
    }

    // Écouter les changements sur tous les champs d'âge
    ageInputs.forEach(input => {
        input.addEventListener('input', calculateTotal);
    });

    // Calculer au chargement
    calculateTotal();
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\Benoit\dashboard-dms\resources\views/admin/mouvements/create.blade.php ENDPATH**/ ?>