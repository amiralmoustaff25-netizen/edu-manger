<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Credit;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\SchoolConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('voir-comptabilite');

        $query = Payment::with(['registration.user', 'registration.classroom', 'validatedBy']);

        // Filtres
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->whereHas('registration.user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('month')) {
            $query->where('month', $request->string('month'));
        }

        if ($request->filled('year')) {
            $query->whereYear('created_at', $request->string('year'));
        }

        $payments = $query->latest()->paginate(15)->withQueryString();

        return view('accounting.payments.index', compact('payments'));
    }

    public function create(): View
    {
        $this->authorize('enregistrer-paiement');

        return view('accounting.payments.create');
    }

    public function show(Payment $payment): View
    {
        $this->authorize('voir-comptabilite');

        $payment->load(['registration.user', 'registration.classroom', 'validatedBy']);

        return view('accounting.payments.show', compact('payment'));
    }

    public function edit(Payment $payment): View
    {
        $this->authorize('modifier-paiement', $payment);

        $payment->load(['registration.user', 'registration.classroom']);

        return view('accounting.payments.edit', compact('payment'));
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('modifier-paiement', $payment);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:complet,partiel',
            'remaining_balance' => 'nullable|numeric|min:0',
            'month' => 'required|string',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'payment_type' => 'required|string',
            'comment' => 'nullable|string',
        ]);

        $payment->update($validated);

        return redirect()->route('payments.show', $payment)
            ->with('success', 'Paiement modifié avec succès.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $this->authorize('supprimer-paiement', $payment);

        $payment->delete();

        return redirect()->route('payments.index')
            ->with('success', 'Paiement supprimé avec succès.');
    }

    public function exportReceipt(Payment $payment)
    {
        $this->authorize('voir-comptabilite');

        $payment->load(['registration.user', 'registration.classroom', 'validatedBy']);

        $pdf = \PDF::loadView('accounting.payments.receipt', compact('payment'));
        
        return $pdf->download('recu-' . $payment->receipt_number . '.pdf');
    }

    public function store(StorePaymentRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        $registration = Registration::findOrFail($validated['registration_id']);
        $amountPaid = (float) $validated['amount_paid'];
        
        // Vérifier la règle de paiement séquentiel
        $config = SchoolConfiguration::current();
        if ($config->sequential_payment_rule && !$config->allow_future_payment) {
            $this->validateSequentialPayment($registration, $validated['month']);
        }
        
        // Récupérer les frais sélectionnés si présents
        $selectedFees = $request->input('selected_fees', []);
        
        if (empty($selectedFees)) {
            // Mode ancien : paiement simple mensuel
            $expectedMonthlyFee = (float) $registration->monthly_fee;
            $isPartial = $amountPaid < $expectedMonthlyFee;

            if ($isPartial && Gate::denies('validatePartial', Payment::class)) {
                abort(403, 'Seul le manager-comptable peut valider un paiement partiel.');
            }

            $payment = Payment::create([
                'registration_id' => $registration->id,
                'amount' => $amountPaid,
                'status' => $isPartial ? 'partiel' : 'complet',
                'remaining_balance' => $isPartial ? $expectedMonthlyFee - $amountPaid : 0,
                'month' => $validated['month'],
                'payment_date' => $validated['payment_date'] ?? now(),
                'payment_method' => $validated['payment_method'] ?? 'espèces',
                'payment_type' => $validated['payment_type'] ?? 'mensualité',
                'comment' => $validated['comment'] ?? null,
                'validated_by' => auth()->id(),
            ]);

            // Gestion du trop-perçu
            if ($amountPaid > $expectedMonthlyFee) {
                $overpayment = $amountPaid - $expectedMonthlyFee;
                $this->handleOverpayment($registration, $payment, $overpayment, $config->overpayment_mode);
            }
        } else {
            // Mode nouveau : paiement de plusieurs frais
            $totalExpected = collect($selectedFees)->sum('amount');
            $isPartial = $amountPaid < $totalExpected;

            if ($isPartial && Gate::denies('validatePartial', Payment::class)) {
                abort(403, 'Seul le manager-comptable peut valider un paiement partiel.');
            }

            // Créer un paiement principal regroupé
            $payment = Payment::create([
                'registration_id' => $registration->id,
                'amount' => $amountPaid,
                'status' => $isPartial ? 'partiel' : 'complet',
                'remaining_balance' => $isPartial ? $totalExpected - $amountPaid : 0,
                'month' => $validated['month'] ?? 'Multiple',
                'payment_date' => $validated['payment_date'] ?? now(),
                'payment_method' => $validated['payment_method'] ?? 'espèces',
                'payment_type' => 'multiple',
                'comment' => $validated['comment'] ?? 'Paiement de plusieurs frais: ' . collect($selectedFees)->pluck('description')->implode(', '),
                'validated_by' => auth()->id(),
                'receipt_number' => $this->generateReceiptNumber(),
            ]);

            // Gestion du trop-perçu
            if ($amountPaid > $totalExpected) {
                $overpayment = $amountPaid - $totalExpected;
                $this->handleOverpayment($registration, $payment, $overpayment, $config->overpayment_mode);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Paiement enregistré avec succès.',
                'payment' => $payment,
            ], 201);
        }

        return back()->with('success', 'Paiement enregistré avec succès.');
    }

    private function handleOverpayment(Registration $registration, Payment $payment, float $overpayment, string $mode): void
    {
        if ($mode === 'credit') {
            // Mode crédit : créer un avoir pour l'élève
            Credit::create([
                'registration_id' => $registration->id,
                'payment_id' => $payment->id,
                'amount' => $overpayment,
                'reason' => 'Trop-perçu lors du paiement',
                'status' => 'available',
            ]);
            
            // Ajouter une note au commentaire du paiement
            $payment->comment .= ' (Avoir créé: ' . number_format($overpayment, 2) . ' FCFA)';
            $payment->save();
        }
        // Mode 'change' : la monnaie est rendue immédiatement, rien à stocker
    }

    private function validateSequentialPayment(Registration $registration, string $targetMonth): void
    {
        $monthsOrder = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        $targetIndex = array_search($targetMonth, $monthsOrder);
        
        if ($targetIndex === false) {
            return; // Mois non reconnu, on ignore
        }

        // Récupérer tous les paiements de l'élève
        $payments = Payment::where('registration_id', $registration->id)
            ->where('status', 'complet')
            ->pluck('month')
            ->toArray();

        // Vérifier si tous les mois précédents sont payés
        for ($i = 0; $i < $targetIndex; $i++) {
            $previousMonth = $monthsOrder[$i];
            if (!in_array($previousMonth, $payments)) {
                abort(400, "Les échéances précédentes doivent être soldées. Le mois de {$previousMonth} n'est pas payé.");
            }
        }
    }

    private function generateReceiptNumber(): string
    {
        $date = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        return "REC-{$date}-{$random}";
    }
}
