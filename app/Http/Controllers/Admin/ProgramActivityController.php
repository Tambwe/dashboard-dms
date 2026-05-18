<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProgramActivity;
use App\Models\ProgramIndicator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProgramActivityController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'check.role:super_admin']);
    }

    public function index()
    {
        $activities = ProgramActivity::with('indicator')
            ->withCount('subActivities')
            ->orderBy('code')
            ->paginate(25);

        return view('admin.programme.activites.index', compact('activities'));
    }

    public function create()
    {
        $indicators = ProgramIndicator::where('is_active', true)->orderBy('code')->get(['id', 'code', 'label']);

        return view('admin.programme.activites.create', compact('indicators'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'reference'            => ['required', 'string', 'max:100', 'unique:program_activities,reference'],
            'code'                 => ['required', 'string', 'max:100', 'unique:program_activities,code'],
            'label'                => ['required', 'string', 'max:500'],
            'program_indicator_id' => ['required', 'exists:program_indicators,id'],
            'program_axis'         => ['nullable', 'string', 'max:255'],
            'project_lead'         => ['nullable', 'string', 'max:255'],
            'status'               => ['nullable', 'string', 'max:100'],
            'planned_start_date'   => ['nullable', 'date'],
            'planned_end_date'     => ['nullable', 'date', 'after_or_equal:planned_start_date'],
        ]);

        ProgramActivity::create($validated);

        return redirect()->route('admin.programme.activites.index')
            ->with('success', 'Activité créée avec succès.');
    }

    public function edit(ProgramActivity $activity)
    {
        $indicators = ProgramIndicator::where('is_active', true)->orderBy('code')->get(['id', 'code', 'label']);

        return view('admin.programme.activites.edit', compact('activity', 'indicators'));
    }

    public function update(Request $request, ProgramActivity $activity)
    {
        $validated = $request->validate([
            'reference'            => ['required', 'string', 'max:100', Rule::unique('program_activities', 'reference')->ignore($activity)],
            'code'                 => ['required', 'string', 'max:100', Rule::unique('program_activities', 'code')->ignore($activity)],
            'label'                => ['required', 'string', 'max:500'],
            'program_indicator_id' => ['required', 'exists:program_indicators,id'],
            'program_axis'         => ['nullable', 'string', 'max:255'],
            'project_lead'         => ['nullable', 'string', 'max:255'],
            'status'               => ['nullable', 'string', 'max:100'],
            'planned_start_date'   => ['nullable', 'date'],
            'planned_end_date'     => ['nullable', 'date', 'after_or_equal:planned_start_date'],
        ]);

        $activity->update($validated);

        return redirect()->route('admin.programme.activites.index')
            ->with('success', 'Activité mise à jour avec succès.');
    }

    public function destroy(ProgramActivity $activity)
    {
        if ($activity->subActivities()->count() > 0) {
            return redirect()->route('admin.programme.activites.index')
                ->with('error', 'Impossible de supprimer cette activité car elle contient des sous-activités. Supprimez d\'abord les sous-activités liées.');
        }

        $activity->delete();

        return redirect()->route('admin.programme.activites.index')
            ->with('success', 'Activité supprimée avec succès.');
    }
}
