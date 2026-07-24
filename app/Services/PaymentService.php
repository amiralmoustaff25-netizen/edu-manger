<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Applique un paiement aux factures en attente de l'inscription.
     * Renvoie le montant non alloué aux factures (non compté comme surplus cash).
     */
    public function applyPaymentToInvoices(Payment $payment, Registration $registration, ?float $maxAmount = null): float
    {
        $remaining = $maxAmount ?? (float) $payment->amount;

        if ($remaining <= 0) {
            return 0;
        }

        $invoices = Invoice::where('registration_id', $registration->id)
            ->whereIn('status', ['draft', 'sent', 'partial', 'overdue'])
            ->orderBy('due_date')
            ->get();

        foreach ($invoices as $invoice) {
            if ($remaining <= 0) {
                break;
            }

            $toApply = min($remaining, (float) $invoice->remaining_balance);
            if ($toApply <= 0) {
                continue;
            }

            $invoice->remaining_balance -= $toApply;
            $invoice->status = $invoice->remaining_balance <= 0 ? 'paid' : 'partial';
            $invoice->save();

            $payment->invoices()->attach($invoice->id, [
                'amount_applied' => $toApply,
            ]);

            $remaining -= $toApply;
        }

        return $remaining;
    }

    /**
     * Enregistre un surplus de paiement comme crédit disponible pour l'élève.
     */
    public function recordSurplusCredit(Payment $payment, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        \App\Models\Credit::create([
            'registration_id' => $payment->registration_id,
            'payment_id' => $payment->id,
            'amount' => $amount,
            'reason' => 'Surplus de paiement',
            'status' => 'available',
        ]);
    }

    /**
     * Journalise une action comptable dans la table d'audit.
     */
    public function logAction(
        string $action,
        string $modelType,
        ?int $modelId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $comment = null
    ): void {
        DB::table('audit_logs')->insert([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'comment' => $comment,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
