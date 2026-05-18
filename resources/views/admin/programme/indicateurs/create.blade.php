@extends('layouts.app')

@section('title', 'Nouvel indicateur')

@section('content')
<div class="py-6">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center gap-3">
            <a href="{{ route('admin.programme.indicateurs.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Nouvel indicateur</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Ajouter un indicateur au cadre de programmation</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
            <form action="{{ route('admin.programme.indicateurs.store') }}" method="POST" class="p-6 space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="reference" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Référence <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="reference" id="reference" value="{{ old('reference') }}"
                               class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white @error('reference') border-red-500 @else border-gray-300 dark:border-gray-600 @enderror"
                               placeholder="ex: IND-001" required>
                        @error('reference')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="code" id="code" value="{{ old('code') }}"
                               class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white @error('code') border-red-500 @else border-gray-300 dark:border-gray-600 @enderror"
                               placeholder="ex: CCCM-IND-01" required>
                        @error('code')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="label" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Libellé <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="label" id="label" value="{{ old('label') }}"
                           class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white @error('label') border-red-500 @else border-gray-300 dark:border-gray-600 @enderror"
                           placeholder="ex: Nombre de sites couverts" required>
                    @error('label')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="unit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Unité de mesure</label>
                        <input type="text" name="unit" id="unit" value="{{ old('unit') }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                               placeholder="ex: Site, Ménage, %">
                    </div>
                    <div>
                        <label for="frequency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fréquence</label>
                        <input type="text" name="frequency" id="frequency" value="{{ old('frequency') }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                               placeholder="ex: Mensuelle, Trimestrielle">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="owner" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Responsable</label>
                        <input type="text" name="owner" id="owner" value="{{ old('owner') }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                               placeholder="ex: MEAL, Coordo">
                    </div>
                    <div>
                        <label for="verification_source" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Source de vérification</label>
                        <input type="text" name="verification_source" id="verification_source" value="{{ old('verification_source') }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                               placeholder="ex: Rapport terrain, Base ménages">
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                           class="h-4 w-4 text-emerald-600 border-gray-300 rounded"
                           {{ old('is_active', '1') ? 'checked' : '' }}>
                    <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">Indicateur actif</label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('admin.programme.indicateurs.index') }}" class="filter-button">Annuler</a>
                    <button type="submit" class="primary-button">Créer l'indicateur</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
