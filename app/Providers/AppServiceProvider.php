<?php

namespace App\Providers;

use App\Models\LoginLog;
use App\Models\Payment;
use App\Policies\LoginLogPolicy;
use App\Policies\PaymentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(LoginLog::class, LoginLogPolicy::class);

        Gate::define('validatePartial', function ($user) {
            return $user->hasRole('manager-comptable');
        });
    }
}
