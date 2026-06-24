<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        Gate::authorize('create', Payment::class);

        $validated = $request->validate([
            'registration_id' => ['required', 'exists:registrations,id'],
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'month' => ['required', 'string', 'max:50'],
        ]);

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
