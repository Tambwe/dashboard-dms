@extends('layouts.app')

@section('title', 'Modifier l\'activité')

@section('content')
<div class="py-6">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center gap-3">
            <a href="{{ route('admin.programme.activites.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Modifier l'activité</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 font-mono">{{ $activity->code }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
            <form action="{{ route('admin.programme.activites.update', $activity) }}" method="POST" class="p-6 space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="reference" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Référence <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="reference" id="reference" value="{{ old('reference', $activity->reference) }}"
                               class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white @error('reference') border-red-500 @else border-gray-300 dark:border-gray-600 @enderror"
                               required>
                        @error('reference')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="code" id="code" value="{{ old('code', $activity->code) }}"
                               class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white @error('code') border-red-500 @else border-gray-300 dark:border-gray-600 @enderror"
                               required>
                        @error('code')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="label" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Libellé <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="label" id="label" value="{{ old('label', $activity->label) }}"
                           class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white @error('label') border-red-500 @else border-gray-300 dark:border-gray-600 @enderror"
                           required>
                    @error('label')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="program_indicator_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Indicateur lié <span class="text-red-500">*</span>
                    </label>
                    <select name="program_indicator_id" id="program_indicator_id"
                            class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white @error('program_indicator_id') border-red-500 @else border-gray-300 dark:border-gray-600 @enderror"
                            required>
                        <option value="">-- Sélectionner un indicateur --</option>
                        @foreach($indicators as $ind)
                            <option value="{{ $ind->id }}" {{ old('program_indicator_id', $activity->program_indicator_id) == $ind->id ? 'selected' : '' }}>
                                {{ $ind->code }} — {{ $ind->label }}
                            </option>
                        @endforeach
                    </select>
                    @error('program_indicator_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Statut</label>
                        <select name="status" id="status"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                            <option value="">-- Statut --</option>
                            @foreach(['Planifie' => 'Planifié', 'En cours' => 'En cours', 'Termine' => 'Terminé', 'Annule' => 'Annulé'] as $val => $label)
                                <option value="{{ $val }}" {{ old('status', $activity->status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('admin.programme.activites.index') }}" class="filter-button">Annuler</a>
                    <button type="submit" class="primary-button">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
