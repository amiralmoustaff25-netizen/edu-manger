<?php

namespace App\Http\Controllers;

use App\Models\FeeType;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Services\FeeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountingController extends Controller
{
    public function index(FeeService $feeService): View
    {
        $this->authorize('voir-comptabilite');

        $activeYear = SchoolYear::where('is_active', true)->first();

        // Solde restant réel : ne JAMAIS sommer la colonne `remaining_balance` de plusieurs
        // paiements (instantané par transaction, pas cumulable). On recalcule via FeeService
        // le solde exact dû par chaque inscription active de l'année en cours.
        $activeRegistrations = $activeYear
            ? Registration::where('school_year_id', $activeYear->id)->where('status', 'active')->get()
            : collect();
        $remainingBalance = $activeRegistrations->sum(
            fn (Registration $registration) => $feeService->getFinancialSituation($registration)['remaining']
        );

        // Statistiques générales (les paiements annulés et rejetés sont exclus de tous les
        // totaux financiers). "Revenu" doit inclure l'argent réellement encaissé via les
        // paiements partiels, pas seulement les paiements complets : le champ amount d'un
        // paiement partiel est un montant réellement reçu, pas une projection.
        $stats = [
            'total_revenue' => Payment::whereIn('status', ['complet', 'partiel'])->notCancelled()->sum('amount'),
            'monthly_revenue' => Payment::whereIn('status', ['complet', 'partiel'])->notCancelled()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
            'yearly_revenue' => Payment::whereIn('status', ['complet', 'partiel'])->notCancelled()
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
            'total_payments' => Payment::notCancelled()->count(),
            'complete_payments' => Payment::where('status', 'complet')->notCancelled()->count(),
            'partial_payments' => Payment::where('status', 'partiel')->notCancelled()->count(),
            'remaining_balance' => $remainingBalance,
            'total_invoices' => \App\Models\Invoice::count(),
            'paid_invoices' => \App\Models\Invoice::where('status', 'paid')->count(),
            'pending_invoices' => \App\Models\Invoice::whereIn('status', ['draft', 'sent', 'partial', 'overdue'])->count(),
        ];

        // Revenus par mois de l'année courante
        $monthlyRevenue = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyRevenue[] = [
                'month' => date('F', mktime(0, 0, 0, $i, 1)),
                'amount' => Payment::whereIn('status', ['complet', 'partiel'])->notCancelled()
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
            ->notCancelled()
            ->latest()
            ->take(10)
            ->get();

        // Élèves avec impayés : solde recalculé via FeeService (frais réellement dus),
        // jamais via un total_due forfaitaire (ancien calcul "monthly_fee * 9" incohérent
        // avec les frais d'inscription, options et dérogations tarifaires).
        $studentsWithDebt = Registration::with(['user', 'classroom'])
            ->whereHas('payments', function ($query) {
                $query->where('status', 'partiel')->notCancelled();
            })
            ->get()
            ->map(function (Registration $registration) use ($feeService) {
                $situation = $feeService->getFinancialSituation($registration);

                return [
                    'student' => $registration->user,
                    'classroom' => $registration->classroom,
                    'total_paid' => $situation['paid'],
                    'total_due' => $situation['expected'],
                    'remaining' => $situation['remaining'],
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

    /**
     * "Rapports financiers" a été fusionnée dans "Analyse avancée" (accounting.advanced-reports) :
     * mêmes données (revenus, répartition par classe/type de paiement), en mieux — filtres de
     * période/classe et export Excel en plus. Redirection conservée pour tout lien existant.
     */
    public function reports()
    {
        return redirect()->route('accounting.advanced-reports');
    }

    /**
     * Requête de paiements filtrée par période/classe/type de frais, partagée entre
     * l'écran de rapport et son export Excel pour qu'ils restent toujours cohérents.
     */
    private function filteredPaymentsQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $classroomId = $request->input('classroom_id');
        $feeTypeId = $request->input('fee_type_id');

        // Seuls les paiements complets et partiels sont un revenu réel (même règle que
        // AccountingController::index et le tableau de bord) : sans ce filtre, les paiements
        // rejetés/en attente gonflaient à tort le revenu total, les répartitions par classe/
        // type/méthode de paiement, et l'export Excel.
        $query = Payment::with(['registration.user', 'registration.classroom'])
            ->whereIn('status', ['complet', 'partiel'])
            ->notCancelled()
            ->whereBetween('payment_date', [$startDate, $endDate]);

        if ($classroomId) {
            $query->whereHas('registration', function ($q) use ($classroomId) {
                $q->where('classroom_id', $classroomId);
            });
        }

        if ($feeTypeId) {
            // payments.payment_type stocke un libellé affichable ('mensualité', avec
            // accent) et non le code brut de fee_types ('mensualite') — voir
            // PaymentController::displayPaymentType(). Traduire le code vers ce même
            // libellé avant de filtrer, sinon la comparaison ne matche jamais.
            $feeTypeCode = FeeType::find($feeTypeId)?->code;
            $paymentTypeLabel = match ($feeTypeCode) {
                'mensualite' => 'mensualité',
                null => null,
                default => $feeTypeCode,
            };
            if ($paymentTypeLabel) {
                $query->where('payment_type', $paymentTypeLabel);
            }
        }

        return $query;
    }

    public function advancedReports(Request $request): View
    {
        // voir-rapports-avances, pas voir-rapports-financiers : cette dernière était la
        // permission de l'ancienne page "Rapports financiers", fusionnée dans "Analyse
        // avancée" au Checkpoint 12 (voir reports()/config/sidebar.php ci-dessus) — le menu
        // utilise déjà voir-rapports-avances, ce contrôleur avait gardé l'ancienne par oubli.
        $this->authorize('voir-rapports-avances');

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $classroomId = $request->input('classroom_id');
        $feeTypeId = $request->input('fee_type_id');

        $payments = $this->filteredPaymentsQuery($request)->latest()->get();

        // Calculs pour le rapport (les paiements annulés sont déjà exclus par la requête ci-dessus).
        // Le "revenu total" doit inclure l'argent réellement encaissé via les paiements
        // partiels (le champ amount est bien un montant reçu, pas une projection) : avant
        // ce correctif, $totalRevenue ne comptait que les paiements complets et sous-évaluait
        // donc l'encaissement réel sur la période.
        $completeRevenue = $payments->where('status', 'complet')->sum('amount');
        $partialRevenue = $payments->where('status', 'partiel')->sum('amount');
        $totalRevenue = $completeRevenue + $partialRevenue;
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

        // Répartition par classe et par type de paiement — fusionnées ici depuis l'ancienne
        // page "Rapports financiers" (accounting.reports, supprimée) qui montrait les mêmes
        // données mais figées sur l'année courante, sans filtre ni export : ce même écran
        // couvre désormais les deux besoins, sur la période choisie ci-dessus.
        $classroomBreakdown = $payments->groupBy(fn ($payment) => $payment->registration->classroom?->name ?? 'Non assigné')
            ->map(fn ($group) => ['count' => $group->count(), 'total' => $group->sum('amount')]);

        $paymentTypeBreakdown = $payments->groupBy('payment_type')
            ->map(fn ($group) => ['count' => $group->count(), 'total' => $group->sum('amount')]);

        $classrooms = \App\Models\Classroom::all();
        $feeTypes = FeeType::all();

        return view('accounting.advanced-reports', compact(
            'payments',
            'totalRevenue',
            'partialRevenue',
            'totalPayments',
            'averagePayment',
            'paymentMethods',
            'classroomBreakdown',
            'paymentTypeBreakdown',
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
        // Voir advancedReports() ci-dessus — même correctif.
        $this->authorize('voir-rapports-avances');

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        $payments = $this->filteredPaymentsQuery($request)->latest()->get();

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
            $sheet->setCellValue('B' . $row, $this->escapeCellValue($payment->receipt_number));
            $sheet->setCellValue('C' . $row, $this->escapeCellValue($payment->registration->user->name));
            $sheet->setCellValue('D' . $row, $this->escapeCellValue($payment->registration->classroom?->name ?? 'Non assigné'));
            $sheet->setCellValue('E' . $row, $payment->amount);
            $sheet->setCellValue('F' . $row, $this->escapeCellValue(ucfirst($payment->status)));
            $sheet->setCellValue('G' . $row, $this->escapeCellValue(ucfirst($payment->payment_method)));
            $sheet->setCellValue('H' . $row, $this->escapeCellValue(ucfirst($payment->payment_type)));
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

    private function escapeCellValue(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        if (preg_match('/^[\+\-=\\t\\r\\n@]/', $value)) {
            return "'" . $value;
        }

        return $value;
    }

    public function alerts(FeeService $feeService): View
    {
        $this->authorize('voir-alertes-impayes');

        // Élèves avec impayés (paiements partiels, hors paiements annulés).
        // Le solde restant est recalculé via FeeService : la colonne `remaining_balance`
        // d'un paiement est un instantané au moment de CETTE transaction, elle ne peut
        // pas être sommée entre plusieurs paiements sans surestimer massivement la dette.
        $studentsWithPartialPayments = Payment::with(['registration.user', 'registration.classroom'])
            ->where('status', 'partiel')
            ->notCancelled()
            ->latest()
            ->get()
            ->groupBy('registration_id')
            ->map(function ($payments) use ($feeService) {
                $registration = $payments->first()->registration;
                $situation = $feeService->getFinancialSituation($registration);
                $lastPaymentDate = $payments->max('payment_date');

                return [
                    'student' => $registration->user,
                    'classroom' => $registration->classroom,
                    'total_paid' => $situation['paid'],
                    'total_remaining' => $situation['remaining'],
                    'last_payment_date' => $lastPaymentDate,
                    'monthly_fee' => $registration->monthly_fee,
                    'payments_count' => $payments->count(),
                ];
            })
            ->filter(fn (array $entry) => $entry['total_remaining'] > 0);

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

        // La trésorerie mesure l'argent réellement encaissé : les paiements partiels
        // comptent (leur montant "amount" est bien reçu), contrairement aux paiements
        // rejetés/annulés qui restent exclus.
        $revenueStatuses = ['complet', 'partiel'];

        // Entrées du mois courant
        $monthlyInflow = Payment::whereIn('status', $revenueStatuses)->notCancelled()
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');

        // Entrées du mois précédent
        $previousMonthInflow = Payment::whereIn('status', $revenueStatuses)->notCancelled()
            ->whereMonth('payment_date', now()->subMonth()->month)
            ->whereYear('payment_date', now()->subMonth()->year)
            ->sum('amount');

        // Évolution mensuelle sur 6 mois
        $monthlyEvolution = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthlyEvolution[] = [
                'month' => $date->format('M Y'),
                'amount' => Payment::whereIn('status', $revenueStatuses)->notCancelled()
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
                'amount' => Payment::whereIn('status', $revenueStatuses)->notCancelled()
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
