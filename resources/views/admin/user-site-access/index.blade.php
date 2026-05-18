@extends('layouts.app')

@section('content')
<div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- En-tête -->
        <div class="mb-4">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Gestion des Accès Utilisateurs aux Sites</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Attribuer des sites spécifiques aux utilisateurs pour la collecte de données</p>
        </div>

        <!-- Messages flash -->
        @if(session('success'))
        <div class="mb-3 rounded bg-green-50 dark:bg-green-900/20 p-3 border border-green-200 dark:border-green-800">
            <p class="text-sm text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
        @endif

        <!-- Filtres -->
        <div class="bg-white dark:bg-gray-800 shadow rounded p-4 mb-4">
            <form method="GET" action="{{ route('admin.user-site-access.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Recherche
                    </label>
                    <input type="text" 
                           name="search" 
                           id="search" 
                           value="{{ request('search') }}"
                           placeholder="Nom ou email..."
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div>
                    <label for="organisation_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Organisation
                    </label>
                    <select name="organisation_id" 
                            id="organisation_id"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Toutes les organisations</option>
                        @foreach($organisations as $org)
                            <option value="{{ $org->id }}" {{ request('organisation_id') == $org->id ? 'selected' : '' }}>
                                {{ $org->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Rôle
                    </label>
                    <select name="role" 
                            id="role"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Tous les rôles</option>
                        <option value="user" {{ request('role') == 'user' ? 'selected' : '' }}>Utilisateur</option>
                        <option value="admin_organisation" {{ request('role') == 'admin_organisation' ? 'selected' : '' }}>Admin Organisation</option>
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit" 
                            class="w-full px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Filtrer
                    </button>
                </div>
            </form>
        </div>

        <!-- Tableau des utilisateurs -->
        <div class="bg-white dark:bg-gray-800 shadow rounded overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                Utilisateur
                            </th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                Organisation
                            </th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                Rôle
                            </th>
                            <th scope="col" class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                Sites assignés
                            </th>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($users as $user)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $user->name }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $user->email }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                {{ $user->organisation->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($user->role === 'admin_organisation')
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300">
                                        Admin Org
                                    </span>
                                @else
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                        Utilisateur
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-800 dark:text-primary-300">
                                    {{ $user->assignedSites->count() }} site(s)
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.user-site-access.manage', $user) }}" 
                                   class="inline-flex items-center px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    Gérer les sites
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                Aucun utilisateur trouvé
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($users->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $users->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
