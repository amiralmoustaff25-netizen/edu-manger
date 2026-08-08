<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Feature flag (config('edu.force_password_change'), piloté par FORCE_PASSWORD_CHANGE
        // dans .env) : permet de désactiver temporairement cette contrainte en dev/test sans
        // toucher au code ni aux comptes existants (password_must_change continue d'être posé
        // normalement à la création — seule l'application de la redirection est coupée ici).
        if (! config('edu.force_password_change')) {
            return $next($request);
        }

        if (auth()->check() && auth()->user()->password_must_change) {
            // Permettre l'accès à la route de modification du mot de passe
            if ($request->is('profile') || $request->is('profile/*')) {
                return $next($request);
            }

           // Permettre la déconnexion
            if ($request->is('logout')) {
                return $next($request);
            }

            return redirect()->route('profile.show')->with('warning', 'Vous devez changer votre mot de passe avant de continuer.');
        }

        return $next($request);
    }
}
