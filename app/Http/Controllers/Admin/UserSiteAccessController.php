<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserSiteAccessController extends Controller
{
    /**
     * Display the user site access management page.
     */
    public function index(Request $request)
    {
        $query = User::with(['organisation', 'assignedSites']);

        // Filtre par organisation
        if ($request->filled('organisation_id')) {
            $query->where('organisation_id', $request->organisation_id);
        }

        // Filtre par rôle
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Recherche par nom
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $users = $query->where('role', '!=', 'super_admin')
                      ->where('is_active', true)
                      ->paginate(20);

        $organisations = \App\Models\Organisation::where('is_active', true)->orderBy('name')->get();

        return view('admin.user-site-access.index', compact('users', 'organisations'));
    }

    /**
     * Show the form to manage a user's site access.
     */
    public function manage(User $user)
    {
        $user->load(['organisation', 'assignedSites.typeSite', 'assignedSites.commune']);
        
        // Tous les sites disponibles (pas seulement ceux de l'organisation de l'utilisateur)
        // Le super admin peut attribuer n'importe quel site à n'importe quel utilisateur
        $availableSites = Site::query()
            ->with(['typeSite', 'commune', 'organisation'])
            ->orderBy('nom')
            ->get();

        return view('admin.user-site-access.manage', compact('user', 'availableSites'));
    }

    /**
     * Grant access to a site for a user.
     */
    public function grantAccess(Request $request, User $user)
    {
        $request->validate([
            'site_id' => 'required|exists:sites,id',
            'can_edit' => 'boolean',
            'can_collect' => 'boolean',
        ]);

        $site = Site::findOrFail($request->site_id);

        // Vérifier si l'utilisateur a déjà accès
        if ($user->assignedSites()->where('sites.id', $site->id)->exists()) {
            return redirect()->back()->with('error', 'L\'utilisateur a déjà accès à ce site.');
        }

        $user->assignedSites()->attach($site->id, [
            'can_edit' => $request->boolean('can_edit', true),
            'can_collect' => $request->boolean('can_collect', true),
            'granted_by' => auth()->id(),
            'granted_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Accès au site accordé avec succès.');
    }

    /**
     * Revoke access to a site for a user.
     */
    public function revokeAccess(User $user, Site $site)
    {
        $user->assignedSites()->detach($site->id);

        return redirect()->back()->with('success', 'Accès au site retiré avec succès.');
    }

    /**
     * Update access permissions for a user on a site.
     */
    public function updateAccess(Request $request, User $user, Site $site)
    {
        $request->validate([
            'can_edit' => 'boolean',
            'can_collect' => 'boolean',
        ]);

        $user->assignedSites()->updateExistingPivot($site->id, [
            'can_edit' => $request->boolean('can_edit'),
            'can_collect' => $request->boolean('can_collect'),
        ]);

        return redirect()->back()->with('success', 'Permissions mises à jour avec succès.');
    }

    /**
     * Grant access to multiple sites for a user.
     */
    public function bulkGrantAccess(Request $request, User $user)
    {
        $request->validate([
            'site_ids' => 'required|array',
            'site_ids.*' => 'exists:sites,id',
            'can_edit' => 'boolean',
            'can_collect' => 'boolean',
        ]);

        $attachData = [];
        foreach ($request->site_ids as $siteId) {
            // Vérifier si l'utilisateur n'a pas déjà accès
            if (!$user->assignedSites()->where('sites.id', $siteId)->exists()) {
                $attachData[$siteId] = [
                    'can_edit' => $request->boolean('can_edit', true),
                    'can_collect' => $request->boolean('can_collect', true),
                    'granted_by' => auth()->id(),
                    'granted_at' => now(),
                ];
            }
        }

        if (!empty($attachData)) {
            $user->assignedSites()->attach($attachData);
            return redirect()->back()->with('success', count($attachData) . ' site(s) attribué(s) avec succès.');
        }

        return redirect()->back()->with('info', 'Aucun nouveau site à attribuer.');
    }

    /**
     * Show sites assigned to a specific site.
     */
    public function siteUsers(Site $site)
    {
        $site->load(['assignedUsers.organisation', 'organisation', 'typeSite']);
        
        return view('admin.user-site-access.site-users', compact('site'));
    }
}
