<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Payment;
use App\Models\Registration;
use App\Notifications\PaymentReceived;
use App\Services\FeeService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payment::class);

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
        $this->authorize('create', Payment::class);

        return view('accounting.payments.create');
    }

    public function show(Payment $payment): View
    {
        $this->authorize('view', $payment);

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
        $this->authorize('view', $payment);

        $payment->load(['registration.user', 'registration.classroom', 'validatedBy']);

        $pdf = \PDF::loadView('accounting.payments.receipt', compact('payment'));
        
        return $pdf->download('recu-' . $payment->receipt_number . '.pdf');
    }

    public function store(StorePaymentRequest $request, FeeService $feeService, PaymentService $paymentService): RedirectResponse|JsonResponse
    {
        return DB::transaction(function () use ($request, $feeService, $paymentService) {
            $validated = $request->validated();

            $registration = Registration::with(['user', 'classroom', 'schoolYear'])->findOrFail($validated['registration_id']);
            $amountPaid = (float) $validated['amount_paid'];

            $selectedFees = $this->decodeSelectedFees($validated['selected_fees'] ?? null);

            if (!empty($selectedFees)) {
                $breakdown = $this->buildMultipleFeeBreakdown($registration, $feeService, $selectedFees);
            } else {
                $breakdown = $this->buildSimpleFeeBreakdown($registration, $feeService, $validated, $amountPaid);
            }

            if (!empty($breakdown['error'])) {
                return back()->withErrors(['selected_fees' => $breakdown['error']])->withInput();
            }

            if (empty($breakdown['items'])) {
                return back()->withErrors(['amount_paid' => 'Aucun frais valide sélectionné.'])->withInput();
            }

            $totalExpected = $breakdown['total_expected'];

            if ($amountPaid <= 0) {
                return back()->withErrors(['amount_paid' => 'Le montant versé doit être supérieur à 0.'])->withInput();
            }

            $items = $breakdown['items'];
            $remaining = $amountPaid;
            $allocatedItems = [];
            foreach ($items as $item) {
                $due = (float) ($item['remaining_amount'] ?? $item['amount']);
                $applied = min($remaining, $due);
                $item['amount_paid'] = $applied;
                $item['remaining_balance'] = $due - $applied;
                $remaining -= $applied;
                $allocatedItems[] = $item;
                if ($remaining <= 0) {
                    break;
                }
            }

            $isPartial = $amountPaid < $totalExpected;

            if ($isPartial) {
                Gate::authorize('validatePartial');
            }

            $remainingBalance = max(0, $totalExpected - $amountPaid);
            $canValidatePartial = Gate::allows('validatePartial');

            $payment = Payment::create([
                'registration_id' => $registration->id,
                'amount' => $amountPaid,
                'status' => $isPartial ? 'partiel' : 'complet',
                'remaining_balance' => $remainingBalance,
                'month' => $this->getPaymentMonth($allocatedItems, $validated['month'] ?? null),
                'payment_date' => $validated['payment_date'] ?? now(),
                'payment_method' => $validated['payment_method'] ?? 'espèces',
                'payment_type' => $this->getPaymentType($allocatedItems, $validated['payment_type'] ?? null),
                'comment' => $validated['comment'] ?? null,
                'fee_breakdown' => $allocatedItems,
                'validated_by' => ($isPartial && $canValidatePartial) ? auth()->id() : ($isPartial ? null : auth()->id()),
            ]);

            $paymentService->applyPaymentToInvoices($payment, $registration, min($amountPaid, $totalExpected));

            if ($amountPaid > $totalExpected) {
                $surplus = $amountPaid - $totalExpected;
                if (config('edu.overpayment_mode', 'change') === 'credit') {
                    $paymentService->recordSurplusCredit($payment, $surplus);
                } else {
                    $payment->comment = trim(($payment->comment ?? '') . ' [Monnaie rendue: ' . number_format($surplus, 2) . ' FCFA]');
                    $payment->save();
                }
            }

            $paymentService->logAction(
                'created',
                Payment::class,
                $payment->id,
                null,
                $payment->toArray(),
                'Paiement enregistré via formulaire'
            );

            if ($payment->status === 'complet') {
                $registration->user->notify(new PaymentReceived($payment));
            }

            $this->activateRegistrationIfNeeded($registration);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Paiement enregistré.', 'payment' => $payment], 201);
            }

            return redirect()->route('payments.show', $payment)->with([
                'success' => 'Paiement enregistré avec succès. Reçu : ' . $payment->receipt_number,
                'payment_receipt' => $payment->id,
            ]);
        });
    }

    private function decodeSelectedFees(mixed $selectedFees): array
    {
        if (is_string($selectedFees)) {
            $decoded = json_decode($selectedFees, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($selectedFees) ? $selectedFees : [];
    }

    private function buildMultipleFeeBreakdown(Registration $registration, FeeService $feeService, array $selectedFees): array
    {
        $pendingFees = collect($feeService->getPendingFees($registration))->keyBy('id');
        $items = [];
        $totalExpected = 0.0;

        foreach ($selectedFees as $input) {
            $id = $input['id'] ?? null;

            if (!$id || !$pendingFees->has($id)) {
                return ['items' => [], 'total_expected' => 0, 'error' => 'Un frais sélectionné est invalide ou a déjà été payé.'];
            }

            $fee = $pendingFees->get($id);

            if ($fee['status'] === 'paid') {
                return ['items' => [], 'total_expected' => 0, 'error' => "Frais déjà payé : {$fee['description']}"];
            }

            $inputAmount = (float) ($input['amount'] ?? 0);
            $remainingAmount = (float) ($fee['remaining_amount'] ?? $fee['amount']);

            if (abs($inputAmount - $remainingAmount) > 0.01) {
                return ['items' => [], 'total_expected' => 0, 'error' => "Montant du frais modifié : {$fee['description']}"];
            }

            $totalExpected += $remainingAmount;
            $items[] = $fee;
        }

        return ['items' => $items, 'total_expected' => $totalExpected, 'error' => null];
    }

    private function buildSimpleFeeBreakdown(Registration $registration, FeeService $feeService, array $validated, float $amountPaid): array
    {
        $pendingFees = collect($feeService->getPendingFees($registration))->keyBy('id');
        $code = $this->normalizePaymentType($validated['payment_type'] ?? 'mensualité');
        $month = $validated['month'] ?? null;

        if ($code === 'inscription') {
            $fee = $pendingFees->first(fn ($f) => $f['code'] === 'inscription');
            if ($fee) {
                return ['items' => [$fee], 'total_expected' => (float) $fee['remaining_amount'], 'error' => null];
            }
        }

        if ($month) {
            $fee = $pendingFees->first(fn ($f) => $f['code'] === $code && ($f['month'] ?? null) === $month);
            if ($fee) {
                return ['items' => [$fee], 'total_expected' => (float) $fee['remaining_amount'], 'error' => null];
            }
        }

        $expected = match ($code) {
            'inscription' => (float) $registration->registration_fee_paid,
            'mensualite' => (float) $registration->monthly_fee,
            'autre' => $amountPaid,
            default => $this->getOptionalFeeAmount($registration, $code),
        };

        if ($expected <= 0 && $code !== 'autre') {
            return ['items' => [], 'total_expected' => 0, 'error' => 'Aucun montant configuré pour ce type de frais.'];
        }

        $fee = [
            'id' => $month ? "{$code}-{$month}" : $code,
            'code' => $code,
            'description' => $month ? "Paiement {$code} - {$month}" : "Paiement {$code}",
            'month' => $month,
            'amount' => $expected,
            'remaining_amount' => $expected,
            'status' => 'pending',
            'priority' => 'medium',
            'due_date' => $validated['payment_date'] ?? now()->toDateString(),
        ];

        return ['items' => [$fee], 'total_expected' => $expected, 'error' => null];
    }

    private function getOptionalFeeAmount(Registration $registration, string $code): float
    {
        $classroomFee = \App\Models\ClassroomFee::with('feeType')
            ->where('classroom_id', $registration->classroom_id)
            ->where('school_year_id', $registration->school_year_id)
            ->whereHas('feeType', fn ($q) => $q->where('code', $code))
            ->first();

        return $classroomFee ? (float) $classroomFee->amount : 0;
    }

    private function getPaymentMonth(array $allocatedItems, ?string $defaultMonth): string
    {
        $months = array_filter(array_map(fn ($item) => $item['month'] ?? null, $allocatedItems));

        if (empty($months)) {
            return $defaultMonth ?? 'Multiple';
        }

        $unique = array_unique($months);

        return count($unique) === 1 ? array_values($unique)[0] : 'Multiple';
    }

    private function getPaymentType(array $allocatedItems, ?string $defaultType): string
    {
        $codes = array_filter(array_map(fn ($item) => $item['code'] ?? null, $allocatedItems));

        if (empty($codes)) {
            return $this->displayPaymentType($this->normalizePaymentType($defaultType)) ?: 'Multiple';
        }

        $unique = array_unique($codes);

        if (count($unique) !== 1) {
            return 'Multiple';
        }

        return $this->displayPaymentType(array_values($unique)[0]);
    }

    private function displayPaymentType(string $code): string
    {
        return match ($code) {
            'inscription' => 'inscription',
            'mensualite' => 'mensualité',
            'cantine' => 'cantine',
            'transport' => 'transport',
            'internat' => 'internat',
            'autre' => 'autre',
            default => $code,
        };
    }

    private function normalizePaymentType(?string $type): string
    {
        $map = [
            'mensualité' => 'mensualite',
            'inscription' => 'inscription',
            'cantine' => 'cantine',
            'transport' => 'transport',
            'internat' => 'internat',
            'autre' => 'autre',
        ];

        return $map[mb_strtolower($type ?? '')] ?? mb_strtolower($type ?? '');
    }

    private function activateRegistrationIfNeeded(Registration $registration): void
    {
        if ($registration->status === 'pending') {
            $registration->update(['status' => 'active']);
            $registration->user->update(['is_active' => true]);
        }
    }

    public function validationIndex(): View
    {
        Gate::authorize('validatePartial');

        $pendingPayments = Payment::with(['registration.user', 'registration.classroom'])
            ->pendingValidation()
            ->latest()
            ->paginate(15);

        return view('accounting.payments.validation', compact('pendingPayments'));
    }

    public function validatePayment(Payment $payment): RedirectResponse
    {
        Gate::authorize('validatePartial');

        if ($payment->status !== 'partiel' || $payment->validated_at) {
            return back()->with('error', 'Ce paiement ne peut pas être validé.');
        }

        $payment->validatePayment(auth()->id());

        return back()->with('success', 'Paiement validé avec succès.');
    }

    public function rejectPayment(Payment $payment, Request $request): RedirectResponse
    {
        Gate::authorize('validatePartial');

        if ($payment->status !== 'partiel' || $payment->validated_at) {
            return back()->with('error', 'Ce paiement ne peut pas être rejeté.');
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $payment->comment = ($payment->comment ?? '') . ' [REJETÉ: ' . $request->reason . ']';
        $payment->status = 'rejected';
        $payment->validated_by = auth()->id();
        $payment->validated_at = now();
        $payment->save();

        return back()->with('success', 'Paiement rejeté.');
    }
}
