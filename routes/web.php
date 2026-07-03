<?php

use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\LoginLogController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SchoolYearController;
use App\Models\Classroom;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
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
            'parents' => \App\Models\ParentModel::count(),
            'active_parents' => \App\Models\ParentModel::where('statut', 'actif')->count(),
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

    Route::middleware(['role:eleve'])->group(function () {
        Route::get('/mon-espace', function () {
            $user = auth()->user();

            $registration = \App\Models\Registration::with(['classroom.teacher'])
                ->where('user_id', $user->id)
                ->latest()
                ->first();

            return view('students.dashboard', compact('user', 'registration'));
        })->name('student.dashboard');
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
    });
});

require __DIR__.'/auth.php';