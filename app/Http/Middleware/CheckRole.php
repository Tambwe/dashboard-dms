<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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

        // Vérifier si l'utilisateur est actif quand la colonne existe
        if (Schema::hasColumn('users', 'is_active') && !$user->is_active) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Votre compte est désactivé.');
        }

        // Vérifier si l'utilisateur a l'un des rôles autorisés
        if (!$this->userHasAllowedRole($user, $roles)) {
            abort(403, 'Accès non autorisé.');
        }

        return $next($request);
    }

    private function userHasAllowedRole($user, array $roles): bool
    {
        $userRole = Schema::hasColumn('users', 'role') ? ($user->role ?? null) : null;
        if ($userRole && in_array($userRole, $roles, true)) {
            return true;
        }

        // Compatibilité: compte superadmin seedé même si role est nul/inexistant.
        if (in_array('super_admin', $roles, true) && strcasecmp((string) ($user->email ?? ''), 'superadmin@dms-cccm.org') === 0) {
            return true;
        }

        return false;
    }
}
