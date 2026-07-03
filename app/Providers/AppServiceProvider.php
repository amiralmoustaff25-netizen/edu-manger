<?php

namespace App\Providers;

use App\Models\LoginLog;
use App\Models\ParentModel;
use App\Models\Payment;
use App\Policies\LoginLogPolicy;
use App\Policies\ParentModelPolicy;
use App\Policies\PaymentPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Les policies de l'application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Payment::class => PaymentPolicy::class,
        LoginLog::class => LoginLogPolicy::class,
        ParentModel::class => ParentModelPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerPolicies();

        // Gate personnalisée pour la validation des paiements partiels
        Gate::define('validatePartial', function ($user) {
            return $user->hasRole('manager-comptable');
        });

        // Rate limiting sur le login (5 tentatives par minute)
        RateLimiter::for('login', function (object $request) {
            $email = (string) $request->input('email');

            return Limit::perMinute(5)->by($email . '|' . $request->ip());
        });

        // Rate limiting sur les endpoints sensibles (payments, users, parents)
        RateLimiter::for('api', function (object $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}