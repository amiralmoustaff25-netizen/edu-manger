<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\ParentModel;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;
use App\Services\FeeService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly FeeService $feeService)
    {
    }

    public function index()
    {
        $user = auth()->user();

        if ($user->hasRole('eleve')) {
            return redirect()->route('student.dashboard');
        }

        if ($user->hasRole('professeur')) {
            return redirect()->route('professeur.dashboard');
        }

        if ($user->hasRole('parent')) {
            return $this->parentDashboard($user);
        }

        abort_unless(
            $user->hasAnyRole(['super-admin', 'admin', 'manager-comptable', 'comptable', 'surveillant']),
            403
        );

        return $this->staffDashboard();
    }

    private function parentDashboard(User $user): View
    {
        $parent = $user->parentProfile()
            ->with(['students' => function ($query) {
                $query->with(['latestRegistration.classroom.schoolYear'])
                    ->withCount(['notes', 'attendances']);
            }])
            ->firstOrFail();

        return view('parents.dashboard', compact('parent'));
    }

    private function staffDashboard(): View
    {
        $activeYear = SchoolYear::where('is_active', true)->first();

        $registrations = Registration::with(['user', 'classroom', 'schoolYear'])
            ->latest()
            ->take(8)
            ->get();

        $recentPayments = Payment::with(['registration.user', 'registration.classroom'])
            ->latest()
            ->take(5)
            ->get();

        $activeRegistrationsForBalance = $activeYear
            ? Registration::where('school_year_id', $activeYear->id)->where('status', 'active')->get()
            : collect();

        $remainingBalance = $activeRegistrationsForBalance->sum(
            fn (Registration $registration) => $this->feeService->getFinancialSituation($registration)['remaining']
        );

        // Même correctif que sur le tableau de bord Comptabilité (AccountingController::
        // index/alerts/cashFlow) : un paiement rattaché à une inscription d'une année
        // scolaire déjà clôturée ne doit pas gonfler les indicateurs "courants" de ce
        // tableau de bord, même s'il tombe dans le même mois calendaire.
        $scopedToActiveYear = fn ($query) => $query->when(
            $activeYear,
            fn ($query) => $query->whereHas('registration', fn ($q) => $q->where('school_year_id', $activeYear->id))
        );

        $stats = [
            'students' => User::role('eleve')->count(),
            'classrooms' => Classroom::count(),
            'parents' => ParentModel::count(),
            'active_parents' => ParentModel::where('statut', 'actif')->count(),
            'paid_this_month' => $scopedToActiveYear(Payment::where('status', 'complet')->notCancelled())
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'partial_payments' => $scopedToActiveYear(Payment::where('status', 'partiel')->notCancelled())->count(),
            'monthly_revenue' => $scopedToActiveYear(Payment::whereIn('status', ['complet', 'partiel'])->notCancelled())
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
            'remaining_balance' => $remainingBalance,
        ];

        $monthlyRevenue = collect(range(5, 0))->map(function ($monthsAgo) use ($scopedToActiveYear) {
            $date = now()->subMonths($monthsAgo);

            return [
                'label' => $date->translatedFormat('M Y'),
                'amount' => $scopedToActiveYear(Payment::whereIn('status', ['complet', 'partiel'])->notCancelled())
                    ->whereMonth('payment_date', $date->month)
                    ->whereYear('payment_date', $date->year)
                    ->sum('amount'),
            ];
        })->values();

        $alerts = [
            'partial_payments' => $scopedToActiveYear(
                Payment::where('status', 'partiel')
                    ->notCancelled()
                    ->where('remaining_balance', '>', 0)
            )->count(),
            'students_without_class' => Registration::where('status', 'active')->whereNull('classroom_id')->count(),
            'classrooms_without_teacher' => Classroom::whereNull('teacher_id')->count(),
            'missing_active_year' => $activeYear === null,
        ];

        return view('dashboard', compact('registrations', 'recentPayments', 'stats', 'alerts', 'activeYear', 'monthlyRevenue'));
    }
}
