<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Payment;
use App\Models\Registration;
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
        $expectedMonthlyFee = (float) $registration->monthly_fee;
        $amountPaid = (float) $validated['amount_paid'];
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

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Paiement enregistré avec succès.',
                'payment' => $payment,
            ], 201);
        }

        return back()->with('success', 'Paiement enregistré avec succès.');
    }
}
