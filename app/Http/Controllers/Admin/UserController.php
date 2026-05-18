<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        
        // Récupérer les utilisateurs selon le rôle
        $users = $user->getManagedUsersQuery()
            ->with('organisation')
            ->latest()
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();
        
        // Récupérer les organisations disponibles
        if ($user->isSuperAdmin()) {
            $organisations = Organisation::where('is_active', true)->get();
            $roles = ['super_admin', 'admin_organisation', 'user'];
        } else {
            $organisations = Organisation::where('id', $user->organisation_id)->get();
            $roles = ['admin_organisation', 'user'];
        }

        return view('admin.users.create', compact('organisations', 'roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(['super_admin', 'admin_organisation', 'user'])],
        ];

        // Si super admin, organisation est requise sauf pour super_admin
        if ($user->isSuperAdmin()) {
            $rules['organisation_id'] = ['nullable', 'exists:organisations,id'];
        } else {
            $rules['organisation_id'] = ['required', 'exists:organisations,id'];
        }

        $validated = $request->validate($rules, [
            'name.required' => 'Le nom est requis.',
            'email.required' => 'L\'email est requis.',
            'email.email' => 'L\'email doit être valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'password.required' => 'Le mot de passe est requis.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'role.required' => 'Le rôle est requis.',
            'organisation_id.required' => 'L\'organisation est requise.',
        ]);

        // Si admin organisation, forcer l'organisation
        if ($user->isAdminOrganisation()) {
            $validated['organisation_id'] = $user->organisation_id;
        }

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['must_change_password'] = true; // Forcer le changement de mot de passe à la première connexion

        User::create($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        // Vérifier les permissions
        $currentUser = auth()->user();
        if (!$currentUser->isSuperAdmin() && $user->organisation_id !== $currentUser->organisation_id) {
            abort(403, 'Accès non autorisé.');
        }

        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        // Vérifier les permissions
        $currentUser = auth()->user();
        if (!$currentUser->isSuperAdmin() && $user->organisation_id !== $currentUser->organisation_id) {
            abort(403, 'Accès non autorisé.');
        }

        // Récupérer les organisations disponibles
        if ($currentUser->isSuperAdmin()) {
            $organisations = Organisation::where('is_active', true)->get();
            $roles = ['super_admin', 'admin_organisation', 'user'];
        } else {
            $organisations = Organisation::where('id', $currentUser->organisation_id)->get();
            $roles = ['admin_organisation', 'user'];
        }

        return view('admin.users.edit', compact('user', 'organisations', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        // Vérifier les permissions
        $currentUser = auth()->user();
        if (!$currentUser->isSuperAdmin() && $user->organisation_id !== $currentUser->organisation_id) {
            abort(403, 'Accès non autorisé.');
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(['super_admin', 'admin_organisation', 'user'])],
        ];

        if ($currentUser->isSuperAdmin()) {
            $rules['organisation_id'] = ['nullable', 'exists:organisations,id'];
        } else {
            $rules['organisation_id'] = ['required', 'exists:organisations,id'];
        }

        // Si mot de passe fourni
        if ($request->filled('password')) {
            $rules['password'] = ['string', 'min:8', 'confirmed'];
        }

        $validated = $request->validate($rules);

        // Si admin organisation, forcer l'organisation
        if ($currentUser->isAdminOrganisation()) {
            $validated['organisation_id'] = $currentUser->organisation_id;
        }

        if (!$request->filled('password')) {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Vérifier les permissions
        $currentUser = auth()->user();
        if (!$currentUser->isSuperAdmin() && $user->organisation_id !== $currentUser->organisation_id) {
            abort(403, 'Accès non autorisé.');
        }

        // Empêcher la suppression de soi-même
        if ($user->id === $currentUser->id) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur supprimé avec succès.');
    }
}
