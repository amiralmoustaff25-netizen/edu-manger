<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Miroir de EnsurePasswordChanged (même emplacement dans le pipeline, même
 * mécanique de "porte" mi-session) : bloque l'accès à toute page tant qu'un
 * utilisateur ayant activé la double authentification n'a pas encore validé
 * son code pour la session en cours.
 */
class RequireTwoFactorVerification
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || ! auth()->user()->two_factor_confirmed_at) {
            return $next($request);
        }

        if ($request->session()->get('2fa_verified_user_id') === auth()->id()) {
            return $next($request);
        }

        if ($request->is('two-factor-challenge') || $request->is('two-factor-challenge/*') || $request->is('logout')) {
            return $next($request);
        }

        $request->session()->put('url.intended', $request->fullUrl());

        return redirect()->route('two-factor.challenge');
    }
}
