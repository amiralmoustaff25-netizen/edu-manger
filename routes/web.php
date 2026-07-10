<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\LoginLogController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\SchoolYearController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherClassController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeacherDashboardController;
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

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile/show', [ProfileController::class, 'show'])->name('profile.show');

    Route::middleware(['role:manager-comptable|comptable'])->group(function () {
        Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
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
        Route::resource('absences', AttendanceController::class)->only(['index', 'store']);
    });

    Route::middleware(['role:super-admin|admin'])->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::patch('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::patch('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');
        Route::patch('/students/{student}/transfer', [StudentController::class, 'transfer'])->name('students.transfer');
        Route::patch('/students/{student}/status', [StudentController::class, 'updateStatus'])->name('students.status');

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
