@extends('layouts.app')

@section('content')
<div class="py-4">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">

        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Modifier le choix</h2>
            <a href="{{ route('admin.ossat-choix.index', ['groupe' => $ossatChoix->groupe]) }}"
               class="text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Retour
            </a>
        </div>

        @if($errors->any())
        <div class="mb-4 rounded bg-red-50 dark:bg-red-900/20 p-3 border border-red-200 dark:border-red-800">
            <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-300">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <form method="POST" action="{{ route('admin.ossat-choix.update', $ossatChoix) }}">
                @csrf @method('PUT')
                @include('admin.ossat-choix._form', ['ossatChoix' => $ossatChoix])
                <div class="mt-6 flex gap-3">
                    <button type="submit"
                            class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Enregistrer
                    </button>
                    <a href="{{ route('admin.ossat-choix.index', ['groupe' => $ossatChoix->groupe]) }}"
                       class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm rounded-lg transition-colors">
                        Annuler
                    </a>
                </div>
            </form>
        </div>

    </div>
</div>
@endsection
