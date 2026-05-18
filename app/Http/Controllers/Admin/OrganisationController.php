<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cluster;
use App\Models\Organisation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrganisationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $organisations = Organisation::withCount('users')
            ->orderBy('is_active', 'desc')
            ->orderBy('name', 'asc')
            ->paginate(15);

        return view('admin.organisations.index', compact('organisations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clusters = Cluster::where('is_active', true)->orderBy('name')->get();
        return view('admin.organisations.create', compact('clusters'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:organisations,code'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
            'cluster_ids' => ['nullable', 'array'],
            'cluster_ids.*' => ['integer', 'exists:clusters,id'],
        ]);

        $validated['is_active'] = $request->has('is_active') ? true : false;

        $organisation = Organisation::create($validated);
        $organisation->clusters()->sync($request->input('cluster_ids', []));

        return redirect()->route('admin.organisations.index')
            ->with('success', 'Organisation créée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Organisation $organisation)
    {
        $organisation->load(['users' => function($query) {
            $query->orderBy('name', 'asc');
        }]);

        return view('admin.organisations.show', compact('organisation'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Organisation $organisation)
    {
        $clusters = Cluster::where('is_active', true)->orderBy('name')->get();
        $selectedClusterIds = $organisation->clusters()->pluck('clusters.id')->toArray();
        return view('admin.organisations.edit', compact('organisation', 'clusters', 'selectedClusterIds'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Organisation $organisation)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('organisations')->ignore($organisation)],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
            'cluster_ids' => ['nullable', 'array'],
            'cluster_ids.*' => ['integer', 'exists:clusters,id'],
        ]);

        $validated['is_active'] = $request->has('is_active') ? true : false;

        $organisation->update($validated);
        $organisation->clusters()->sync($request->input('cluster_ids', []));

        return redirect()->route('admin.organisations.index')
            ->with('success', 'Organisation mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Organisation $organisation)
    {
        // Vérifier si l'organisation a des utilisateurs
        if ($organisation->users()->count() > 0) {
            return redirect()->route('admin.organisations.index')
                ->with('error', 'Impossible de supprimer cette organisation car elle contient des utilisateurs.');
        }

        $organisation->delete();

        return redirect()->route('admin.organisations.index')
            ->with('success', 'Organisation supprimée avec succès.');
    }

    /**
     * Toggle the active status of the organisation.
     */
    public function toggleStatus(Organisation $organisation)
    {
        $organisation->update([
            'is_active' => !$organisation->is_active
        ]);

        $status = $organisation->is_active ? 'activée' : 'désactivée';

        return redirect()->back()
            ->with('success', "Organisation {$status} avec succès.");
    }
}
