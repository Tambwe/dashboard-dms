<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckOrganisation
{
    /**
     * Handle an incoming request.
     *
     * Vérifie que l'utilisateur accède uniquement aux données de son organisation
     * (sauf pour les super admin)
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Les super admin peuvent tout voir
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Vérifier que l'utilisateur a une organisation
        if (!$user->organisation_id) {
            abort(403, 'Vous devez être rattaché à une organisation.');
        }

        // Pour les routes avec paramètre organisation_id
        if ($request->route('organisation')) {
            $organisationId = $request->route('organisation');
            if ($user->organisation_id != $organisationId) {
                abort(403, 'Accès interdit à cette organisation.');
            }
        }

        return $next($request);
    }
}
