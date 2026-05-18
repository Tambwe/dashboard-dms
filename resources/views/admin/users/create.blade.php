@extends('layouts.app')

@section('title', 'Nouvel utilisateur')
@section('subtitle', 'Créer un nouveau compte utilisateur')

@section('content')
<div class="max-w-3xl">
    <div class="card p-6">
        <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Nom complet <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                    class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Adresse email <span class="text-red-500">*</span>
                </label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                    class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                @error('email')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Phone -->
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Téléphone
                </label>
                <input type="tel" name="phone" id="phone" value="{{ old('phone') }}"
                    class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                @error('phone')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Organisation -->
            <div>
                <label for="organisation_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Organisation <span class="text-red-500">*</span>
                </label>
                <select name="organisation_id" id="organisation_id" required
                    class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500"
                    @if(auth()->user()->isAdminOrganisation()) disabled @endif>
                    <option value="">Sélectionnez une organisation</option>
                    @foreach($organisations as $organisation)
                        <option value="{{ $organisation->id }}" {{ old('organisation_id') == $organisation->id ? 'selected' : '' }}>
                            {{ $organisation->name }} ({{ $organisation->code }})
                        </option>
                    @endforeach
                </select>
                @if(auth()->user()->isAdminOrganisation())
                    <input type="hidden" name="organisation_id" value="{{ auth()->user()->organisation_id }}">
                @endif
                @error('organisation_id')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Role -->
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Rôle <span class="text-red-500">*</span>
                </label>
                <select name="role" id="role" required
                    class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                    <option value="">Sélectionnez un rôle</option>
                    @foreach($roles as $role)
                        <option value="{{ $role }}" {{ old('role') == $role ? 'selected' : '' }}>
                            @if($role === 'super_admin')
                                Super Administrateur
                            @elseif($role === 'admin_organisation')
                                Administrateur Organisation
                            @else
                                Utilisateur
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('role')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Mot de passe <span class="text-red-500">*</span>
                </label>
                <input type="password" name="password" id="password" required
                    class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Minimum 8 caractères</p>
                @error('password')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password Confirmation -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Confirmer le mot de passe <span class="text-red-500">*</span>
                </label>
                <input type="password" name="password_confirmation" id="password_confirmation" required
                    class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
            </div>

            <!-- Active Status -->
            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1" checked
                    class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                    Compte actif
                </label>
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('admin.users.index') }}" class="filter-button">
                    Annuler
                </a>
                <button type="submit" class="primary-button">
                    Créer l'utilisateur
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
