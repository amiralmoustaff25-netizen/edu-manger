<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Détails du Paiement
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Boutons d'action -->
                    <div class="mb-6 flex flex-wrap gap-2">
                        <a href="{{ route('payments.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                            ← Retour
                        </a>
                        <a href="{{ route('payments.edit', $payment) }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Modifier
                        </a>
                        <a href="{{ route('payments.receipt', $payment) }}" target="_blank" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                            Télécharger le reçu (PDF)
                        </a>
                        <button onclick="window.print()" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                            Imprimer
                        </button>
                    </div>

                    <!-- Informations du paiement -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informations du paiement</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Numéro de reçu:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $payment->receipt_number }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Montant payé:</span>
                                    <span class="font-medium text-green-600 dark:text-green-400">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Statut:</span>
                                    @if($payment->status === 'complet')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Complet</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">Partiel</span>
                                    @endif
                                </div>
                                @if($payment->status === 'partiel')
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Reste à payer:</span>
                                        <span class="font-medium text-red-600 dark:text-red-400">{{ number_format($payment->remaining_balance, 0, ',', ' ') }} FCFA</span>
                                    </div>
                                @endif
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Mois:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $payment->month }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Date de paiement:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $payment->payment_date->format('d/m/Y') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Méthode:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $payment->payment_method }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Type:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $payment->payment_type }}</span>
                                </div>
                                @if($payment->comment)
                                    <div>
                                        <span class="text-gray-600 dark:text-gray-400">Commentaire:</span>
                                        <p class="font-medium text-gray-900 dark:text-white mt-1">{{ $payment->comment }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informations de l'élève</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Nom:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $payment->registration->user->name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Classe:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $payment->registration->classroom?->name ?? 'Non assigné' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Mensualité:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ number_format($payment->registration->monthly_fee, 0, ',', ' ') }} FCFA</span>
                                </div>
                            </div>

                            @if($payment->validatedBy)
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mt-6 mb-4">Validation</h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Validé par:</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $payment->validatedBy->name }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($payment->fee_breakdown && count($payment->fee_breakdown))
                        <div class="mt-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Détail des frais couverts</h3>
                            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-slate-700">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                                    <thead class="bg-gray-50 dark:bg-slate-700">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Frais</th>
                                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Montant dû</th>
                                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Montant payé</th>
                                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Reste</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                                        @foreach($payment->fee_breakdown as $fee)
                                            <tr>
                                                <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $fee['description'] ?? '-' }}</td>
                                                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ number_format($fee['amount'] ?? ($fee['remaining_amount'] ?? 0) + ($fee['amount_paid'] ?? 0), 0, ',', ' ') }} FCFA</td>
                                                <td class="px-4 py-3 text-right font-medium text-green-600 dark:text-green-400">{{ number_format($fee['amount_paid'] ?? 0, 0, ',', ' ') }} FCFA</td>
                                                <td class="px-4 py-3 text-right font-medium text-amber-700 dark:text-amber-400">{{ number_format($fee['remaining_balance'] ?? ($fee['remaining_amount'] ?? 0), 0, ',', ' ') }} FCFA</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
