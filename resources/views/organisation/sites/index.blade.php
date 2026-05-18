@extends('layouts.app')

@section('content')
<div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- En-tête -->
        <div class="mb-4">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Nos Sites</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Gérer les informations de vos sites</p>
        </div>

        <!-- Messages flash -->
        @if(session('success'))
        <div class="mb-3 rounded bg-green-50 dark:bg-green-900/20 p-3 border border-green-200 dark:border-green-800">
            <p class="text-sm text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
        @endif

        <!-- Barre de recherche -->
        <div class="bg-white dark:bg-gray-800 shadow rounded p-4 mb-4">
            <form method="GET" action="{{ route('organisation.sites.index') }}" class="flex gap-4">
                <div class="flex-1">
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Rechercher un site..."
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                </div>
                <button type="submit" 
                        class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Rechercher
                </button>
            </form>
        </div>

        <!-- Liste des sites -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($sites as $site)
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden hover:shadow-lg transition-shadow cursor-pointer"
                 onclick="window.location.href='{{ route('organisation.sites.edit', $site) }}'">
                <!-- Photo du site -->
                <div class="h-48 bg-gray-200 dark:bg-gray-700 relative overflow-hidden">
                    @if($site->photos && count($site->photos) > 0)
                        <img src="{{ asset('storage/' . $site->photos[0]) }}" 
                             alt="{{ $site->nom }}"
                             class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                            <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                    @endif
                    
                    <!-- Badge de localisation GPS -->
                    @if($site->latitude && $site->longitude)
                    <div class="absolute top-2 right-2">
                        <span class="px-2 py-1 bg-emerald-500 text-white text-xs rounded-full flex items-center">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                            </svg>
                            GPS
                        </span>
                    </div>
                    @endif
                </div>

                <!-- Informations du site -->
                <div class="p-4">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $site->nom }}
                        </h3>
                        @if($site->code_site)
                        <span class="px-2 py-1 text-xs font-medium rounded bg-primary-100 dark:bg-primary-900/30 text-primary-800 dark:text-primary-300">
                            {{ $site->code_site }}
                        </span>
                        @endif
                    </div>

                    <div class="space-y-2 mb-4 text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            {{ $site->territoire }}, {{ $site->province }}
                        </div>

                        @if($site->typeSite)
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            {{ $site->typeSite->name }}
                        </div>
                        @endif

                        @if($site->menages || $site->individus)
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path>
                            </svg>
                            {{ $site->individus ?? 0 }} personnes
                            @if($site->menages)
                                ({{ $site->menages }} ménages)
                            @endif
                        </div>
                        @endif
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                {{ $site->photos ? count($site->photos) : 0 }}
                            </span>
                        </div>

                        <a href="{{ route('organisation.sites.edit', $site) }}" 
                           onclick="event.stopPropagation()"
                           class="px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                            Profil
                        </a>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-full">
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucun site</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Aucun site n'a été attribué à votre organisation pour le moment
                    </p>
                </div>
            </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($sites->hasPages())
        <div class="mt-6">
            {{ $sites->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
