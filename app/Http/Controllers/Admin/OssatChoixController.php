<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OssatChoix;
use Illuminate\Http\Request;

class OssatChoixController extends Controller
{
    /**
     * Liste tous les groupes avec leurs valeurs.
     */
    public function index(Request $request)
    {
        $query = OssatChoix::orderBy('groupe')->orderBy('ordre')->orderBy('valeur');

        if ($request->filled('groupe')) {
            $query->where('groupe', $request->groupe);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('valeur', 'like', '%' . $request->search . '%')
                  ->orWhere('libelle', 'like', '%' . $request->search . '%');
            });
        }

        $choix    = $query->paginate(50)->withQueryString();
        $groupes  = OssatChoix::distinct()->orderBy('groupe')->pluck('groupe');

        return view('admin.ossat-choix.index', compact('choix', 'groupes'));
    }

    /**
     * Formulaire de création.
     */
    public function create()
    {
        $groupes = OssatChoix::distinct()->orderBy('groupe')->pluck('groupe');
        return view('admin.ossat-choix.create', compact('groupes'));
    }

    /**
     * Enregistrement d'un nouveau choix.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'groupe'  => 'required|string|max:60',
            'valeur'  => 'required|string|max:120',
            'libelle' => 'nullable|string|max:180',
            'ordre'   => 'nullable|integer|min:0',
            'actif'   => 'nullable|boolean',
        ]);

        // Vérifier unicité groupe+valeur
        if (OssatChoix::where('groupe', $data['groupe'])->where('valeur', $data['valeur'])->exists()) {
            return back()->withInput()->withErrors(['valeur' => 'Cette valeur existe déjà dans ce groupe.']);
        }

        $data['ordre'] = $data['ordre'] ?? 0;
        $data['actif'] = $request->boolean('actif', true);

        OssatChoix::create($data);
        OssatChoix::clearCache();

        return redirect()->route('admin.ossat-choix.index', ['groupe' => $data['groupe']])
                         ->with('success', 'Choix ajouté avec succès.');
    }

    /**
     * Formulaire d'édition.
     */
    public function edit(OssatChoix $ossatChoix)
    {
        $groupes = OssatChoix::distinct()->orderBy('groupe')->pluck('groupe');
        return view('admin.ossat-choix.edit', compact('ossatChoix', 'groupes'));
    }

    /**
     * Mise à jour.
     */
    public function update(Request $request, OssatChoix $ossatChoix)
    {
        $data = $request->validate([
            'groupe'  => 'required|string|max:60',
            'valeur'  => 'required|string|max:120',
            'libelle' => 'nullable|string|max:180',
            'ordre'   => 'nullable|integer|min:0',
            'actif'   => 'nullable|boolean',
        ]);

        // Unicité groupe+valeur (hors soi-même)
        if (OssatChoix::where('groupe', $data['groupe'])
                      ->where('valeur', $data['valeur'])
                      ->where('id', '!=', $ossatChoix->id)
                      ->exists()) {
            return back()->withInput()->withErrors(['valeur' => 'Cette valeur existe déjà dans ce groupe.']);
        }

        $data['ordre'] = $data['ordre'] ?? 0;
        $data['actif'] = $request->boolean('actif', true);

        $ossatChoix->update($data);
        OssatChoix::clearCache();

        return redirect()->route('admin.ossat-choix.index', ['groupe' => $data['groupe']])
                         ->with('success', 'Choix mis à jour.');
    }

    /**
     * Activation / désactivation rapide (toggle).
     */
    public function toggle(OssatChoix $ossatChoix)
    {
        $ossatChoix->update(['actif' => !$ossatChoix->actif]);
        OssatChoix::clearCache();

        return back()->with('success', $ossatChoix->actif ? 'Choix activé.' : 'Choix désactivé.');
    }

    /**
     * Suppression.
     */
    public function destroy(OssatChoix $ossatChoix)
    {
        $groupe = $ossatChoix->groupe;
        $ossatChoix->delete();
        OssatChoix::clearCache();

        return redirect()->route('admin.ossat-choix.index', ['groupe' => $groupe])
                         ->with('success', 'Choix supprimé.');
    }
}
