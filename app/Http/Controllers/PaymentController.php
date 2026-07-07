<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;  // ✅ AJOUT
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    // ✅ CHANGEment du type de retour
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
