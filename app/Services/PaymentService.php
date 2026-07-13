<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function canPayInvoice(Invoice $invoice): bool
    {
        $registration = $invoice->registration;

        $previousInvoices = Invoice::where('registration_id', $registration->id)
            ->where('id', '<', $invoice->id)
            ->orderBy('id', 'asc')
            ->get();

        foreach ($previousInvoices as $prevInvoice) {
            if (!in_array($prevInvoice->status, ['paid', 'partial'])) {
                return false;
            }

            if ($prevInvoice->status === 'partial' && $prevInvoice->remaining_balance > 0) {
                return false;
            }
        }

        return true;
    }

    public function applyPaymentToInvoice(Payment $payment, Invoice $invoice): void
    {
        $remainingToPay = min($payment->amount, $invoice->remaining_balance);
        $invoice->remaining_balance -= $remainingToPay;

        if ($invoice->remaining_balance <= 0) {
            $invoice->status = 'paid';
        } else {
            $invoice->status = 'partial';
        }
        $invoice->save();

        $payment->invoices()->attach($invoice->id, [
            'amount_applied' => $remainingToPay,
        ]);
    }

    public function handleSurplusAsCredit(Registration $registration, float $surplus): void
    {
        if ($surplus <= 0) {
            return;
        }

        CreditNote::create([
            'registration_id' => $registration->id,
            'amount' => $surplus,
            'reason' => 'Surplus de paiement',
            'used_amount' => 0,
            'status' => 'available',
        ]);
    }

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
