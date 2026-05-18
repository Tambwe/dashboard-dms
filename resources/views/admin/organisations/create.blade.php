@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <!-- En-tête -->
        <div class="mb-6">
            <div class="flex items-center mb-4">
                <a href="{{ route('admin.organisations.index') }}" 
                   class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 mr-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Nouvelle Organisation</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Créer une nouvelle organisation dans le système
                    </p>
                </div>
            </div>
        </div>

        <!-- Formulaire -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
            <form action="{{ route('admin.organisations.store') }}" method="POST" class="p-6 space-y-6">
                @csrf

                <!-- Nom -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Nom de l'organisation <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="name" 
                           id="name" 
                           value="{{ old('name') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white @error('name') border-red-500 @enderror"
                           required>
                    @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Code -->
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Code <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="code" 
                           id="code" 
                           value="{{ old('code') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white @error('code') border-red-500 @enderror"
                           required
                           maxlength="50">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Code unique pour identifier l'organisation (ex: HCR, WNH)</p>
                    @error('code')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Description
                    </label>
                    <textarea name="description" 
                              id="description" 
                              rows="3"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                    @error('description')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Email
                        </label>
                        <input type="email" 
                               name="email" 
                               id="email" 
                               value="{{ old('email') }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white @error('email') border-red-500 @enderror">
                        @error('email')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Téléphone -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Téléphone
                        </label>
                        <input type="text" 
                               name="phone" 
                               id="phone" 
                               value="{{ old('phone') }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white @error('phone') border-red-500 @enderror">
                        @error('phone')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Adresse -->
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Adresse
                    </label>
                    <input type="text" 
                           name="address" 
                           id="address" 
                           value="{{ old('address') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white @error('address') border-red-500 @enderror">
                    @error('address')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Statut actif -->
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" 
                               name="is_active" 
                               value="1"
                               {{ old('is_active', true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Organisation active</span>
                    </label>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Les organisations inactives ne peuvent pas se connecter au système
                    </p>
                </div>

                <!-- Clusters -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Clusters
                    </label>
                    @if($clusters->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">Aucun cluster actif disponible.</p>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach($clusters as $cluster)
                                <label class="flex items-center p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                    <input type="checkbox"
                                           name="cluster_ids[]"
                                           value="{{ $cluster->id }}"
                                           {{ in_array($cluster->id, old('cluster_ids', [])) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600">
                                    <div class="ml-3">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $cluster->name }}</span>
                                        <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">({{ $cluster->code }})</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    @error('cluster_ids')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Boutons -->
                <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('admin.organisations.index') }}" 
                       class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Annuler
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors shadow-sm">
                        Créer l'organisation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
