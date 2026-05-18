<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Connexion - DMS CCCM</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    <x-vite-manifest-loader :assets="['resources/css/app.css', 'resources/js/app.js']" />
</head>
<body class="h-full bg-gradient-to-br from-primary-50 via-white to-secondary-50 dark:from-gray-900 dark:to-gray-800">
    <div class="flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <!-- Logo -->
            <div class="flex justify-center">
                <img src="{{ asset('images/logo-dms-cccm.avif') }}" alt="Logo DMS CCCM" class="h-16 w-auto">
            </div>
            <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                Connexion à DMS CCCM
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                Data Management System - Gestion des déplacés internes
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white dark:bg-gray-800 py-8 px-4 shadow-lg sm:rounded-lg sm:px-10 border border-gray-200 dark:border-gray-700">
                <!-- Session Status -->
                @if (session('status'))
                    <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-900/20 p-4 text-sm text-green-800 dark:text-green-200">
                        {{ session('status') }}
                    </div>
                @endif

                <!-- Errors -->
                @if ($errors->any())
                    <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/20 p-4">
                        <div class="text-sm text-red-800 dark:text-red-200">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <form class="space-y-6" action="{{ route('login') }}" method="POST">
                    @csrf

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Adresse email
                        </label>
                        <div class="mt-1">
                            <input id="email" name="email" type="email" autocomplete="email" required
                                value="{{ old('email') }}"
                                class="block w-full appearance-none rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm">
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Mot de passe
                        </label>
                        <div class="mt-1">
                            <input id="password" name="password" type="password" autocomplete="current-password" required
                                class="block w-full appearance-none rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm">
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember" name="remember" type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                            <label for="remember" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                                Se souvenir de moi
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button type="submit"
                            class="flex w-full justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
                            Se connecter
                        </button>
                    </div>
                </form>

                <!-- Info Login -->
                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="bg-white dark:bg-gray-800 px-2 text-gray-500 dark:text-gray-400">Comptes de test</span>
                        </div>
                    </div>

                    <div class="mt-4 space-y-2 text-xs text-gray-600 dark:text-gray-400">
                        <p><strong>Super Admin:</strong> superadmin@dms-cccm.org / dmscccm@2026</p>
                        <p><strong>Admin HCR:</strong> admin@hcr.com / dmscccm@2026</p>
                        <p><strong>Admin WNH:</strong> admin@wnh.com / dmscccm@2026</p>
                    </div>
                </div>
            </div>

            <!-- Back to Home -->
            <div class="mt-6 text-center">
                <a href="{{ url('/') }}" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                    ← Retour à l'accueil
                </a>
            </div>
        </div>
    </div>

    <script>
        // Dark mode support
        const theme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (theme === 'dark' || (!theme && prefersDark)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</body>
</html>
