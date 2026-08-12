<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Models\ChapterCompletion;
use App\Models\Classroom;
use App\Models\ClassroomFee;
use App\Models\FeeType;
use App\Models\Invoice;
use App\Models\LoginLog;
use App\Models\Note;
use App\Models\ParentModel;
use App\Models\Payment;
use App\Models\ProgramAnnual;
use App\Models\SchoolYear;
use App\Models\User;
use App\Policies\AttendancePolicy;
use App\Policies\ChapterCompletionPolicy;
use App\Policies\ClassroomFeePolicy;
use App\Policies\ClassroomPolicy;
use App\Policies\FeeTypePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\LoginLogPolicy;
use App\Policies\NotePolicy;
use App\Policies\ParentPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProgramAnnualPolicy;
use App\Policies\SchoolYearPolicy;
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
        ParentModel::class => ParentPolicy::class,
        ProgramAnnual::class => ProgramAnnualPolicy::class,
        ChapterCompletion::class => ChapterCompletionPolicy::class,
        \App\Models\Teacher::class => \App\Policies\TeacherPolicy::class,
        Classroom::class => ClassroomPolicy::class,
        Attendance::class => AttendancePolicy::class,
        Note::class => NotePolicy::class,
        ClassroomFee::class => ClassroomFeePolicy::class,
        FeeType::class => FeeTypePolicy::class,
        Invoice::class => InvoicePolicy::class,
        SchoolYear::class => SchoolYearPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // ATTENTION : ce hook fait court-circuiter toutes les Policies et
        // permissions pour le rôle super-admin. C'est volontaire (passe-droit
        // global) mais implique que tout garde-fou métier critique (ex. supprimer
        // le dernier super-admin, muter un rôle sensible, supprimer une année
        // scolaire clôturée) DOIT être implémenté explicitement au niveau des
        // contrôleurs ou services appelants. Cette règle est documentée ; ne pas
        // la supprimer sans répercuter la logique dans chaque Policy concernée.
        Gate::before(function ($user) {
            return $user->hasRole('super-admin') ? true : null;
        });

        Gate::define('validatePartial', function ($user) {
            return $user->can('valider-paiement-partiel');
        });

        Gate::define('upload-photo-eleve', function ($user) {
            return $user->can('upload-photo-eleve');
        });

        Gate::define('remove-photo-eleve', function ($user) {
            return $user->can('upload-photo-eleve');
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
