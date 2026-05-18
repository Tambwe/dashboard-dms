<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\OrganisationController;
use App\Http\Controllers\Admin\SiteController;
use App\Http\Controllers\Admin\UserSiteAccessController;
use App\Http\Controllers\Admin\OssatChoixController;
use App\Http\Controllers\OrganisationSiteController;
use App\Http\Controllers\UserSiteController;
use App\Http\Controllers\SiteMouvementPopulationController;
use App\Http\Controllers\SiteMasterListController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ServiceProfileController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\OrganisationProjectController;
use App\Http\Controllers\OrganisationDashboardController;
use App\Http\Controllers\ProjectActivityImportController;
use App\Http\Controllers\ProgramImportController;
use App\Http\Controllers\Admin\ProgramIndicatorController;
use App\Http\Controllers\Admin\ProgramActivityController;
use App\Http\Controllers\Admin\ProgramSubActivityController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Page d'accueil → redirige vers le dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Page À propos (welcome)
Route::get('/about', function () {
    return view('welcome');
})->name('about');

// Manuel utilisateur public
Route::get('/aide', function () {
    return response()->file(base_path('MANUEL_UTILISATEUR_AVEC_CAPTURES.html'));
})->name('help.manual');

// Dashboard public (accessible sans authentification)
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Profil public des sites OSSAT (sans authentification)
Route::get('/profil-site', [PublicSiteController::class, 'index'])->name('public.site.index');
Route::get('/profil-site/{site}', [PublicSiteController::class, 'show'])->name('public.site.show');
Route::get('/cartographie', [PublicSiteController::class, 'cartographie'])->name('public.cartographie');
Route::get('/cartographie-mapbox', [PublicSiteController::class, 'cartographieMapbox'])->name('public.cartographie.mapbox');

// API publiques cascade Province → Territoire → Site (sans auth)
Route::get('/api/public/territoires', function (\Illuminate\Http\Request $request) {
    return response()->json(
        \App\Models\Territoire::where('province_id', $request->province_id)
            ->orderBy('name')->get(['id', 'name'])
    );
});
Route::get('/api/public/sites', function (\Illuminate\Http\Request $request) {
    return response()->json(
        \App\Models\Site::whereHas('commune', function ($q) use ($request) {
            $q->where('territoire_id', $request->territoire_id);
        })->select('id', 'nom', 'code_site')->orderBy('nom')->get()
    );
});

// Master List des sites (authentification requise)
Route::middleware(['auth'])->group(function () {
    Route::get('/sites/master-list', [SiteMasterListController::class, 'index'])->name('sites.master-list');
    Route::get('/sites/master-list/export', [SiteMasterListController::class, 'exportExcel'])->name('sites.master-list.export');
});

// Routes d'authentification
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// Routes de changement de mot de passe (accessible uniquement aux utilisateurs authentifiés)
Route::middleware(['auth'])->group(function () {
    Route::get('/change-password', [ChangePasswordController::class, 'show'])->name('password.change.show');
    Route::post('/change-password', [ChangePasswordController::class, 'update'])->name('password.change');
});

// Routes d'administration - Accessible par super admin et admin organisation
Route::middleware(['auth', 'check.role:super_admin,admin_organisation'])->prefix('admin')->name('admin.')->group(function () {
    // Gestion des utilisateurs
    Route::resource('users', UserController::class);
    
    // Gestion des mouvements de population
    Route::get('mouvements/import/template', [SiteMouvementPopulationController::class, 'downloadTemplate'])
        ->name('mouvements.import.template');
    Route::post('mouvements/import', [SiteMouvementPopulationController::class, 'import'])
        ->name('mouvements.import');
    Route::resource('mouvements', SiteMouvementPopulationController::class);
    
    // Routes de validation (réservées au super admin)
    Route::middleware('check.role:super_admin')->group(function () {
        Route::post('mouvements/{id}/validate', [SiteMouvementPopulationController::class, 'approuve'])
            ->name('mouvements.validate');
        Route::post('mouvements/{id}/reject', [SiteMouvementPopulationController::class, 'reject'])
            ->name('mouvements.reject');
    });
    
    // Dashboard admin
    Route::get('/dashboard', function () {
        $user = auth()->user();
        $stats = [
            'total_users' => $user->getManagedUsersQuery()->count(),
            'active_users' => $user->getManagedUsersQuery()->where('is_active', true)->count(),
            'recent_users' => $user->getManagedUsersQuery()->latest()->take(5)->get(),
        ];
        return view('admin.dashboard', compact('stats'));
    })->name('dashboard');
});

// Routes réservées au super admin uniquement
Route::middleware(['auth', 'check.role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    // Cadre de programmation : import Excel
    Route::get('programme/import', [ProgramImportController::class, 'showImport'])->name('programme.import.show');
    Route::post('programme/import', [ProgramImportController::class, 'import'])->name('programme.import.process');
    Route::get('programme/import/template', [ProgramImportController::class, 'downloadTemplate'])->name('programme.import.template');

    // Cadre de programmation : CRUD Indicateurs
    Route::get('programme/indicateurs', [ProgramIndicatorController::class, 'index'])->name('programme.indicateurs.index');
    Route::get('programme/indicateurs/create', [ProgramIndicatorController::class, 'create'])->name('programme.indicateurs.create');
    Route::post('programme/indicateurs', [ProgramIndicatorController::class, 'store'])->name('programme.indicateurs.store');
    Route::get('programme/indicateurs/{indicator}/edit', [ProgramIndicatorController::class, 'edit'])->name('programme.indicateurs.edit');
    Route::put('programme/indicateurs/{indicator}', [ProgramIndicatorController::class, 'update'])->name('programme.indicateurs.update');
    Route::delete('programme/indicateurs/{indicator}', [ProgramIndicatorController::class, 'destroy'])->name('programme.indicateurs.destroy');

    // Cadre de programmation : CRUD Activités
    Route::get('programme/activites', [ProgramActivityController::class, 'index'])->name('programme.activites.index');
    Route::get('programme/activites/create', [ProgramActivityController::class, 'create'])->name('programme.activites.create');
    Route::post('programme/activites', [ProgramActivityController::class, 'store'])->name('programme.activites.store');
    Route::get('programme/activites/{activity}/edit', [ProgramActivityController::class, 'edit'])->name('programme.activites.edit');
    Route::put('programme/activites/{activity}', [ProgramActivityController::class, 'update'])->name('programme.activites.update');
    Route::delete('programme/activites/{activity}', [ProgramActivityController::class, 'destroy'])->name('programme.activites.destroy');

    // Cadre de programmation : CRUD Sous-activités
    Route::get('programme/sous-activites', [ProgramSubActivityController::class, 'index'])->name('programme.sous-activites.index');
    Route::get('programme/sous-activites/create', [ProgramSubActivityController::class, 'create'])->name('programme.sous-activites.create');
    Route::post('programme/sous-activites', [ProgramSubActivityController::class, 'store'])->name('programme.sous-activites.store');
    Route::get('programme/sous-activites/{sousActivite}/edit', [ProgramSubActivityController::class, 'edit'])->name('programme.sous-activites.edit');
    Route::put('programme/sous-activites/{sousActivite}', [ProgramSubActivityController::class, 'update'])->name('programme.sous-activites.update');
    Route::delete('programme/sous-activites/{sousActivite}', [ProgramSubActivityController::class, 'destroy'])->name('programme.sous-activites.destroy');

    Route::resource('organisations', OrganisationController::class);
    Route::post('organisations/{organisation}/toggle-status', [OrganisationController::class, 'toggleStatus'])->name('organisations.toggle-status');
    
    // Gestion des sites et attribution aux organisations
    Route::get('sites', [SiteController::class, 'index'])->name('sites.index');
    Route::post('sites/{site}/assign-to-organisation', [SiteController::class, 'assignToOrganisation'])->name('sites.assign-to-organisation');
    Route::delete('sites/{site}/remove-from-organisation', [SiteController::class, 'removeFromOrganisation'])->name('sites.remove-from-organisation');
    Route::post('sites/bulk-assign', [SiteController::class, 'bulkAssign'])->name('sites.bulk-assign');
    
    // Référentiels de choix OSSAT (listes déroulantes)
    Route::resource('ossat-choix', OssatChoixController::class);
    Route::post('ossat-choix/{ossatChoix}/toggle', [OssatChoixController::class, 'toggle'])->name('ossat-choix.toggle');

    // Gestion des accès utilisateurs aux sites (attribution individuelle)
    Route::get('user-site-access', [UserSiteAccessController::class, 'index'])->name('user-site-access.index');
    Route::get('user-site-access/{user}/manage', [UserSiteAccessController::class, 'manage'])->name('user-site-access.manage');
    Route::post('user-site-access/{user}/grant', [UserSiteAccessController::class, 'grantAccess'])->name('user-site-access.grant');
    Route::delete('user-site-access/{user}/sites/{site}/revoke', [UserSiteAccessController::class, 'revokeAccess'])->name('user-site-access.revoke');
    Route::post('user-site-access/{user}/sites/{site}/update', [UserSiteAccessController::class, 'updateAccess'])->name('user-site-access.update');
    Route::post('user-site-access/{user}/bulk-grant', [UserSiteAccessController::class, 'bulkGrantAccess'])->name('user-site-access.bulk-grant');
    Route::get('sites/{site}/users', [UserSiteAccessController::class, 'siteUsers'])->name('sites.users');
});

// Routes pour les utilisateurs individuels ayant accès à des sites spécifiques
Route::middleware(['auth'])->prefix('my')->name('user.')->group(function () {
    // Sites assignés à l'utilisateur pour la collecte de données
    Route::get('sites', [UserSiteController::class, 'index'])->name('sites.index');
    Route::get('sites/{site}/edit', [UserSiteController::class, 'edit'])->name('sites.edit');
    Route::put('sites/{site}', [UserSiteController::class, 'update'])->name('sites.update');
    Route::delete('sites/{site}/delete-photo', [UserSiteController::class, 'deletePhoto'])->name('sites.delete-photo');
});

Route::middleware(['auth', 'check.role:super_admin,admin_organisation,user'])
    ->prefix('imports/project-activities')
    ->name('project-activities-import.')
    ->group(function () {
        Route::get('/', [ProjectActivityImportController::class, 'index'])->name('index');
        Route::get('/template', [ProjectActivityImportController::class, 'downloadTemplate'])->name('template');
        Route::post('/', [ProjectActivityImportController::class, 'import'])->name('process');
    });

// Routes pour les organisations (utilisateurs avec organisation_id)
Route::middleware(['auth', 'check.role:super_admin,admin_organisation,user'])->prefix('organisation')->name('organisation.')->group(function () {
    // Tableau de bord organisation
    Route::get('dashboard', [OrganisationDashboardController::class, 'index'])->name('dashboard');

    // Gestion des sites de l'organisation
    Route::get('sites', [OrganisationSiteController::class, 'index'])->name('sites.index');
    Route::get('sites/{site}/edit', [OrganisationSiteController::class, 'edit'])->name('sites.edit');
    Route::put('sites/{site}', [OrganisationSiteController::class, 'update'])->name('sites.update');
    Route::delete('sites/{site}/delete-photo', [OrganisationSiteController::class, 'deletePhoto'])->name('sites.delete-photo');

    // Gestion des projets de l'organisation
    Route::get('projects/{project}/activities-data', [OrganisationProjectController::class, 'activitiesData'])
        ->name('projects.activities.data');
    Route::post('projects/{project}/activities', [OrganisationProjectController::class, 'updateActivities'])
        ->name('projects.activities.update');

    Route::resource('projects', OrganisationProjectController::class)
        ->except(['show']);
});

// Routes pour les profils de services (monitoring des services dans les sites)
Route::middleware(['auth'])->prefix('service-profiles')->name('service-profiles.')->group(function () {
    Route::get('/', [ServiceProfileController::class, 'index'])->name('index');
    Route::get('/create', [ServiceProfileController::class, 'create'])->name('create');
    Route::post('/', [ServiceProfileController::class, 'store'])->name('store');
    Route::get('/{serviceProfile}', [ServiceProfileController::class, 'show'])->name('show');
    Route::get('/{serviceProfile}/edit', [ServiceProfileController::class, 'edit'])->name('edit');
    Route::put('/{serviceProfile}', [ServiceProfileController::class, 'update'])->name('update');
    Route::delete('/{serviceProfile}', [ServiceProfileController::class, 'destroy'])->name('destroy');
    
    // Actions de workflow
    Route::post('/{serviceProfile}/submit', [ServiceProfileController::class, 'submit'])->name('submit');
    
    // Actions réservées au super admin
    Route::middleware('check.role:super_admin')->group(function () {
        Route::post('/{serviceProfile}/approve', [ServiceProfileController::class, 'approve'])->name('approve');
        Route::post('/{serviceProfile}/reject', [ServiceProfileController::class, 'reject'])->name('reject');
    });
});

// Routes pour la gestion des ménages
Route::middleware(['auth'])->prefix('households')->name('households.')->group(function () {
    // Routes Niveau 2 - Vue d'ensemble (DOIT être avant les routes avec paramètres)
    Route::get('/level2', [\App\Http\Controllers\HouseholdController::class, 'indexLevel2'])->name('level2.index');

    // Analyse de doublons biométriques
    Route::get('/fingerprint-duplicates', [\App\Http\Controllers\HouseholdController::class, 'fingerprintDuplicates'])->name('fingerprint-duplicates');
    Route::post('/fingerprint-duplicates', [\App\Http\Controllers\HouseholdController::class, 'runFingerprintDuplicates'])->name('fingerprint-duplicates.run');
    Route::get('/fingerprint-sdk-test', [\App\Http\Controllers\HouseholdController::class, 'fingerprintSdkTest'])->name('fingerprint-sdk-test');
    Route::post('/fingerprint-sdk-test', [\App\Http\Controllers\HouseholdController::class, 'runFingerprintSdkTest'])->name('fingerprint-sdk-test.run');
    Route::post('/fingerprint-sdk-test/capture', [\App\Http\Controllers\HouseholdController::class, 'sdkTestCapture'])->name('fingerprint-sdk-test.capture');
    
    // Routes Niveau 1 - Ménages
    Route::get('/', [\App\Http\Controllers\HouseholdController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\HouseholdController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\HouseholdController::class, 'store'])->name('store');
    Route::get('/{household}', [\App\Http\Controllers\HouseholdController::class, 'show'])->name('show');
    Route::get('/{household}/edit', [\App\Http\Controllers\HouseholdController::class, 'edit'])->name('edit');
    Route::put('/{household}', [\App\Http\Controllers\HouseholdController::class, 'update'])->name('update');
    Route::delete('/{household}', [\App\Http\Controllers\HouseholdController::class, 'destroy'])->name('destroy')->middleware('check.role:super_admin');
    
    // Upgrade vers Niveau 2
    Route::get('/{household}/upgrade-to-level2', [\App\Http\Controllers\HouseholdController::class, 'upgradeToLevel2'])->name('upgrade-to-level2');
    Route::post('/{household}/upgrade-to-level2', [\App\Http\Controllers\HouseholdController::class, 'processUpgradeToLevel2'])->name('process-upgrade-to-level2');
    
    // Routes Niveau 2 - Membres du ménage
    Route::get('/{household}/members/create', [\App\Http\Controllers\HouseholdController::class, 'createMember'])->name('members.create');
    Route::post('/{household}/members', [\App\Http\Controllers\HouseholdController::class, 'storeMember'])->name('members.store');
    Route::get('/{household}/members/{member}/edit', [\App\Http\Controllers\HouseholdController::class, 'editMember'])->name('members.edit');
    Route::put('/{household}/members/{member}', [\App\Http\Controllers\HouseholdController::class, 'updateMember'])->name('members.update');
    Route::delete('/{household}/members/{member}', [\App\Http\Controllers\HouseholdController::class, 'destroyMember'])->name('members.destroy');
    
    // Capture biométrique
    Route::post('/capture-photo', [\App\Http\Controllers\HouseholdController::class, 'capturePhoto'])->name('capture-photo');
    Route::post('/capture-fingerprint', [\App\Http\Controllers\HouseholdController::class, 'captureFingerprint'])->name('capture-fingerprint');
    Route::post('/check-fingerprint-duplicate', [\App\Http\Controllers\HouseholdController::class, 'checkFingerprintDuplicate'])->name('check-fingerprint-duplicate');
});

// Routes OSSAT - Outil de Suivi des Sites d'Accueil Temporaire
Route::middleware(['auth'])->group(function () {
    Route::resource('ossat', \App\Http\Controllers\OssatReportController::class);
});

// API pour charger les territoires selon la province
Route::get('/api/territoires', function(\Illuminate\Http\Request $request) {
    $territoires = \App\Models\Territoire::where('province_id', $request->province_id)->orderBy('name')->get();
    return response()->json($territoires);
});

// API pour charger les sites selon le territoire (utilisé par OSSAT)
Route::middleware(['auth'])->get('/api/sites-par-territoire', function(\Illuminate\Http\Request $request) {
    $user = auth()->user();

    $query = \App\Models\Site::whereHas('commune', function ($q) use ($request) {
        $q->where('territoire_id', $request->territoire_id);
    });

    if ($user->isSuperAdmin()) {
        // Super admin voit tous les sites
    } elseif ($user->isAdminOrganisation()) {
        // Admin organisation : sites attribués à l'organisation OU à un membre de l'organisation
        $orgUserIds = \App\Models\User::where('organisation_id', $user->organisation_id)->pluck('id');
        $accessSiteIds = \Illuminate\Support\Facades\DB::table('site_user_access')
            ->whereIn('user_id', $orgUserIds)
            ->pluck('site_id');

        $query->where(function ($q) use ($user, $accessSiteIds) {
            $q->where('organisation_id', $user->organisation_id);
            if ($accessSiteIds->isNotEmpty()) {
                $q->orWhereIn('id', $accessSiteIds);
            }
        });
    } else {
        // Utilisateur standard : uniquement les sites qui lui sont attribués (can_collect)
        $query->whereHas('assignedUsers', function ($q) use ($user) {
            $q->where('users.id', $user->id)
              ->where('site_user_access.can_collect', true);
        });
    }

    $sites = $query->select('id', 'nom', 'code_site')->orderBy('nom')->get();
    return response()->json($sites);
});
