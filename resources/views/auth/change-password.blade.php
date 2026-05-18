<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Changement de mot de passe - DMS CCCM</title>

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
                Changement de mot de passe requis
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                Pour des raisons de sécurité, vous devez changer votre mot de passe
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white dark:bg-gray-800 py-8 px-4 shadow-lg sm:rounded-lg sm:px-10 border border-gray-200 dark:border-gray-700">
                <!-- Alert -->
                <div class="mb-6 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 p-4 border border-yellow-200 dark:border-yellow-800">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                Vous devez changer votre mot de passe avant de continuer.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Success Message -->
                @if (session('success'))
                    <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-900/20 p-4 text-sm text-green-800 dark:text-green-200">
                        {{ session('success') }}
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

                <form class="space-y-6" action="{{ route('password.change') }}" method="POST">
                    @csrf

                    <!-- Current Password -->
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Mot de passe actuel
                        </label>
                        <div class="mt-1">
                            <input id="current_password" name="current_password" type="password" autocomplete="current-password" required
                                class="block w-full appearance-none rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm">
                        </div>
                    </div>

                    <!-- New Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Nouveau mot de passe
                        </label>
                        <div class="mt-1">
                            <input id="password" name="password" type="password" autocomplete="new-password" required
                                class="block w-full appearance-none rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm">
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Le mot de passe doit contenir au moins 8 caractères
                        </p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Confirmer le nouveau mot de passe
                        </label>
                        <div class="mt-1">
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                                class="block w-full appearance-none rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm">
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button type="submit"
                            class="flex w-full justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 transition-colors">
                            Changer mon mot de passe
                        </button>
                    </div>
                </form>

                <!-- Logout Option -->
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full text-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                            Se déconnecter
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
