<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Veuillez vous connecter.');
        }

        $user = auth()->user();

        // Vérifier si l'utilisateur est actif
        if (!$user->is_active) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Votre compte est désactivé.');
        }

        // Vérifier si l'utilisateur a l'un des rôles autorisés
        if (!in_array($user->role, $roles)) {
            abort(403, 'Accès non autorisé.');
        }

        return $next($request);
    }
}
