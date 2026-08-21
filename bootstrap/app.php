<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\RequireTwoFactorVerification;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'password.changed' => EnsurePasswordChanged::class,
            'two-factor' => RequireTwoFactorVerification::class,
        ]);

        $middleware->append(AddSecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Une session expirée (ex. déconnexion après inactivité, token CSRF
        // désormais périmé) ne doit pas afficher la page d'erreur 419 brute :
        // l'utilisateur doit simplement retomber sur la page de connexion.
        $exceptions->render(function (TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Session expirée, veuillez vous reconnecter.'], 419);
            }

            return redirect()->route('login')->with('status', 'Votre session a expiré. Veuillez vous reconnecter.');
        });
    })->create();
