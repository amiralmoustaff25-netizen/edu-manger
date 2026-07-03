<?php

namespace App\Providers;

use App\Models\LoginLog;
use App\Models\ParentModel;
use App\Models\Payment;
use App\Policies\LoginLogPolicy;
use App\Policies\ParentModelPolicy;
use App\Policies\PaymentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
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

        Gate::define('validatePartial', function ($user) {
            return $user->hasRole('manager-comptable');
        });
    }
}