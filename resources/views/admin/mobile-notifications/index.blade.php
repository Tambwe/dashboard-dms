@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-[1500px] space-y-6 p-4 md:p-8">
    <div>
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-primary-600">Administration mobile</p>
        <h1 class="text-2xl font-bold text-gray-900 md:text-3xl dark:text-white">Notifications mobiles</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
            Sélectionnez les appareils connectés auxquels envoyer une notification.
        </p>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <p class="text-xs uppercase tracking-[0.2em] text-gray-500">Appareils affichés</p>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $devices->total() }}</p>
        </div>
        <div class="rounded-xl border border-green-200 bg-green-50 p-5 dark:border-green-800 dark:bg-green-900/20">
            <p class="text-xs uppercase tracking-[0.2em] text-green-700 dark:text-green-300">Notifications actives</p>
            <p class="mt-2 text-3xl font-bold text-green-900 dark:text-green-100">
                {{ $devices->getCollection()->filter(fn ($device) => $device->notifications_enabled && $device->expo_push_token)->count() }}
            </p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-800 dark:bg-amber-900/20">
            <p class="text-xs uppercase tracking-[0.2em] text-amber-700 dark:text-amber-300">Sans permission sur cette page</p>
            <p class="mt-2 text-3xl font-bold text-amber-900 dark:text-amber-100">
                {{ $devices->getCollection()->reject(fn ($device) => $device->notifications_enabled && $device->expo_push_token)->count() }}
            </p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.mobile-notifications.index') }}" class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 md:grid-cols-[1fr_220px_auto] dark:border-gray-700 dark:bg-gray-800">
        <input
            type="search"
            name="search"
            value="{{ request('search') }}"
            placeholder="Rechercher un utilisateur, email ou appareil..."
            class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
        >
        <select name="status" class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
            <option value="">Tous les états</option>
            <option value="active" @selected(request('status') === 'active')>Notifications actives</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Notifications inactives</option>
        </select>
        <button type="submit" class="rounded-lg bg-gray-800 px-5 py-2 text-sm font-semibold text-white hover:bg-gray-900">
            Filtrer
        </button>
    </form>

    <form
        method="POST"
        action="{{ route('admin.mobile-notifications.send') }}"
        data-swal-loading="Envoi des notifications en cours..."
        class="space-y-5"
    >
        @csrf
        <div class="grid gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm md:grid-cols-2 dark:border-gray-700 dark:bg-gray-800">
            <div>
                <label for="title" class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-200">Titre</label>
                <input
                    id="title"
                    name="title"
                    value="{{ old('title') }}"
                    maxlength="100"
                    required
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                    placeholder="Ex. Nouvelle campagne disponible"
                >
            </div>
            <div>
                <label for="body" class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-200">Message</label>
                <textarea
                    id="body"
                    name="body"
                    rows="3"
                    maxlength="1000"
                    required
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                    placeholder="Saisissez le contenu de la notification..."
                >{{ old('body') }}</textarea>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-col gap-3 border-b border-gray-200 bg-gray-50 px-5 py-4 md:flex-row md:items-center md:justify-between dark:border-gray-700 dark:bg-gray-900/50">
                <div>
                    <h2 class="font-semibold text-gray-900 dark:text-white">Appareils destinataires</h2>
                    <p class="text-xs text-gray-500">Les appareils sans jeton actif restent visibles mais ne peuvent pas être sélectionnés.</p>
                </div>
                <label class="inline-flex items-center gap-2 text-sm font-semibold text-primary-700 dark:text-primary-300">
                    <input id="select-all-devices" type="checkbox" class="rounded border-gray-300 text-primary-600">
                    Sélectionner les appareils actifs de cette page
                </label>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-700 dark:bg-gray-900 dark:text-gray-200">
                        <tr>
                            <th class="px-4 py-3">Choix</th>
                            <th class="px-4 py-3">Utilisateur</th>
                            <th class="px-4 py-3">Appareil</th>
                            <th class="px-4 py-3">Plateforme</th>
                            <th class="px-4 py-3">Application</th>
                            <th class="px-4 py-3">Dernière connexion</th>
                            <th class="px-4 py-3">État</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($devices as $device)
                            @php($eligible = $device->notifications_enabled && filled($device->expo_push_token))
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="px-4 py-3">
                                    <input
                                        type="checkbox"
                                        name="device_ids[]"
                                        value="{{ $device->id }}"
                                        class="device-checkbox rounded border-gray-300 text-primary-600"
                                        @checked(in_array($device->id, old('device_ids', [])))
                                        @disabled(! $eligible)
                                    >
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $device->user->name ?? '-' }}</div>
                                    <div class="text-xs text-gray-500">{{ $device->user->email ?? '-' }}</div>
                                    <div class="text-xs text-gray-500">{{ $device->user->organisation->name ?? 'Sans organisation' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-800 dark:text-gray-100">{{ $device->device_name ?: 'Appareil sans nom' }}</div>
                                    <div class="font-mono text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($device->device_uuid, 18) }}</div>
                                </td>
                                <td class="px-4 py-3 uppercase">{{ $device->platform }}</td>
                                <td class="px-4 py-3">{{ $device->app_version ?: '-' }}</td>
                                <td class="px-4 py-3">{{ optional($device->last_login_at)->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3">
                                    @if($eligible)
                                        <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">Actif</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Permission absente</span>
                                    @endif
                                    @if($device->last_error)
                                        <p class="mt-1 max-w-xs text-xs text-red-600">{{ $device->last_error }}</p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                    Aucun appareil ne correspond aux filtres.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-700">
                {{ $devices->links() }}
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="rounded-lg bg-primary-600 px-6 py-3 text-sm font-semibold text-white hover:bg-primary-700">
                Envoyer aux appareils sélectionnés
            </button>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <h2 class="font-semibold text-gray-900 dark:text-white">Historique des envois</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Titre</th>
                        <th class="px-4 py-3">Expéditeur</th>
                        <th class="px-4 py-3">Destinataires</th>
                        <th class="px-4 py-3">Envoyées</th>
                        <th class="px-4 py-3">Échecs</th>
                        <th class="px-4 py-3">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($history as $notification)
                        <tr>
                            <td class="px-4 py-3">{{ optional($notification->sent_at ?? $notification->created_at)->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 font-semibold">{{ $notification->title }}</td>
                            <td class="px-4 py-3">{{ $notification->creator->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $notification->recipient_count }}</td>
                            <td class="px-4 py-3 text-green-700">{{ $notification->sent_count }}</td>
                            <td class="px-4 py-3 text-red-700">{{ $notification->failed_count }}</td>
                            <td class="px-4 py-3">{{ ucfirst($notification->status) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">Aucune notification envoyée.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('select-all-devices')?.addEventListener('change', function () {
        document.querySelectorAll('.device-checkbox:not(:disabled)').forEach((checkbox) => {
            checkbox.checked = this.checked;
        });
    });
</script>
@endpush
@endsection
