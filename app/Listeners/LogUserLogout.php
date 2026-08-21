<?php

namespace App\Listeners;

use App\Models\LoginLog;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\AsEventListener;

/**
 * Renseigne logout_at sur l'entrée du Journal des connexions correspondant à
 * cette session, pour permettre le calcul de la durée (voir
 * LoginLog::getDurationInSecondsAttribute()).
 *
 * En cas de sessions concurrentes (plusieurs appareils), on ne peut pas
 * distinguer laquelle des entrées ouvertes correspond à CETTE déconnexion sans
 * corréler par session ID — hors du périmètre de login_logs actuellement. On
 * clôture donc la plus récente entrée encore ouverte pour cet utilisateur :
 * une approximation raisonnable pour un tableau de bord de sécurité, pas une
 * garantie d'exactitude par session dans le cas multi-appareils.
 */
#[AsEventListener]
class LogUserLogout
{
    public function handle(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        LoginLog::where('user_id', $event->user->id)
            ->where('status', 'success')
            ->whereNull('logout_at')
            ->orderByDesc('login_at')
            ->first()
            ?->update(['logout_at' => now()]);
    }
}
