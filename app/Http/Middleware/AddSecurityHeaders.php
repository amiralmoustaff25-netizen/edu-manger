<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Strict-Transport-Security : activé uniquement en HTTPS pour ne pas forcer
        // un comportement irréversible en environnement local HTTP.
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Content-Security-Policy : Alpine.js inline, Tailwind et les ressources
        // distantes utilisées par l'application (fonts Bunny, avatars). Le serveur
        // Vite (npm run dev) n'est autorisé qu'en environnement local — auparavant
        // le commentaire prétendait le permettre mais aucune directive ne l'incluait
        // réellement : script-src/style-src/connect-src bloquaient silencieusement
        // tout le JS/CSS dès que public/hot pointait vers localhost:5173, y compris
        // via composer run dev (notre propre workflow de développement).
        $scriptSrc = "'self' 'unsafe-inline' 'unsafe-eval'";
        $styleSrc = "'self' 'unsafe-inline' https://fonts.bunny.net";
        $connectSrc = "'self'";

        if (app()->environment('local')) {
            // Port en wildcard (":*") : si 5173 est déjà pris (processus Vite d'une
            // session précédente encore vivant, autre projet...), Vite bascule sur le
            // port suivant disponible (constaté : 5174) et public/hot pointe dessus —
            // un port figé en dur aurait cassé le rechargement à chaud à chaque fois
            // que ça arrive. Pas d'IPv6 ([::1]) : rejeté comme source invalide par les
            // navigateurs dans un en-tête CSP (voir vite.config.js, qui force Vite sur
            // 127.0.0.1 pour que public/hot n'y pointe jamais).
            $viteDevOrigins = 'http://localhost:* http://127.0.0.1:*';
            $viteDevSockets = 'ws://localhost:* ws://127.0.0.1:*';

            $scriptSrc .= ' '.$viteDevOrigins;
            $styleSrc .= ' '.$viteDevOrigins;
            $connectSrc .= ' '.$viteDevOrigins.' '.$viteDevSockets;
        }

        $csp = "default-src 'self'; "
            ."script-src {$scriptSrc}; "
            ."style-src {$styleSrc}; "
            ."font-src 'self' https://fonts.bunny.net; "
            ."img-src 'self' data: https://ui-avatars.com; "
            ."connect-src {$connectSrc}; "
            ."frame-ancestors 'self'; "
            ."base-uri 'self'; "
            ."form-action 'self';";

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
