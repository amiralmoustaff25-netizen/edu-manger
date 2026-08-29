<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Détails de la Facture
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Boutons d'action -->
                    <div class="mb-6 flex gap-2">
                        <a href="{{ route('invoices.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                            ← Retour
                        </a>
                        <a href="{{ route('invoices.edit', $invoice) }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Modifier
                        </a>
                        <a href="{{ route('invoices.pdf', $invoice) }}" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                            Télécharger PDF
                        </a>
                    </div>

                    <!-- Informations de la facture -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informations de la facture</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Numéro de facture:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $invoice->invoice_number }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Date d'émission:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $invoice->issued_at->format('d/m/Y') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Date d'échéance:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $invoice->due_date->format('d/m/Y') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Statut:</span>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ \App\Models\Invoice::BADGE_COLORS[$invoice->status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400' }}">
                                        {{ \App\Models\Invoice::LABELS[$invoice->status] ?? $invoice->status }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informations de l'élève</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Nom:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $invoice->registration->user->name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Classe:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $invoice->registration->classroom?->name ?? 'Non assigné' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lignes de facture -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Lignes de facture</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Description</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Quantité</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Prix unitaire</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($invoice->items as $item)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $item->description }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $item->feeType?->name ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $item->quantity }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ number_format($item->unit_price, 0, ',', ' ') }} FCFA</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ number_format($item->total, 0, ',', ' ') }} FCFA</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <td colspan="4" class="px-4 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white">Total:</td>
                                        <td class="px-4 py-3 text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($invoice->total_amount, 0, ',', ' ') }} FCFA</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Paiements associés -->
                    @if($invoice->payments->count() > 0)
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Paiements associés</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Reçu</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Montant appliqué</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($invoice->payments as $payment)
                                            <tr>
                                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $payment->receipt_number }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $payment->payment_date->format('d/m/Y') }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ number_format($payment->pivot->amount_applied, 0, ',', ' ') }} FCFA</td>
                                                <td class="px-4 py-3">
                                                    @if($payment->status === 'complet')
                                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Complet</span>
                                                    @else
                                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">Partiel</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <!-- Résumé financier -->
                    <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="text-center">
                                <p class="text-sm text-gray-600 dark:text-gray-400">Total facture</p>
                                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($invoice->total_amount, 0, ',', ' ') }} FCFA</p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-600 dark:text-gray-400">Payé</p>
                                <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ number_format($invoice->total_amount - $invoice->remaining_balance, 0, ',', ' ') }} FCFA</p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-600 dark:text-gray-400">Reste à payer</p>
                                <p class="text-xl font-bold text-red-600 dark:text-red-400">{{ number_format($invoice->remaining_balance, 0, ',', ' ') }} FCFA</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
