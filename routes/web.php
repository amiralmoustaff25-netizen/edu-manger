<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\Api\StudentController as ApiStudentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BulletinController;
use App\Http\Controllers\CahierTexteController;
use App\Http\Controllers\CahierTexteDashboardController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\ClassroomFeeController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\FeeTypeController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LoginLogController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\PedagogicalConfigurationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\RoleAssignmentController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\SchoolYearController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentDocumentController;
use App\Http\Controllers\TeacherClassController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\TeachingSessionController;
use App\Http\Controllers\ProgramAnnualController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserNotificationController;
use App\Models\Classroom;
use App\Models\Invoice;
use App\Models\Matiere;
use App\Models\ParentModel;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware(['auth', 'verified', 'password.changed'])->group(function () {
    Route::get('/dashboard', function () {
        // REDIRECTION ÉLÈVE vers /mon-espace
        if (auth()->user()->hasRole('eleve')) {
            return redirect()->route('student.dashboard');
        }

        // REDIRECTION PROFESSEUR vers /professeur/dashboard
        if (auth()->user()->hasRole('professeur')) {
            return redirect()->route('professeur.dashboard');
        }

        // Parent dashboard personnalisé
        if (auth()->user()->hasRole('parent')) {
            $parent = auth()->user()->parentProfile()
                ->with(['students' => function ($query) {
                    $query->with(['latestRegistration.classroom.schoolYear'])
                        ->withCount(['notes', 'attendances']);
                }])
                ->firstOrFail();

            return view('parents.dashboard', compact('parent'));
        }

        // Ce tableau de bord (chiffres financiers, paiements récents, inscriptions)
        // est réservé au personnel de direction/comptabilité. Sans ce contrôle,
        // n'importe quel compte authentifié n'ayant aucun des rôles ci-dessus
        // (eleve/professeur/parent traités plus haut) tombait dans cette branche
        // et voyait les données financières de l'établissement.
        abort_unless(
            auth()->user()->hasAnyRole(['super-admin', 'admin', 'manager-comptable', 'comptable', 'surveillant']),
            403
        );

        $activeYear = SchoolYear::where('is_active', true)->first();

        $registrations = Registration::with(['user', 'classroom', 'schoolYear'])
            ->latest()
            ->take(8)
            ->get();

        $recentPayments = Payment::with(['registration.user', 'registration.classroom'])
            ->latest()
            ->take(5)
            ->get();

        // Solde restant réel : recalculé via FeeService (frais réellement dus) pour les
        // inscriptions actives de l'année en cours, jamais via le module Invoice (peu
        // utilisé, déconnecté du suivi réel des paiements) ni en sommant remaining_balance.
        $feeService = app(\App\Services\FeeService::class);
        $activeRegistrationsForBalance = $activeYear
            ? Registration::where('school_year_id', $activeYear->id)->where('status', 'active')->get()
            : collect();
        $remainingBalance = $activeRegistrationsForBalance->sum(
            fn (Registration $registration) => $feeService->getFinancialSituation($registration)['remaining']
        );

        $stats = [
            'students' => User::role('eleve')->count(),
            'classrooms' => Classroom::count(),
            'parents' => ParentModel::count(),
            'active_parents' => ParentModel::where('statut', 'actif')->count(),
            'paid_this_month' => Payment::where('status', 'complet')->notCancelled()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'partial_payments' => Payment::where('status', 'partiel')->notCancelled()->count(),
            // Inclut les paiements partiels : leur montant "amount" est de l'argent
            // réellement encaissé, pas une projection — l'exclure sous-évaluait le
            // revenu mensuel réel dès qu'un paiement partiel avait eu lieu.
            'monthly_revenue' => Payment::whereIn('status', ['complet', 'partiel'])->notCancelled()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
            'remaining_balance' => $remainingBalance,
        ];

        $monthlyRevenue = collect(range(5, 0))->map(function ($monthsAgo) {
            $date = now()->subMonths($monthsAgo);

            return [
                'label' => $date->translatedFormat('M Y'),
                'amount' => Payment::whereIn('status', ['complet', 'partiel'])->notCancelled()
                    ->whereMonth('payment_date', $date->month)
                    ->whereYear('payment_date', $date->year)
                    ->sum('amount'),
            ];
        })->values();

        // Alertes synchronisées avec les données réelles (Payment/Registration/Classroom),
        // plus de dépendance au module Invoice qui n'est pas utilisé en pratique.
        $alerts = [
            'partial_payments' => Payment::where('status', 'partiel')
                ->notCancelled()
                ->where('remaining_balance', '>', 0)
                ->count(),
            'students_without_class' => Registration::where('status', 'active')->whereNull('classroom_id')->count(),
            'classrooms_without_teacher' => Classroom::whereNull('teacher_id')->count(),
            'missing_active_year' => $activeYear === null,
        ];

        return view('dashboard', compact('registrations', 'recentPayments', 'stats', 'alerts', 'activeYear', 'monthlyRevenue'));
    })->name('dashboard');

    Route::resource('classrooms', ClassroomController::class)->except(['show']);
    Route::get('/classrooms/{classroom}/teachers', [ClassroomController::class, 'teachers'])->name('classrooms.teachers');
    Route::post('/classrooms/{classroom}/teachers', [ClassroomController::class, 'attachTeacher'])->name('classrooms.attach-teacher');
    Route::delete('/classrooms/{classroom}/teachers/{teacher}', [ClassroomController::class, 'detachTeacher'])->name('classrooms.detach-teacher');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile/show', [ProfileController::class, 'show'])->name('profile.show');

    // Routes pour les bulletins scolaires
    Route::get('/bulletins', [BulletinController::class, 'index'])->name('bulletins.index');
    Route::get('/bulletins/{student}/{period}', [BulletinController::class, 'show'])->name('bulletins.show');
    Route::get('/bulletins/{student}/{period}/pdf', [BulletinController::class, 'generatePdf'])->name('bulletins.pdf');
    Route::get('/bulletins/class/{classroom}/{period}/pdf', [BulletinController::class, 'generateClassPdf'])->name('bulletins.class-pdf');

    // Paiements : accessibles en lecture/saisie à l'admin (voir-paiements, enregistrer-paiement,
    // modifier-paiement ne sont pas exclus de son rôle — cf. RoleAndPermissionSeeder), mais pas
    // le reste de la comptabilité (factures, rapports, trésorerie). D'où un groupe de rôle plus
    // large que le reste du bloc finance ci-dessous ; la Policy (PaymentPolicy) reste le contrôle
    // fin par action (un admin n'a par ex. pas la permission 'supprimer-paiement').
    Route::middleware(['role:super-admin|manager-comptable|comptable|admin'])->group(function () {
        // Validation des paiements (permission explicite) — déclaré avant la ressource pour éviter le conflit avec /payments/{payment}
        Route::middleware(['permission:valider-paiement-partiel'])->group(function () {
            Route::get('/payments/validation', [PaymentController::class, 'validationIndex'])->name('payments.validation');
            Route::post('/payments/{payment}/validate', [PaymentController::class, 'validatePayment'])->name('payments.validate');
            Route::post('/payments/{payment}/reject', [PaymentController::class, 'rejectPayment'])->name('payments.reject');
        });

        Route::resource('payments', PaymentController::class)->only(['index', 'create', 'show', 'edit', 'update', 'destroy']);
        Route::get('/payments/{payment}/receipt', [PaymentController::class, 'exportReceipt'])->name('payments.receipt');
        Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::middleware(['permission:annuler-paiement'])->group(function () {
            Route::patch('/payments/{payment}/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');
        });
    });

    Route::middleware(['role:super-admin|manager-comptable|comptable'])->group(function () {
        // Routes API pour les paiements (recherche élève par matricule / frais dus) —
        // consommées uniquement par accounting/payments/create.blade.php. Restreintes
        // au personnel comptable : sinon tout utilisateur authentifié pouvait énumérer
        // les données personnelles et financières de n'importe quel élève.
        // throttle:api (limiteur défini dans AuthServiceProvider) : ralentit une
        // énumération automatisée de matricules même par un compte comptable autorisé.
        Route::middleware(['throttle:api'])->group(function () {
            Route::get('/api/students/by-matricule/{matricule}', [ApiStudentController::class, 'getByMatricule']);
            Route::get('/api/students/{registrationId}/fees', [ApiStudentController::class, 'getStudentFees']);
        });

        // Routes comptabilité
        Route::get('/accounting', [AccountingController::class, 'index'])->name('accounting.dashboard');
        Route::get('/accounting/reports', [AccountingController::class, 'reports'])->name('accounting.reports');
        Route::get('/accounting/advanced-reports', [AccountingController::class, 'advancedReports'])->name('accounting.advanced-reports');
        Route::get('/accounting/export-advanced-reports', [AccountingController::class, 'exportAdvancedReports'])->name('accounting.export-advanced-reports');
        Route::get('/accounting/alerts', [AccountingController::class, 'alerts'])->name('accounting.alerts');
        Route::get('/accounting/cash-flow', [AccountingController::class, 'cashFlow'])->name('accounting.cash-flow');

        // Rappels (manager-comptable uniquement)
        Route::middleware(['role:super-admin|manager-comptable'])->group(function () {
            Route::resource('reminders', ReminderController::class)->only(['index', 'create', 'store', 'destroy']);
            Route::post('/reminders/generate-overdue', [ReminderController::class, 'generateOverdue'])->name('reminders.generate-overdue');
            Route::post('/reminders/generate-upcoming', [ReminderController::class, 'generateUpcoming'])->name('reminders.generate-upcoming');
        });
        
        // Factures
        Route::resource('invoices', InvoiceController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'exportPdf'])->name('invoices.pdf');
        
        // Types de frais
        Route::resource('fee-types', FeeTypeController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

        // Frais par classe
        Route::resource('classroom-fees', ClassroomFeeController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::get('classroom-fees/{classroomFee}/history', [ClassroomFeeController::class, 'history'])->name('classroom-fees.history');
    });

    Route::middleware(['auth'])->group(function () {
        Route::resource('programs', ProgramAnnualController::class);
        Route::post('programs/{program}/submit', [ProgramAnnualController::class, 'submit'])->name('programs.submit');
        Route::post('programs/{program}/validate-directeur', [ProgramAnnualController::class, 'validateDirecteur'])->name('programs.validate-directeur');
        Route::post('programs/{program}/reject', [ProgramAnnualController::class, 'reject'])->name('programs.reject');
        Route::post('programs/import', [ProgramAnnualController::class, 'importExcel'])->name('programs.import');
        Route::get('programs/template', [ProgramAnnualController::class, 'downloadTemplate'])->name('programs.template');

        Route::get('cahier-textes', [CahierTexteController::class, 'index'])->name('cahier-textes.index');
        Route::get('cahier-textes/select', [CahierTexteController::class, 'select'])->name('cahier-textes.select');
        Route::post('cahier-textes/toggle', [CahierTexteController::class, 'toggle'])->name('cahier-textes.toggle');
        Route::post('cahier-textes/bulk', [CahierTexteController::class, 'bulkToggle'])->name('cahier-textes.bulk');
        Route::post('cahier-textes/mark-lesson', [CahierTexteController::class, 'markLessonDone'])->name('cahier-textes.mark-lesson');
        Route::patch('cahier-textes/{completion}/remark', [CahierTexteController::class, 'updateRemark'])->name('cahier-textes.remark');

        Route::get('cahier-textes/dashboard', [CahierTexteDashboardController::class, 'index'])->name('cahier-textes.dashboard.index');
        Route::get('cahier-textes/dashboard/{program}/progress', [CahierTexteDashboardController::class, 'progress'])->name('cahier-textes.dashboard.progress');
        Route::get('cahier-textes/dashboard/{program}/timeline', [CahierTexteDashboardController::class, 'timeline'])->name('cahier-textes.dashboard.timeline');
    });

    // ✅ CORRIGÉ : plus de doublon
    Route::middleware(['role:eleve'])->group(function () {
        Route::get('/mon-espace/notes', function () {
            $user = auth()->user();
            $notes = $user->notes()->with('matiere')->latest()->get();

            return view('students.notes', compact('user', 'notes'));
        })->name('student.notes');

        Route::get('/mon-espace/emploi-du-temps', function () {
            $user = auth()->user();
            $registration = $user->latestRegistration;
            $registration?->load(['classroom.teachers.user', 'classroom.schoolYear', 'schoolYear']);

            $matieres = Matiere::all()->keyBy('id');

            return view('students.timetable', compact('user', 'registration', 'matieres'));
        })->name('student.timetable');

        Route::get('/mon-espace/bulletins', function () {
            $user = auth()->user();

            return view('students.bulletins', compact('user'));
        })->name('student.bulletins');

        Route::get('/mon-espace', function () {
            $user = auth()->user();
            $user->load([
                'latestRegistration.classroom.schoolYear',
                'parents',
                'attendances.classroom',
                'sanctions',
            ]);

            $registration = $user->latestRegistration;
            $notes = $user->notes()->with('matiere')->latest()->take(5)->get();

            // Moyenne pondérée par coefficient, scopée aux matières de la classe, pour la
            // période la plus récente où l'élève a des notes — même calcul que le bulletin
            // (GradeCalculationService), au lieu d'une simple moyenne arithmétique de toutes
            // les notes de toutes les périodes/matières confondues sans pondération.
            $moyenne = 0.0;
            if ($registration?->classroom) {
                $latestPeriod = $user->notes()->latest()->value('periode');
                if ($latestPeriod) {
                    $moyenne = app(\App\Services\GradeCalculationService::class)
                        ->getBulletinData($user, $latestPeriod)['general_average'];
                }
            }

            $payments = $registration ? $registration->payments()->latest()->take(5)->get() : collect();

            // Solde réel recalculé via FeeService (frais d'inscription/mensualité/options/
            // dérogations réellement dus), jamais via monthly_fee * nombre_de_mois — ce
            // calcul forfaitaire, déjà identifié et évité ailleurs dans le module comptable
            // (cf. AccountingController), ignorait les frais d'inscription, les options
            // (cantine/transport/internat) et les dérogations tarifaires. Le total payé
            // excluait aussi les paiements annulés/rejetés du calcul réel.
            $situation = $registration ? app(\App\Services\FeeService::class)->getFinancialSituation($registration) : null;
            $totalPaid = $situation['paid'] ?? 0;
            $remaining = $situation['remaining'] ?? 0;

            return view('students.dashboard', compact(
                'user', 'registration', 'notes', 'moyenne',
                'payments', 'totalPaid', 'remaining'
            ));
        })->name('student.dashboard');
    });

    // Routes pour les professeurs
    Route::middleware(['role:professeur'])->prefix('professeur')->name('professeur.')->group(function () {
        Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');
        Route::resource('classes', TeacherClassController::class)
            ->parameters(['classes' => 'classroom'])
            ->only(['index', 'show']);
        Route::resource('notes', GradeController::class)->only(['index', 'store']);
        Route::get('notes/eleve', [GradeController::class, 'searchStudent'])->name('notes.eleve');
        Route::post('notes/eleve', [GradeController::class, 'storeForStudent'])->name('notes.eleve.store');
        Route::get('/pointage-cours', [TeachingSessionController::class, 'index'])->name('teaching-sessions.index');
        Route::post('/pointage-cours', [TeachingSessionController::class, 'store'])->name('teaching-sessions.store');
        Route::get('/attendances/history', [AttendanceController::class, 'history'])->name('attendances.history');
        Route::resource('attendances', AttendanceController::class)->only(['index', 'store']);
    });

    Route::middleware(['permission:voir-detail-eleve'])->group(function () {
        Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');
        Route::post('/registrations/{registration}/discounts', [DiscountController::class, 'store'])->name('discounts.store');
        Route::delete('/discounts/{discount}', [DiscountController::class, 'destroy'])->name('discounts.destroy');
        Route::get('/students/{student}/documents/{document}/download', [StudentDocumentController::class, 'download'])->name('students.documents.download');
    });

    Route::middleware(['permission:gerer-documents-eleve'])->group(function () {
        Route::post('/students/{student}/documents', [StudentDocumentController::class, 'store'])->name('students.documents.store');
        Route::delete('/students/{student}/documents/{document}', [StudentDocumentController::class, 'destroy'])->name('students.documents.destroy');
    });

    // Routes spécifiques pour les parents (portail famille)
    // Déclarée AVANT Route::resource('parents', ParentController::class) plus bas :
    // ce dernier définit /parents/{parent} (show), qui interceptrait sinon les URL
    // à un seul segment de ce portail (/parents/dashboard, /parents/children, ...)
    // puisque Laravel matche les routes dans leur ordre de déclaration.
    Route::middleware(['role:parent'])->prefix('parents')->name('parents.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\ParentPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/children', [\App\Http\Controllers\ParentPortalController::class, 'childrenIndex'])->name('children.index');
        Route::get('/children/profile', [\App\Http\Controllers\ParentPortalController::class, 'childProfile'])->name('children.profile');
        Route::get('/children/notes', [\App\Http\Controllers\ParentPortalController::class, 'childNotes'])->name('children.notes');
        Route::get('/children/bulletins', [\App\Http\Controllers\ParentPortalController::class, 'childBulletins'])->name('children.bulletins');
        Route::get('/children/attendances', [\App\Http\Controllers\ParentPortalController::class, 'childAttendances'])->name('children.attendances');
        Route::get('/children/discipline', [\App\Http\Controllers\ParentPortalController::class, 'childDiscipline'])->name('children.discipline');
        Route::get('/children/timetable', [\App\Http\Controllers\ParentPortalController::class, 'childTimetable'])->name('children.timetable');
        Route::get('/children/payments', [\App\Http\Controllers\ParentPortalController::class, 'childPayments'])->name('children.payments');
        Route::get('/messaging', [\App\Http\Controllers\ParentPortalController::class, 'messaging'])->name('messaging');
        Route::get('/calendar', [\App\Http\Controllers\ParentPortalController::class, 'calendar'])->name('calendar');
    });

    Route::middleware(['role:super-admin|admin'])->group(function () {
        Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');
        Route::get('/attendances', [AttendanceController::class, 'overview'])->name('attendances.overview');
        Route::post('/notes/validate', [GradeController::class, 'validateNotes'])->name('notes.validate');
        Route::post('/notes/reopen', [GradeController::class, 'reopenNotes'])->name('notes.reopen');
        Route::prefix('pedagogical-configuration')->name('pedagogical-configuration.')->group(function () {
            Route::get('/', [PedagogicalConfigurationController::class, 'index'])->name('index');
            Route::get('/assignments', [PedagogicalConfigurationController::class, 'assignments'])->name('assignments');
            Route::post('/assignments', [PedagogicalConfigurationController::class, 'storeAssignments'])->name('assignments.store');
            Route::patch('/assignments/{assignment}/toggle', [PedagogicalConfigurationController::class, 'toggleAssignment'])->name('assignments.toggle');
            Route::post('/periods', [PedagogicalConfigurationController::class, 'storePeriod'])->name('periods.store');
            Route::patch('/periods/{period}/toggle', [PedagogicalConfigurationController::class, 'togglePeriod'])->name('periods.toggle');
            Route::post('/evaluation-types', [PedagogicalConfigurationController::class, 'storeEvaluationType'])->name('evaluation-types.store');
            Route::post('/subject-configurations', [PedagogicalConfigurationController::class, 'storeSubjectConfiguration'])->name('subjects.store');
            Route::put('/school-years/{schoolYear}/grade-settings', [PedagogicalConfigurationController::class, 'updateSettings'])->name('settings.update');
        });
        Route::resource('users', UserController::class)->except(['show']);
        Route::patch('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::patch('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::post('/users/{id}/restore', [UserController::class, 'restore'])->name('users.restore');

        Route::get('/users/roles/assign', [RoleAssignmentController::class, 'index'])->name('users.roles.index');
        Route::patch('/users/{user}/roles', [RoleAssignmentController::class, 'update'])->name('users.roles.update');

        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
        Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
        Route::post('/students/{id}/restore', [StudentController::class, 'restore'])->name('students.restore');
        Route::patch('/students/{student}/transfer', [StudentController::class, 'transfer'])->name('students.transfer');
        Route::patch('/students/{student}/status', [StudentController::class, 'updateStatus'])->name('students.status');

        Route::get('/registrations/create', [RegistrationController::class, 'create'])->name('registrations.create');
        Route::post('/registrations', [RegistrationController::class, 'store'])->name('registrations.store');
        Route::get('/registrations/reinscription', [RegistrationController::class, 'reenrollSearch'])->name('registrations.reinscription');
        Route::post('/registrations/reinscription', [RegistrationController::class, 'storeReenrollment'])->name('registrations.reinscription.store');

        // Routes pour la gestion des parents (admin)
        // NB : le portail parent (prefix /parents, role:parent) est déclaré plus haut,
        // AVANT cette resource — sinon /parents/{parent} (show) intercepterait les URL
        // à un seul segment du portail (/parents/dashboard, /parents/children, ...).
        Route::resource('parents', ParentController::class);
        Route::patch('/parents/{parent}/archive', [ParentController::class, 'archive'])->name('parents.archive');
        Route::post('/parents/{parent}/attach-student', [ParentController::class, 'attachStudent'])->name('parents.attach-student');
        Route::delete('/parents/{parent}/detach-student/{student}', [ParentController::class, 'detachStudent'])->name('parents.detach-student');
        Route::patch('/parents/{parent}/reset-password', [ParentController::class, 'resetPassword'])->name('parents.reset-password');
        Route::post('/parents/{id}/restore', [ParentController::class, 'restore'])->name('parents.restore');

        // Gestion des années scolaires
        Route::resource('school-years', SchoolYearController::class)->only(['index', 'store', 'destroy']);
        Route::post('school-years/{schoolYear}/activate', [SchoolYearController::class, 'activate'])->name('school-years.activate');

        // Routes pour les logs de connexion
        Route::get('/login-logs', [LoginLogController::class, 'index'])->name('login-logs.index');
        Route::get('/login-logs/{loginLog}', [LoginLogController::class, 'show'])->name('login-logs.show');

        Route::resource('teachers', TeacherController::class);
        Route::post('/teachers/{id}/restore', [TeacherController::class, 'restore'])->name('teachers.restore');
        Route::get('/teachers-export-pdf', [TeacherController::class, 'exportPdf'])->name('teachers.export-pdf');
        Route::get('/teachers-export-csv', [TeacherController::class, 'exportCsv'])->name('teachers.export-csv');

        // Notifications & Communications
        Route::get('/announcements/{announcement}/preview', [AnnouncementController::class, 'preview'])->name('announcements.preview');
        Route::post('/announcements/{announcement}/publish', [AnnouncementController::class, 'publish'])->name('announcements.publish');
        Route::post('/announcements/{announcement}/archive', [AnnouncementController::class, 'archive'])->name('announcements.archive');
        Route::resource('announcements', AnnouncementController::class);
    });

    // Centre de notifications pour tous les utilisateurs authentifiés
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [UserNotificationController::class, 'index'])->name('index');
        Route::get('/unread', [UserNotificationController::class, 'unread'])->name('unread');
        Route::post('/mark-all-read', [UserNotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::post('/{notification}/mark-as-read', [UserNotificationController::class, 'markAsRead'])->name('mark-as-read');
        Route::get('/{notification}', [UserNotificationController::class, 'show'])->name('show');
    });

});

require __DIR__.'/auth.php';
