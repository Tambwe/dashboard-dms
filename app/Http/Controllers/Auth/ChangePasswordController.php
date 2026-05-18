<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ChangePasswordController extends Controller
{
    /**
     * Afficher le formulaire de changement de mot de passe
     */
    public function show()
    {
        return view('auth.change-password');
    }

    /**
     * Traiter le changement de mot de passe
     */
    public function update(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Le mot de passe actuel est requis.',
            'password.required' => 'Le nouveau mot de passe est requis.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
        ]);

        $user = auth()->user();

        // Vérifier que le mot de passe actuel est correct
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'Le mot de passe actuel est incorrect.'
            ]);
        }

        // Vérifier que le nouveau mot de passe est différent de l'ancien
        if (Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'password' => 'Le nouveau mot de passe doit être différent de l\'ancien.'
            ]);
        }

        // Mettre à jour le mot de passe
        $user->password = Hash::make($request->password);
        $user->must_change_password = false;
        $user->save();

        // Rediriger selon le rôle
        if ($user->isSuperAdmin()) {
            return redirect()->route('admin.dashboard')->with('success', 'Mot de passe mis à jour avec succès.');
        } elseif ($user->isAdminOrganisation()) {
            return redirect()->route('admin.users.index')->with('success', 'Mot de passe mis à jour avec succès.');
        } else {
            return redirect()->route('dashboard')->with('success', 'Mot de passe mis à jour avec succès.');
        }
    }
}
