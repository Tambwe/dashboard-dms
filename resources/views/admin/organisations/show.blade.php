@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- En-tête -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <a href="{{ route('admin.organisations.index') }}" 
                       class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 mr-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $organisation->name }}</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Code: {{ $organisation->code }} • 
                            @if($organisation->is_active)
                            <span class="text-green-600 dark:text-green-400">Active</span>
                            @else
                            <span class="text-red-600 dark:text-red-400">Inactive</span>
                            @endif
                        </p>
                    </div>
                </div>
                <a href="{{ route('admin.organisations.edit', $organisation) }}" 
                   class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Modifier
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Informations générales -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Informations</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        @if($organisation->description)
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $organisation->description }}</p>
                        </div>
                        @endif

                        @if($organisation->address)
                        <div>
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-gray-400 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Adresse</p>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $organisation->address }}</p>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($organisation->email)
                        <div>
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-gray-400 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Email</p>
                                    <a href="mailto:{{ $organisation->email }}" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">
                                        {{ $organisation->email }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($organisation->phone)
                        <div>
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-gray-400 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Téléphone</p>
                                    <a href="tel:{{ $organisation->phone }}" class="text-sm text-gray-900 dark:text-white">
                                        {{ $organisation->phone }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Statut</span>
                                @if($organisation->is_active)
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                    Active
                                </span>
                                @else
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                    Inactive
                                </span>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Créé le</span>
                            <span class="text-gray-900 dark:text-white">{{ $organisation->created_at->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des utilisateurs -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Utilisateurs ({{ $organisation->users->count() }})
                            </h3>
                        </div>
                    </div>
                    
                    @if($organisation->users->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Utilisateur
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Email
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Rôle
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Statut
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($organisation->users as $user)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($user->role === 'admin_organisation')
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-secondary-100 dark:bg-secondary-900/30 text-secondary-800 dark:text-secondary-300">
                                            Admin
                                        </span>
                                        @else
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                                            Utilisateur
                                        </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($user->is_active)
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                            Actif
                                        </span>
                                        @else
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                            Inactif
                                        </span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <p class="mt-2 text-gray-500 dark:text-gray-400">Aucun utilisateur dans cette organisation</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
