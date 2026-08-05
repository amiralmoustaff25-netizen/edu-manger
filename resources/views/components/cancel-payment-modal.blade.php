{{--
    Modal global d'annulation de paiement.
    Un paiement validé ne peut jamais être supprimé (voir PaymentPolicy::delete) :
    cette action déclenche PaymentController::cancel() qui exige un motif obligatoire
    et conserve une trace d'audit complète (qui, quand, pourquoi).

    Déclenchement depuis n'importe quelle vue :
    <button x-on:click="$dispatch('open-cancel-payment', { action: '{{ route('payments.cancel', $payment) }}', receipt: '{{ $payment->receipt_number }}' })">
        Annuler
    </button>
--}}
<div
    x-data="{ open: false, action: '', receipt: '', reason: '' }"
    x-on:open-cancel-payment.window="action = $event.detail.action; receipt = $event.detail.receipt; reason = ''; open = true"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[65] flex items-center justify-center px-4"
    role="dialog"
    aria-modal="true"
>
    <div class="absolute inset-0 bg-slate-950/60" x-on:click="open = false"></div>
    <div class="relative w-full max-w-lg rounded-xl bg-white p-6 shadow-2xl dark:bg-slate-800" x-on:keydown.escape.window="open = false">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Annuler le paiement <span x-text="receipt"></span></h2>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
            Ce paiement est validé et ne peut pas être supprimé. L'annulation retire son montant des totaux financiers
            tout en conservant une trace d'audit (auteur, date, motif).
        </p>
        <form method="POST" :action="action" class="mt-4 space-y-3">
            @csrf
            @method('PATCH')
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Motif de l'annulation (obligatoire)</label>
                <textarea name="cancellation_reason" x-model="reason" required rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" x-on:click="open = false" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Annuler</button>
                <button type="submit" :disabled="reason.trim().length === 0" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50">Confirmer l'annulation</button>
            </div>
        </form>
    </div>
</div>
