<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\Organisation;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    /**
     * Display a listing of all sites with their assignment status.
     */
    public function index(Request $request)
    {
        $query = Site::with(['organisation', 'typeSite', 'commune']);

        // Filtre par organisation
        if ($request->filled('organisation_id')) {
            $query->where('organisation_id', $request->organisation_id);
        }

        // Filtre par statut d'attribution
        if ($request->filled('status')) {
            if ($request->status === 'assigned') {
                $query->whereNotNull('organisation_id');
            } elseif ($request->status === 'unassigned') {
                $query->whereNull('organisation_id');
            }
        }

        // Recherche par nom
        if ($request->filled('search')) {
            $query->where('nom', 'like', '%' . $request->search . '%');
        }

        $sites = $query->paginate(20);
        $organisations = Organisation::where('is_active', true)->orderBy('name')->get();

        return view('admin.sites.index', compact('sites', 'organisations'));
    }

    /**
     * Assign a site to an organisation.
     */
    public function assignToOrganisation(Request $request, Site $site)
    {
        $request->validate([
            'organisation_id' => 'required|exists:organisations,id',
        ]);

        $site->update([
            'organisation_id' => $request->organisation_id,
        ]);

        return redirect()->back()->with('success', 'Site attribué à l\'organisation avec succès.');
    }

    /**
     * Remove organisation assignment from a site.
     */
    public function removeFromOrganisation(Site $site)
    {
        $site->update([
            'organisation_id' => null,
        ]);

        return redirect()->back()->with('success', 'Site retiré de l\'organisation avec succès.');
    }

    /**
     * Bulk assign sites to an organisation.
     */
    public function bulkAssign(Request $request)
    {
        $request->validate([
            'site_ids' => 'required|array',
            'site_ids.*' => 'exists:sites,id',
            'organisation_id' => 'required|exists:organisations,id',
        ]);

        Site::whereIn('id', $request->site_ids)->update([
            'organisation_id' => $request->organisation_id,
        ]);

        return redirect()->back()->with('success', count($request->site_ids) . ' site(s) attribué(s) avec succès.');
    }
}
