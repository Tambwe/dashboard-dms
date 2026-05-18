{{-- Champ : Groupe --}}
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
        Groupe <span class="text-red-500">*</span>
    </label>
    <input list="groupes-list" name="groupe"
           value="{{ old('groupe', $ossatChoix->groupe ?? '') }}"
           required
           class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
           placeholder="ex: types_abri">
    <datalist id="groupes-list">
        @foreach($groupes as $g)
            <option value="{{ $g }}">
        @endforeach
    </datalist>
    <p class="mt-1 text-xs text-gray-400">Utilisez des underscores, pas d'espaces (ex: <code>sources_eau</code>)</p>
</div>

{{-- Champ : Valeur --}}
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
        Valeur <span class="text-red-500">*</span>
    </label>
    <input type="text" name="valeur"
           value="{{ old('valeur', $ossatChoix->valeur ?? '') }}"
           required maxlength="120"
           class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
           placeholder="ex: Tente">
    <p class="mt-1 text-xs text-gray-400">Valeur stockée en base de données (clé technique)</p>
</div>

{{-- Champ : Libellé --}}
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
        Libellé affiché
    </label>
    <input type="text" name="libelle"
           value="{{ old('libelle', $ossatChoix->libelle ?? '') }}"
           maxlength="180"
           class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
           placeholder="ex: Tente bâche (optionnel, sinon la valeur est utilisée)">
</div>

{{-- Champ : Ordre --}}
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
        Ordre d'affichage
    </label>
    <input type="number" name="ordre" min="0"
           value="{{ old('ordre', $ossatChoix->ordre ?? 0) }}"
           class="w-32 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
</div>

{{-- Champ : Actif --}}
<div class="flex items-center gap-2">
    <input type="hidden" name="actif" value="0">
    <input type="checkbox" name="actif" value="1" id="actif"
           {{ old('actif', $ossatChoix->actif ?? true) ? 'checked' : '' }}
           class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
    <label for="actif" class="text-sm text-gray-700 dark:text-gray-300">Actif (visible dans les formulaires)</label>
</div>
