<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProgramActivity;
use App\Models\ProgramSubActivity;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProgramSubActivityController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'check.role:super_admin']);
    }

    public function index()
    {
        $subActivities = ProgramSubActivity::with('activity.indicator')
            ->orderBy('code')
            ->paginate(25);

        return view('admin.programme.sous-activites.index', compact('subActivities'));
    }

    public function create()
    {
        $activities = ProgramActivity::with('indicator')->orderBy('code')->get(['id', 'code', 'label', 'program_indicator_id']);

        return view('admin.programme.sous-activites.create', compact('activities'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'reference'           => ['required', 'string', 'max:100', 'unique:program_sub_activities,reference'],
            'code'                => ['required', 'string', 'max:100', 'unique:program_sub_activities,code'],
            'label'               => ['required', 'string', 'max:500'],
            'program_activity_id' => ['required', 'exists:program_activities,id'],
            'site_name'           => ['nullable', 'string', 'max:255'],
            'province'            => ['nullable', 'string', 'max:255'],
            'territoire'          => ['nullable', 'string', 'max:255'],
            'health_zone'         => ['nullable', 'string', 'max:255'],
            'planned_start_date'  => ['nullable', 'date'],
            'planned_end_date'    => ['nullable', 'date', 'after_or_equal:planned_start_date'],
            'status'              => ['nullable', 'string', 'max:100'],
        ]);

        ProgramSubActivity::create($validated);

        return redirect()->route('admin.programme.sous-activites.index')
            ->with('success', 'Sous-activité créée avec succès.');
    }

    public function edit(ProgramSubActivity $sousActivite)
    {
        $activities = ProgramActivity::with('indicator')->orderBy('code')->get(['id', 'code', 'label', 'program_indicator_id']);

        return view('admin.programme.sous-activites.edit', [
            'subActivity' => $sousActivite,
            'activities'  => $activities,
        ]);
    }

    public function update(Request $request, ProgramSubActivity $sousActivite)
    {
        $validated = $request->validate([
            'reference'           => ['required', 'string', 'max:100', Rule::unique('program_sub_activities', 'reference')->ignore($sousActivite)],
            'code'                => ['required', 'string', 'max:100', Rule::unique('program_sub_activities', 'code')->ignore($sousActivite)],
            'label'               => ['required', 'string', 'max:500'],
            'program_activity_id' => ['required', 'exists:program_activities,id'],
            'site_name'           => ['nullable', 'string', 'max:255'],
            'province'            => ['nullable', 'string', 'max:255'],
            'territoire'          => ['nullable', 'string', 'max:255'],
            'health_zone'         => ['nullable', 'string', 'max:255'],
            'planned_start_date'  => ['nullable', 'date'],
            'planned_end_date'    => ['nullable', 'date', 'after_or_equal:planned_start_date'],
            'status'              => ['nullable', 'string', 'max:100'],
        ]);

        $sousActivite->update($validated);

        return redirect()->route('admin.programme.sous-activites.index')
            ->with('success', 'Sous-activité mise à jour avec succès.');
    }

    public function destroy(ProgramSubActivity $sousActivite)
    {
        $sousActivite->delete();

        return redirect()->route('admin.programme.sous-activites.index')
            ->with('success', 'Sous-activité supprimée avec succès.');
    }
}
