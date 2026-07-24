<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Registration;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountingController extends Controller
{
    public function index(): View
    {
        $this->authorize('voir-comptabilite');

        $activeYear = SchoolYear::where('is_active', true)->first();
        
        // Statistiques générales
        $stats = [
            'total_revenue' => Payment::where('status', 'complet')->sum('amount'),
            'monthly_revenue' => Payment::where('status', 'complet')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
            'yearly_revenue' => Payment::where('status', 'complet')
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
            'total_payments' => Payment::count(),
            'complete_payments' => Payment::where('status', 'complet')->count(),
            'partial_payments' => \App\Models\Invoice::whereIn('status', ['partial', 'overdue'])->count(),
            'remaining_balance' => \App\Models\Invoice::whereIn('status', ['sent', 'partial', 'overdue'])->sum('remaining_balance'),
            'total_invoices' => \App\Models\Invoice::count(),
            'paid_invoices' => \App\Models\Invoice::where('status', 'paid')->count(),
            'pending_invoices' => \App\Models\Invoice::whereIn('status', ['draft', 'sent', 'partial', 'overdue'])->count(),
        ];

        // Revenus par mois de l'année courante
        $monthlyRevenue = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyRevenue[] = [
                'month' => date('F', mktime(0, 0, 0, $i, 1)),
                'amount' => Payment::where('status', 'complet')
                    ->whereMonth('created_at', $i)
                    ->whereYear('created_at', now()->year)
                    ->sum('amount'),
            ];
        }

        // Paiements récents
        $recentPayments = Payment::with(['registration.user', 'registration.classroom'])
            ->latest()
            ->take(10)
            ->get();

        // Paiements partiels en attente
        $partialPayments = Payment::with(['registration.user', 'registration.classroom'])
            ->where('status', 'partiel')
            ->latest()
            ->take(10)
            ->get();

        // Élèves avec impayés
        $studentsWithDebt = Registration::with(['user', 'classroom'])
            ->whereHas('payments', function ($query) {
                $query->where('status', 'partiel');
            })
            ->get()
            ->map(function ($registration) {
                $totalPaid = $registration->payments()->sum('amount');
                $totalDue = $registration->monthly_fee * 9; // 9 mois d'école
                $remaining = $totalDue - $totalPaid;
                
                return [
                    'student' => $registration->user,
                    'classroom' => $registration->classroom,
                    'total_paid' => $totalPaid,
                    'total_due' => $totalDue,
                    'remaining' => $remaining,
                ];
            });

        return view('accounting.dashboard', compact(
            'stats',
            'monthlyRevenue',
            'recentPayments',
            'partialPayments',
            'studentsWithDebt',
            'activeYear'
        ));
    }

    public function reports(): View
    {
        $this->authorize('voir-rapports-financiers');

        $activeYear = SchoolYear::where('is_active', true)->first();

        // Rapport journalier du mois courant
        $dailyReport = [];
        for ($i = 1; $i <= now()->daysInMonth; $i++) {
            $date = now()->setDay($i);
            $dailyReport[] = [
                'date' => $date->format('d/m/Y'),
                'amount' => Payment::where('status', 'complet')
                    ->whereDate('created_at', $date)
                    ->sum('amount'),
                'count' => Payment::where('status', 'complet')
                    ->whereDate('created_at', $date)
                    ->count(),
            ];
        }

        // Rapport par classe
        $classReport = Payment::with(['registration.classroom'])
            ->where('status', 'complet')
            ->whereYear('created_at', now()->year)
            ->get()
            ->groupBy(function ($payment) {
                return $payment->registration->classroom?->name ?? 'Non assigné';
            })
            ->map(function ($payments) {
                return [
                    'count' => $payments->count(),
                    'total' => $payments->sum('amount'),
                ];
            });

        // Rapport par type de paiement
        $paymentTypeReport = Payment::select('payment_type', \DB::raw('COUNT(*) as count'), \DB::raw('SUM(amount) as total'))
            ->where('status', 'complet')
            ->whereYear('created_at', now()->year)
            ->groupBy('payment_type')
            ->get();

        return view('accounting.reports', compact(
            'dailyReport',
            'classReport',
            'paymentTypeReport',
            'activeYear'
        ));
    }

    public function advancedReports(Request $request): View
    {
        $this->authorize('voir-rapports-financiers');

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $classroomId = $request->input('classroom_id');
        $feeTypeId = $request->input('fee_type_id');

        $query = Payment::with(['registration.user', 'registration.classroom'])
            ->whereBetween('payment_date', [$startDate, $endDate]);

        if ($classroomId) {
            $query->whereHas('registration', function ($q) use ($classroomId) {
                $q->where('classroom_id', $classroomId);
            });
        }

        $payments = $query->latest()->get();

        // Calculs pour le rapport
        $totalRevenue = $payments->where('status', 'complet')->sum('amount');
        $partialRevenue = $payments->where('status', 'partiel')->sum('amount');
        $totalPayments = $payments->count();
        $averagePayment = $totalPayments > 0 ? $payments->avg('amount') : 0;

        // Répartition par méthode de paiement
        $paymentMethods = $payments->groupBy('payment_method')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('amount'),
                ];
            });

        $classrooms = \App\Models\Classroom::all();
        $feeTypes = FeeType::all();

        return view('accounting.advanced-reports', compact(
            'payments',
            'totalRevenue',
            'partialRevenue',
            'totalPayments',
            'averagePayment',
            'paymentMethods',
            'classrooms',
            'feeTypes',
            'startDate',
            'endDate',
            'classroomId',
            'feeTypeId'
        ));
    }

    public function exportAdvancedReports(Request $request)
    {
        $this->authorize('voir-rapports-financiers');

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $classroomId = $request->input('classroom_id');

        $query = Payment::with(['registration.user', 'registration.classroom'])
            ->whereBetween('payment_date', [$startDate, $endDate]);

        if ($classroomId) {
            $query->whereHas('registration', function ($q) use ($classroomId) {
                $q->where('classroom_id', $classroomId);
            });
        }

        $payments = $query->latest()->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // En-têtes
        $sheet->setCellValue('A1', 'Rapport Financier Avancé');
        $sheet->setCellValue('A2', 'Du: ' . $startDate . ' Au: ' . $endDate);
        $sheet->setCellValue('A4', 'Date');
        $sheet->setCellValue('B4', 'Reçu');
        $sheet->setCellValue('C4', 'Élève');
        $sheet->setCellValue('D4', 'Classe');
        $sheet->setCellValue('E4', 'Montant');
        $sheet->setCellValue('F4', 'Statut');
        $sheet->setCellValue('G4', 'Méthode');
        $sheet->setCellValue('H4', 'Type');

        // Données
        $row = 5;
        foreach ($payments as $payment) {
            $sheet->setCellValue('A' . $row, $payment->payment_date->format('d/m/Y'));
            $sheet->setCellValue('B' . $row, $payment->receipt_number);
            $sheet->setCellValue('C' . $row, $payment->registration->user->name);
            $sheet->setCellValue('D' . $row, $payment->registration->classroom?->name ?? 'Non assigné');
            $sheet->setCellValue('E' . $row, $payment->amount);
            $sheet->setCellValue('F' . $row, ucfirst($payment->status));
            $sheet->setCellValue('G' . $row, ucfirst($payment->payment_method));
            $sheet->setCellValue('H' . $row, ucfirst($payment->payment_type));
            $row++;
        }

        // Style
        $sheet->getStyle('A4:H4')->getFont()->setBold(true);
        $sheet->getStyle('A1:H1')->getFont()->setBold(true)->setSize(14);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        $filename = 'rapport-financier-' . now()->format('Y-m-d') . '.xlsx';
        
        return response()->streamDownload(
            function () use ($writer) {
                $writer->save('php://output');
            },
            $filename
        );
    }

    public function alerts(): View
    {
        $this->authorize('voir-alertes-impayes');

        // Élèves avec impayés (paiements partiels)
        $studentsWithPartialPayments = Payment::with(['registration.user', 'registration.classroom'])
            ->where('status', 'partiel')
            ->where('remaining_balance', '>', 0)
            ->latest()
            ->get()
            ->groupBy('registration_id')
            ->map(function ($payments) {
                $registration = $payments->first()->registration;
                $totalPaid = $payments->sum('amount');
                $totalRemaining = $payments->sum('remaining_balance');
                $lastPaymentDate = $payments->max('payment_date');
                
                return [
                    'student' => $registration->user,
                    'classroom' => $registration->classroom,
                    'total_paid' => $totalPaid,
                    'total_remaining' => $totalRemaining,
                    'last_payment_date' => $lastPaymentDate,
                    'monthly_fee' => $registration->monthly_fee,
                    'payments_count' => $payments->count(),
                ];
            });

        // Factures en retard
        $overdueInvoices = \App\Models\Invoice::with(['registration.user', 'registration.classroom'])
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->where('due_date', '<', now())
            ->where('remaining_balance', '>', 0)
            ->latest()
            ->get();

        // Élèves sans paiement depuis 30 jours
        $studentsWithoutRecentPayments = Registration::with(['user', 'classroom', 'payments'])
            ->where('status', 'active')
            ->whereDoesntHave('payments', function ($query) {
                $query->where('payment_date', '>=', now()->subDays(30));
            })
            ->whereHas('payments') // A déjà payé au moins une fois
            ->get()
            ->map(function ($registration) {
                $lastPayment = $registration->payments()->latest()->first();
                return [
                    'student' => $registration->user,
                    'classroom' => $registration->classroom,
                    'last_payment_date' => $lastPayment?->payment_date,
                    'days_since_last_payment' => $lastPayment ? now()->diffInDays($lastPayment->payment_date) : null,
                ];
            });

        return view('accounting.alerts', compact(
            'studentsWithPartialPayments',
            'overdueInvoices',
            'studentsWithoutRecentPayments'
        ));
    }

    public function cashFlow(): View
    {
        $this->authorize('voir-tresorerie');

        // Entrées du mois courant
        $monthlyInflow = Payment::where('status', 'complet')
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');

        // Entrées du mois précédent
        $previousMonthInflow = Payment::where('status', 'complet')
            ->whereMonth('payment_date', now()->subMonth()->month)
            ->whereYear('payment_date', now()->subMonth()->year)
            ->sum('amount');

        // Évolution mensuelle sur 6 mois
        $monthlyEvolution = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthlyEvolution[] = [
                'month' => $date->format('M Y'),
                'amount' => Payment::where('status', 'complet')
                    ->whereMonth('payment_date', $date->month)
                    ->whereYear('payment_date', $date->year)
                    ->sum('amount'),
            ];
        }

        // Paiements par jour du mois courant
        $dailyCashFlow = [];
        for ($i = 1; $i <= now()->daysInMonth; $i++) {
            $date = now()->setDay($i);
            $dailyCashFlow[] = [
                'date' => $date->format('d/m'),
                'amount' => Payment::where('status', 'complet')
                    ->whereDate('payment_date', $date)
                    ->sum('amount'),
            ];
        }

        return view('accounting.cash-flow', compact(
            'monthlyInflow',
            'previousMonthInflow',
            'monthlyEvolution',
            'dailyCashFlow'
        ));
    }
}
