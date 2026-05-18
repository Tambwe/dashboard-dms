@extends('layouts.app')

@section('title', 'Dashboard Administrateur')
@section('subtitle', 'Vue d\'ensemble de la gestion des utilisateurs')

@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Total Users -->
        <div class="stat-card">
            <div class="stat-icon bg-primary-100 dark:bg-primary-900/30">
                <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <div class="stat-value">{{ $stats['total_users'] }}</div>
            <div class="stat-label">Utilisateurs totaux</div>
        </div>

        <!-- Active Users -->
        <div class="stat-card">
            <div class="stat-icon bg-green-100 dark:bg-green-900/30">
                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="stat-value">{{ $stats['active_users'] }}</div>
            <div class="stat-label">Utilisateurs actifs</div>
        </div>

        <!-- Organisation -->
        <div class="stat-card">
            <div class="stat-icon bg-purple-100 dark:bg-purple-900/30">
                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <div class="stat-value text-2xl">
                @if(auth()->user()->isSuperAdmin())
                    Toutes
                @else
                    {{ auth()->user()->organisation->code }}
                @endif
            </div>
            <div class="stat-label">Organisation(s)</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Actions rapides</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('admin.users.create') }}" class="flex items-center p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/30 transition-colors">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Ajouter un utilisateur</p>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Créer un nouveau compte</p>
                </div>
            </a>

            <a href="{{ route('admin.users.index') }}" class="flex items-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Gérer les utilisateurs</p>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Liste complète</p>
                </div>
            </a>

            @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('admin.organisations.index') }}" class="flex items-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Organisations</p>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Gérer les organisations</p>
                </div>
            </a>
            @endif
        </div>
    </div>

    <!-- Recent Users -->
    @if($stats['recent_users']->count() > 0)
    <div class="card p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Utilisateurs récents</h3>
        <div class="space-y-4">
            @foreach($stats['recent_users'] as $user)
            <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700 last:border-0">
                <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                        <span class="text-primary-600 dark:text-primary-400 font-semibold text-sm">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </span>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    @if($user->role === 'super_admin')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-200">
                            Super Admin
                        </span>
                    @elseif($user->role === 'admin_organisation')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-800 dark:text-primary-200">
                            Admin
                        </span>
                    @else
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                            Utilisateur
                        </span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
