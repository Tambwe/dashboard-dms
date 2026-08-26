@php
    $user = auth()->user();
    $isSigUser = $user?->isSigUser() ?? false;
    $isSuperAdmin = $user?->isSuperAdmin() ?? false;
    $canManageUsers = $user?->canManageUsers() ?? false;
    $hasOrganisation = (bool) ($user?->organisation_id);

    $sitesActive = request()->is('cartographie', 'cartographie-mapbox', 'profil-site', 'profil-site/*')
        || request()->routeIs('sites.master-list', 'user.sites.*');
    $collectionActive = request()->routeIs('mobile.index', 'service-profiles.*', 'admin.mobile-questionnaires.*');
    $populationActive = request()->routeIs('admin.mouvements.*', 'households.*');
    $programmeActive = request()->routeIs('admin.programme.*', 'project-activities-import.*', 'organisation.dashboard', 'organisation.projects.*');
    $ossatActive = request()->routeIs('ossat.*', 'admin.ossat-choix.*');
    $administrationActive = request()->routeIs('admin.users.*', 'admin.organisations.*', 'admin.user-site-access.*', 'admin.mobile-notifications.*', 'mobile.synced-data*');
    $helpActive = request()->routeIs('help.manual') || request()->is('about');
@endphp

<x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="py-3 font-medium text-gray-700 dark:text-gray-300">
    Tableau de bord
</x-sidebar-link>

@auth
    <x-sidebar-link :href="route('mobile.apk.download')" :active="false" class="py-3 font-medium text-gray-700 dark:text-gray-300">
        📱 Télécharger l'application
    </x-sidebar-link>
@endauth

<x-sidebar-group title="Sites & Cartographie" icon="📍" :active="$sitesActive">
    <x-sidebar-link :href="url('/cartographie')" :active="request()->is('cartographie', 'cartographie-mapbox')">
        Cartographie
    </x-sidebar-link>
    <x-sidebar-link :href="url('/profil-site')" :active="request()->is('profil-site', 'profil-site/*')">
        Profil des sites
    </x-sidebar-link>
    <x-sidebar-link :href="route('sites.master-list')" :active="request()->routeIs('sites.master-list')">
        Master list
    </x-sidebar-link>
    @auth
        <x-sidebar-link
            :href="route('user.sites.index')"
            :active="request()->routeIs('user.sites.*') && ! request()->routeIs('user.sites.collected.*')"
        >
            Mes sites
        </x-sidebar-link>
        <x-sidebar-link :href="route('user.sites.collected.index')" :active="request()->routeIs('user.sites.collected.*')">
            Données sites synchronisées
        </x-sidebar-link>
    @endauth
</x-sidebar-group>

<x-sidebar-group title="Collecte" icon="📝" :active="$collectionActive">
    <x-sidebar-link :href="route('mobile.index')" :active="request()->routeIs('mobile.index')">
        Collecte mobile
    </x-sidebar-link>
    @auth
        @if(! $isSigUser)
            <x-sidebar-link :href="route('service-profiles.index')" :active="request()->routeIs('service-profiles.*')">
                Profils de services
            </x-sidebar-link>
        @endif
        @if($isSuperAdmin)
            <x-sidebar-link :href="route('admin.mobile-questionnaires.index')" :active="request()->routeIs('admin.mobile-questionnaires.*')">
                Questionnaires mobiles
            </x-sidebar-link>
        @endif
    @endauth
</x-sidebar-group>

@auth
    @if(! $isSigUser)
        <x-sidebar-group title="Population & Ménages" icon="👥" :active="$populationActive">
            @if($canManageUsers)
                <x-sidebar-link :href="route('admin.mouvements.index')" :active="request()->routeIs('admin.mouvements.*') && ! request()->routeIs('admin.mouvements.history')">
                    Mouvements de population
                </x-sidebar-link>
                <x-sidebar-link :href="route('admin.mouvements.history')" :active="request()->routeIs('admin.mouvements.history')">
                    Historique des mouvements
                </x-sidebar-link>
            @endif
            <x-sidebar-link :href="route('households.index')" :active="request()->routeIs('households.index', 'households.create', 'households.edit', 'households.show')">
                Ménages - Niveau 1
            </x-sidebar-link>
            <x-sidebar-link
                :href="route('households.level2.index')"
                :active="request()->routeIs('households.level2.*', 'households.members.*', 'households.upgrade*')"
            >
                Membres - Niveau 2
            </x-sidebar-link>
        </x-sidebar-group>

        @if($isSuperAdmin || $hasOrganisation)
            <x-sidebar-group title="Programmes & Projets" icon="📊" :active="$programmeActive">
                @if($isSuperAdmin)
                    <x-sidebar-link :href="route('admin.programme.import.show')" :active="request()->routeIs('admin.programme.import.*')">
                        Import du programme
                    </x-sidebar-link>
                    <x-sidebar-link :href="route('admin.programme.indicateurs.index')" :active="request()->routeIs('admin.programme.indicateurs.*')">
                        Indicateurs
                    </x-sidebar-link>
                    <x-sidebar-link :href="route('admin.programme.activites.index')" :active="request()->routeIs('admin.programme.activites.*')">
                        Activités
                    </x-sidebar-link>
                    <x-sidebar-link :href="route('admin.programme.sous-activites.index')" :active="request()->routeIs('admin.programme.sous-activites.*')">
                        Sous-activités
                    </x-sidebar-link>
                @endif
                <x-sidebar-link :href="route('project-activities-import.index')" :active="request()->routeIs('project-activities-import.*')">
                    Import activités Excel
                </x-sidebar-link>
                @if($hasOrganisation)
                    <x-sidebar-link :href="route('organisation.dashboard')" :active="request()->routeIs('organisation.dashboard')">
                        Tableau de bord organisation
                    </x-sidebar-link>
                    <x-sidebar-link :href="route('organisation.projects.index')" :active="request()->routeIs('organisation.projects.*')">
                        Projets organisation
                    </x-sidebar-link>
                @endif
            </x-sidebar-group>
        @endif

        <x-sidebar-group title="OSSAT" icon="🧭" :active="$ossatActive">
            <x-sidebar-link :href="route('ossat.index')" :active="request()->routeIs('ossat.index', 'ossat.edit', 'ossat.show')">
                Liste des rapports
            </x-sidebar-link>
            <x-sidebar-link :href="route('ossat.create')" :active="request()->routeIs('ossat.create')">
                Nouveau rapport
            </x-sidebar-link>
            @if($isSuperAdmin)
                <x-sidebar-link :href="route('admin.ossat-choix.index')" :active="request()->routeIs('admin.ossat-choix.*')">
                    Gérer les listes OSSAT
                </x-sidebar-link>
            @endif
        </x-sidebar-group>

        @if($canManageUsers)
            <x-sidebar-group title="Administration" icon="⚙️" :active="$administrationActive">
                <x-sidebar-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                    Utilisateurs
                </x-sidebar-link>
                <x-sidebar-link :href="route('mobile.synced-data')" :active="request()->routeIs('mobile.synced-data*')">
                    Données mobiles synchronisées
                </x-sidebar-link>
                <x-sidebar-link :href="route('admin.mobile-notifications.index')" :active="request()->routeIs('admin.mobile-notifications.*')">
                    Notifications mobiles
                </x-sidebar-link>
                @if($isSuperAdmin)
                    <x-sidebar-link :href="route('admin.organisations.index')" :active="request()->routeIs('admin.organisations.*')">
                        Organisations
                    </x-sidebar-link>
                    <x-sidebar-link :href="route('admin.user-site-access.index')" :active="request()->routeIs('admin.user-site-access.*')">
                        Attribution des sites
                    </x-sidebar-link>
                @endif
            </x-sidebar-group>
        @endif
    @endif
@endauth

<x-sidebar-group title="Aide" icon="❓" :active="$helpActive">
    <x-sidebar-link :href="route('help.manual')" :active="request()->routeIs('help.manual')">
        Manuel utilisateur
    </x-sidebar-link>
    <x-sidebar-link :href="url('/about')" :active="request()->is('about')">
        À propos
    </x-sidebar-link>
</x-sidebar-group>
