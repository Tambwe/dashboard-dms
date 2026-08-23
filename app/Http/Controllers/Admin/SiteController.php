<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\Organisation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        // Filtre par état d'ouverture/fermeture
        if ($request->filled('closure_status')) {
            if ($request->closure_status === 'open') {
                $query->whereNull('date_fermeture');
            } elseif ($request->closure_status === 'closed') {
                $query->whereNotNull('date_fermeture');
            }
        }

        // Recherche par nom
        if ($request->filled('search')) {
            $query->where('nom', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('gps_status')) {
            if ($request->gps_status === 'missing') {
                $query->where(function ($gpsQuery) {
                    $gpsQuery->whereNull('latitude')
                        ->orWhereNull('longitude')
                        ->orWhere('latitude', 0)
                        ->orWhere('longitude', 0);
                });
            } elseif ($request->gps_status === 'present') {
                $query->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->where('latitude', '!=', 0)
                    ->where('longitude', '!=', 0);
            }
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

    /**
     * Déclare un site fermé à partir d'une date.
     */
    public function close(Request $request, Site $site)
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'date_fermeture' => 'required|date',
            'raison_fermeture' => 'required|string|max:255',
            'commentaire_fermeture' => 'required|string',
            'document_fermeture' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $documentPath = $site->document_fermeture;

        if ($request->hasFile('document_fermeture')) {
            if ($documentPath) {
                Storage::disk('public')->delete($documentPath);
            }

            $documentPath = $request->file('document_fermeture')->store('sites/closure-documents', 'public');
        }

        $site->update([
            'date_fermeture' => $validated['date_fermeture'],
            'raison_fermeture' => $validated['raison_fermeture'],
            'commentaire_fermeture' => $validated['commentaire_fermeture'],
            'document_fermeture' => $documentPath,
        ]);

        $dateFermeture = $site->date_fermeture
            ? \Illuminate\Support\Carbon::parse($site->date_fermeture)->format('d/m/Y')
            : $validated['date_fermeture'];

        return redirect()->back()->with('success', 'Le site a été déclaré fermé à partir du ' . $dateFermeture . '.');
    }

    /**
     * Réouvre un site (retire la date de fermeture).
     */
    public function reopen(Site $site)
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        if ($site->document_fermeture) {
            Storage::disk('public')->delete($site->document_fermeture);
        }

        $site->update([
            'date_fermeture' => null,
            'raison_fermeture' => null,
            'commentaire_fermeture' => null,
            'document_fermeture' => null,
        ]);

        return redirect()->back()->with('success', 'Le site a été réouvert avec succès.');
    }
}
