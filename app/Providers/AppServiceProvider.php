<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // SEC-07 : politique de complexité par défaut pour tout mot de passe
        // choisi volontairement par un utilisateur (changement de mot de passe
        // après le mot de passe temporaire imposé, "mot de passe oublié"...).
        // Restreinte à l'environnement de production pour ne pas casser les
        // fixtures/tests existants qui utilisent des mots de passe simples.
        Password::defaults(function () {
            if (! $this->app->environment('production')) {
                return Password::min(8);
            }

            return Password::min(10)->mixedCase()->numbers()->uncompromised();
        });

        // SEC-06 : garde-fou explicite contre un déploiement où APP_DEBUG=true
        // en production exposerait stack traces, requêtes SQL et variables
        // d'environnement. On ne fait pas planter l'application (cela
        // aggraverait l'incident), mais on journalise une alerte critique
        // dès le démarrage pour qu'elle soit remontée par la supervision.
        if ($this->app->environment('production') && config('app.debug')) {
            Log::critical('APP_DEBUG=true en environnement de production : fuite d\'informations techniques possible. Corriger immédiatement APP_DEBUG=false.');
        }
    }
}
