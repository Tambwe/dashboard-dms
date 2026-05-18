<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProgramIndicator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProgramIndicatorController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'check.role:super_admin']);
    }

    public function index()
    {
        $indicators = ProgramIndicator::withCount('activities')
            ->orderBy('code')
            ->paginate(25);

        return view('admin.programme.indicateurs.index', compact('indicators'));
    }

    public function create()
    {
        return view('admin.programme.indicateurs.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:100', 'unique:program_indicators,reference'],
            'code'      => ['required', 'string', 'max:100', 'unique:program_indicators,code'],
            'label'     => ['required', 'string', 'max:500'],
            'unit'      => ['nullable', 'string', 'max:100'],
            'frequency' => ['nullable', 'string', 'max:100'],
            'owner'     => ['nullable', 'string', 'max:255'],
            'verification_source' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->has('is_active');

        ProgramIndicator::create($validated);

        return redirect()->route('admin.programme.indicateurs.index')
            ->with('success', 'Indicateur créé avec succès.');
    }

    public function edit(ProgramIndicator $indicator)
    {
        return view('admin.programme.indicateurs.edit', compact('indicator'));
    }

    public function update(Request $request, ProgramIndicator $indicator)
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:100', Rule::unique('program_indicators', 'reference')->ignore($indicator)],
            'code'      => ['required', 'string', 'max:100', Rule::unique('program_indicators', 'code')->ignore($indicator)],
            'label'     => ['required', 'string', 'max:500'],
            'unit'      => ['nullable', 'string', 'max:100'],
            'frequency' => ['nullable', 'string', 'max:100'],
            'owner'     => ['nullable', 'string', 'max:255'],
            'verification_source' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->has('is_active');

        $indicator->update($validated);

        return redirect()->route('admin.programme.indicateurs.index')
            ->with('success', 'Indicateur mis à jour avec succès.');
    }

    public function destroy(ProgramIndicator $indicator)
    {
        if ($indicator->activities()->count() > 0) {
            return redirect()->route('admin.programme.indicateurs.index')
                ->with('error', 'Impossible de supprimer cet indicateur car il contient des activités. Supprimez d\'abord les activités liées.');
        }

        $indicator->delete();

        return redirect()->route('admin.programme.indicateurs.index')
            ->with('success', 'Indicateur supprimé avec succès.');
    }
}
