<?php

namespace App\Http\Middleware;

use App\Models\SchoolConfiguration;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSchoolConfiguration
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier si l'utilisateur est admin ou super-admin
        if (!auth()->user()->hasRole('super-admin|admin')) {
            return $next($request);
        }

        // Vérifier si la configuration est requise
        if (!SchoolConfiguration::isConfigured()) {
            // Rediriger vers la page de configuration sauf si on y est déjà
            if (!$request->routeIs('settings.school.*')) {
                return redirect()->route('settings.school.index')
                    ->with('warning', 'Veuillez configurer votre école avant de continuer.');
            }
        }

        return $next($request);
    }
}
