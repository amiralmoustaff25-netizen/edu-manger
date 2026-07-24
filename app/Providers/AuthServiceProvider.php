<?php

namespace App\Providers;

use App\Models\ChapterCompletion;
use App\Models\LoginLog;
use App\Models\ParentModel;
use App\Models\Payment;
use App\Models\ProgramAnnual;
use App\Models\User;
use App\Policies\ChapterCompletionPolicy;
use App\Policies\LoginLogPolicy;
use App\Policies\ParentModelPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProgramAnnualPolicy;
use App\Policies\StudentPhotoPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        Payment::class => PaymentPolicy::class,
        LoginLog::class => LoginLogPolicy::class,
        ParentModel::class => ParentModelPolicy::class,
        ProgramAnnual::class => ProgramAnnualPolicy::class,
        ChapterCompletion::class => ChapterCompletionPolicy::class,
        \App\Models\Teacher::class => \App\Policies\TeacherPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function ($user) {
            return $user->hasRole('super-admin') ? true : null;
        });

        Gate::define('validatePartial', function ($user) {
            return $user->hasAnyRole(['super-admin', 'manager-comptable']);
        });

        Gate::define('upload-photo-eleve', function ($user) {
            return $user->hasRole(['super-admin', 'admin']);
        });

        Gate::define('remove-photo-eleve', function ($user) {
            return $user->hasRole(['super-admin', 'admin']);
        });

        RateLimiter::for('login', function (object $request) {
            $email = (string) $request->input('email');

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('api', function (object $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
