<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Display the login view.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'L\'email est requis.',
            'email.email' => 'L\'email doit être valide.',
            'password.required' => 'Le mot de passe est requis.',
        ]);

        // Tentative de connexion
        if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $user = Auth::user();

            // Vérifier si l'utilisateur est actif seulement si la colonne existe
            if (Schema::hasColumn('users', 'is_active') && !$user->is_active) {
                Auth::logout();
                throw ValidationException::withMessages([
                    'email' => 'Votre compte est désactivé. Contactez l\'administrateur.',
                ]);
            }

            $request->session()->regenerate();

            // Vérifier si l'utilisateur doit changer son mot de passe seulement si la colonne existe
            if (Schema::hasColumn('users', 'must_change_password') && $user->must_change_password) {
                return redirect()->route('password.change.show');
            }

            // Rediriger selon le rôle
            if ($user->isSuperAdmin()) {
                return redirect()->intended('/admin/dashboard');
            } elseif ($user->isAdminOrganisation()) {
                return redirect()->intended('/admin/users');
            } else {
                return redirect()->intended('/dashboard');
            }
        }

        throw ValidationException::withMessages([
            'email' => 'Ces identifiants ne correspondent pas à nos enregistrements.',
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
