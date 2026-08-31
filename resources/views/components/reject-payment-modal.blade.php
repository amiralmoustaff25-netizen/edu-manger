{{--
    Modal global de rejet d'un paiement partiel en attente.
    Remplace un <input type="text"> jusque-là embarqué directement dans la ligne du
    tableau (accounting/payments/index.blade.php) : sur les paiements partiels non
    validés, la cellule "Actions" affichait un champ + bouton en plus des autres liens,
    provoquant un retour à la ligne et un désalignement visible avec les autres lignes
    du tableau (chacune avec un nombre d'actions différent). Même mécanisme que
    cancel-payment-modal.

    Déclenchement depuis n'importe quelle vue :
    <button x-on:click="$dispatch('open-reject-payment', { action: '{{ route('payments.reject', $payment) }}', receipt: '{{ $payment->receipt_number }}' })">
        Rejeter
    </button>
--}}
<div
    x-data="{ open: false, action: '', receipt: '', reason: '' }"
    x-on:open-reject-payment.window="action = $event.detail.action; receipt = $event.detail.receipt; reason = ''; open = true"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[65] flex items-center justify-center px-4"
    role="dialog"
    aria-modal="true"
>
    <div class="absolute inset-0 bg-slate-950/60" x-on:click="open = false"></div>
    <div class="relative w-full max-w-lg rounded-xl bg-white p-6 shadow-2xl dark:bg-slate-800" x-on:keydown.escape.window="open = false">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Rejeter le paiement <span x-text="receipt"></span></h2>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
            Ce paiement partiel sera marqué comme rejeté. Le motif saisi sera conservé dans l'historique.
        </p>
        <form method="POST" :action="action" class="mt-4 space-y-3">
            @csrf
            <div>
                <label for="reject_reason" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Motif du rejet (obligatoire)</label>
                <textarea name="reason" id="reject_reason" x-model="reason" required rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" x-on:click="open = false" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Annuler</button>
                <button type="submit" :disabled="reason.trim().length === 0" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50">Confirmer le rejet</button>
            </div>
        </form>
    </div>
</div>
