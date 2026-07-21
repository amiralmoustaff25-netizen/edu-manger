<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\Api\StudentController as ApiStudentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BulletinController;
use App\Http\Controllers\CahierTexteController;
use App\Http\Controllers\CahierTexteDashboardController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\ClassroomFeeController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FeeTypeController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LoginLogController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\SchoolConfigurationController;
use App\Http\Controllers\SchoolYearController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherClassController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\ProgramAnnualController;
use App\Http\Controllers\UserController;
use App\Models\Classroom;
use App\Models\ParentModel;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Routes pour tester les exports (PDF/Excel)
Route::get('/export/pdf-hello-world', [ExportController::class, 'pdfHelloWorld'])->name('export.pdf.hello-world');
Route::get('/export/pdf-preview', [ExportController::class, 'pdfPreview'])->name('export.pdf.preview');
Route::get('/export/excel-hello-world', [ExportController::class, 'excelHelloWorld'])->name('export.excel.hello-world');

Route::middleware(['auth', 'verified', 'password.changed', 'school.configured'])->group(function () {
    Route::get('/dashboard', function () {
        // REDIRECTION ÉLÈVE vers /mon-espace
        if (auth()->user()->hasRole('eleve')) {
            return redirect()->route('student.dashboard');
        }

        // REDIRECTION PROFESSEUR vers /professeur/dashboard
        if (auth()->user()->hasRole('professeur')) {
            return redirect()->route('professeur.dashboard');
        }

        $activeYear = SchoolYear::where('is_active', true)->first();

        $registrations = Registration::with(['user', 'classroom', 'schoolYear'])
            ->latest()
            ->take(8)
            ->get();

        $recentPayments = Payment::with(['registration.user', 'registration.classroom'])
            ->latest()
            ->take(5)
            ->get();

        $stats = [
            'students' => User::where('role', 'eleve')->count(),
            'classrooms' => Classroom::count(),
            'parents' => ParentModel::count(),
            'active_parents' => ParentModel::where('statut', 'actif')->count(),
            'paid_this_month' => Payment::where('status', 'complet')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'partial_payments' => Payment::where('status', 'partiel')->count(),
            'monthly_revenue' => Payment::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
            'remaining_balance' => Payment::where('status', 'partiel')->sum('remaining_balance'),
        ];

        $alerts = [
            'partial_payments' => Payment::where('status', 'partiel')->count(),
            'students_without_class' => Registration::whereNull('classroom_id')->count(),
            'classrooms_without_teacher' => Classroom::whereNull('teacher_id')->count(),
            'missing_active_year' => $activeYear === null,
        ];

        return view('dashboard', compact('registrations', 'recentPayments', 'stats', 'alerts', 'activeYear'));
    })->name('dashboard');

    Route::resource('classrooms', ClassroomController::class)->except(['show']);
    Route::get('/classrooms/{classroom}/teachers', [ClassroomController::class, 'teachers'])->name('classrooms.teachers');
    Route::post('/classrooms/{classroom}/teachers', [ClassroomController::class, 'attachTeacher'])->name('classrooms.attach-teacher');
    Route::delete('/classrooms/{classroom}/teachers/{teacher}', [ClassroomController::class, 'detachTeacher'])->name('classrooms.detach-teacher');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile/show', [ProfileController::class, 'show'])->name('profile.show');

    // Routes API pour les paiements
    Route::get('/api/students/by-matricule/{matricule}', [ApiStudentController::class, 'getByMatricule']);
    Route::get('/api/students/{registrationId}/fees', [ApiStudentController::class, 'getStudentFees']);

    Route::middleware(['role:super-admin|admin|manager-comptable'])->group(function () {
        // Routes paramètres
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/school', [SchoolConfigurationController::class, 'index'])->name('school.index');
            Route::post('/school/update-info', [SchoolConfigurationController::class, 'updateSchoolInfo'])->name('school.update-info');
            Route::post('/school/update-bank', [SchoolConfigurationController::class, 'updateBankInfo'])->name('school.update-bank');
            Route::post('/school/update-accounting', [SchoolConfigurationController::class, 'updateAccountingSettings'])->name('school.update-accounting');
            Route::post('/school/complete', [SchoolConfigurationController::class, 'completeConfiguration'])->name('school.complete');
        });
    });

    // Routes pour les bulletins scolaires
    Route::get('/bulletins', [BulletinController::class, 'index'])->name('bulletins.index');
    Route::get('/bulletins/{student}/{period}', [BulletinController::class, 'show'])->name('bulletins.show');
    Route::get('/bulletins/{student}/{period}/pdf', [BulletinController::class, 'generatePdf'])->name('bulletins.pdf');
    Route::get('/bulletins/class/{classroom}/{period}/pdf', [BulletinController::class, 'generateClassPdf'])->name('bulletins.class-pdf');

    Route::middleware(['role:manager-comptable|comptable'])->group(function () {
        // Routes comptabilité
        Route::get('/accounting', [AccountingController::class, 'index'])->name('accounting.dashboard');
        Route::get('/accounting/reports', [AccountingController::class, 'reports'])->name('accounting.reports');
        Route::get('/accounting/advanced-reports', [AccountingController::class, 'advancedReports'])->name('accounting.advanced-reports');
        Route::get('/accounting/export-advanced-reports', [AccountingController::class, 'exportAdvancedReports'])->name('accounting.export-advanced-reports');
        Route::get('/accounting/alerts', [AccountingController::class, 'alerts'])->name('accounting.alerts');
        Route::get('/accounting/cash-flow', [AccountingController::class, 'cashFlow'])->name('accounting.cash-flow');
        
        // Paiements
        Route::resource('payments', PaymentController::class)->only(['index', 'create', 'show', 'edit', 'update', 'destroy']);
        Route::get('/payments/{payment}/receipt', [PaymentController::class, 'exportReceipt'])->name('payments.receipt');
        Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
        
        // Factures
        Route::resource('invoices', InvoiceController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'exportPdf'])->name('invoices.pdf');
        
        // Types de frais
        Route::resource('fee-types', FeeTypeController::class)->only(['index', 'create', 'edit', 'update', 'destroy']);
        
        // Frais par classe
        Route::resource('classroom-fees', ClassroomFeeController::class)->only(['index', 'create', 'edit', 'update', 'destroy']);
    });

    Route::middleware(['auth'])->group(function () {
        Route::resource('programs', ProgramAnnualController::class);
        Route::post('programs/{program}/submit', [ProgramAnnualController::class, 'submit'])->name('programs.submit');
        Route::post('programs/{program}/validate-surveillant', [ProgramAnnualController::class, 'validateSurveillant'])->name('programs.validate-surveillant');
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
        Route::get('/mon-espace', function () {
            $user = auth()->user();
            $user->load('latestRegistration.classroom.schoolYear');

            $registration = $user->latestRegistration;
            $notes = $user->notes()->with('matiere')->latest()->take(5)->get();
            $moyenne = $user->notes()->avg('valeur') ?? 0;
            $payments = $registration ? $registration->payments()->latest()->take(5)->get() : collect();
            $totalPaid = $registration ? $registration->payments()->sum('amount') : 0;
            $totalDue = $registration ? ($registration->monthly_fee * 9) : 0;
            $remaining = $totalDue - $totalPaid;

            return view('students.dashboard', compact(
                'user', 'registration', 'notes', 'moyenne',
                'payments', 'totalPaid', 'remaining'
            ));
        })->name('student.dashboard');
    });

    // Routes pour les professeurs
    Route::middleware(['role:professeur'])->prefix('professeur')->name('professeur.')->group(function () {
        Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');
        Route::resource('classes', TeacherClassController::class)->only(['index', 'show']);
        Route::resource('notes', GradeController::class)->only(['index', 'store']);
        Route::resource('attendances', AttendanceController::class)->only(['index', 'store']);
    });

    Route::middleware(['role:super-admin|admin'])->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::patch('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::patch('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

        Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
        Route::post('/students', [StudentController::class, 'store'])->name('students.store');
        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');
        Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
        Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
        Route::patch('/students/{student}/transfer', [StudentController::class, 'transfer'])->name('students.transfer');
        Route::patch('/students/{student}/status', [StudentController::class, 'updateStatus'])->name('students.status');
        Route::delete('/students/{student}/photo', [StudentController::class, 'removePhoto'])->name('students.remove-photo');

        Route::get('/registrations/create', [RegistrationController::class, 'create'])->name('registrations.create');
        Route::post('/registrations', [RegistrationController::class, 'store'])->name('registrations.store');

        // Routes pour la gestion des parents
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
        Route::get('/teachers-export-pdf', [TeacherController::class, 'exportPdf'])->name('teachers.export-pdf');
        Route::get('/teachers-export-csv', [TeacherController::class, 'exportCsv'])->name('teachers.export-csv');
    });

});

require __DIR__.'/auth.php';
