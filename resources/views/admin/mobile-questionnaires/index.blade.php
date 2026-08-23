@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-4 md:p-6 space-y-6">
    <div class="rounded-2xl border border-gray-200 bg-white p-5">
        <h1 class="text-xl font-bold text-gray-900">Questionnaires mobile</h1>
        <p class="text-sm text-gray-600 mt-1">Importer un XLSForm et gérer les versions actives utilisées par l'application mobile.</p>
        @php($activeQuestionnaire = $questionnaires->firstWhere('is_active', true))
        @if($activeQuestionnaire)
            <div class="mt-4">
                <a href="{{ route('admin.mobile-questionnaires.edit', $activeQuestionnaire) }}" class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">
                    Modifier le questionnaire actif (v{{ $activeQuestionnaire->version }})
                </a>
            </div>
        @endif
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5">
        <form method="POST" action="{{ route('admin.mobile-questionnaires.import') }}" enctype="multipart/form-data" class="grid gap-4 md:grid-cols-4">
            @csrf
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Fichier XLSX</label>
                <input type="file" name="xlsx_file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required>
                <p class="mt-1 text-xs text-gray-500">Choisissez un fichier .xlsx depuis votre disque.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Code</label>
                <input type="text" name="code" value="{{ old('code', 'service-cartography') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Titre</label>
                <input type="text" name="title" value="{{ old('title', 'Cartographie des services (XLSForm)') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div class="md:col-span-4">
                <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white">Importer et activer</button>
            </div>
        </form>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Code</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Version</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Titre</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Actif</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Publié</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($questionnaires as $questionnaire)
                    <tr>
                        <td class="px-4 py-3">{{ $questionnaire->code }}</td>
                        <td class="px-4 py-3">v{{ $questionnaire->version }}</td>
                        <td class="px-4 py-3">{{ $questionnaire->title }}</td>
                        <td class="px-4 py-3">
                            @if($questionnaire->is_active)
                                <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-700">Oui</span>
                            @else
                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">Non</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ optional($questionnaire->published_at)->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.mobile-questionnaires.edit', $questionnaire) }}" class="text-primary-700 font-semibold hover:underline">Configurer</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">Aucun questionnaire disponible.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">
            {{ $questionnaires->links() }}
        </div>
    </div>
</div>
@endsection
