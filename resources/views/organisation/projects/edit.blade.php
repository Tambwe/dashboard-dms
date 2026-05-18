@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6">
            <div class="flex items-center mb-4">
                <a href="{{ route('organisation.projects.index') }}"
                   class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 mr-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Modifier le Projet</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Mettre à jour les informations du projet</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
            <form action="{{ route('organisation.projects.update', $project) }}" method="POST" class="p-6 space-y-6">
                @csrf
                @method('PUT')
                @include('organisation.projects.partials.form', ['submitLabel' => 'Enregistrer les modifications'])
            </form>
        </div>
    </div>
</div>
@endsection
