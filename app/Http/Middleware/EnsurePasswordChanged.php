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
