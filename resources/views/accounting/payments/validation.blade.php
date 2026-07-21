<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Validation des Paiements
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <a href="{{ route('payments.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                            ← Retour aux paiements
                        </a>
                    </div>

                    @if($pendingPayments->count() === 0)
                        <div class="text-center py-12">
                            <div class="text-6xl mb-4">✅</div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Aucun paiement en attente</h3>
                            <p class="text-gray-600 dark:text-gray-400">Tous les paiements ont été traités.</p>
                        </div>
                    @else
                        <div class="mb-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $pendingPayments->total() }} paiement(s) en attente de validation
                            </p>
                        </div>

                        <div class="space-y-4">
                            @foreach($pendingPayments as $payment)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <div class="md:col-span-2">
                                            <div class="flex items-center gap-3 mb-2">
                                                <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center">
                                                    <span class="text-xl">👤</span>
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-gray-900 dark:text-white">
                                                        {{ $payment->registration->user->name }}
                                                    </p>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                                        {{ $payment->registration->classroom->name ?? 'Non assigné' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">Montant</p>
                                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                                {{ number_format($payment->amount, 0) }} FCFA
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                Reste: {{ number_format($payment->remaining_balance, 0) }} FCFA
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">Mois</p>
                                            <p class="font-semibold text-gray-900 dark:text-white">{{ $payment->month }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                {{ $payment->payment_date->format('d/m/Y') }}
                                            </p>
                                        </div>
                                    </div>

                                    @if($payment->comment)
                                        <div class="mt-4 p-3 bg-gray-100 dark:bg-gray-600 rounded text-sm text-gray-700 dark:text-gray-300">
                                            <strong>Note:</strong> {{ $payment->comment }}
                                        </div>
                                    @endif

                                    <div class="mt-4 flex gap-2 justify-end">
                                        <form action="{{ route('payments.reject', $payment) }}" method="POST" class="flex gap-2">
                                            @csrf
                                            <input type="text" name="reason" placeholder="Motif du rejet..." required
                                                class="px-3 py-2 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm">
                                                Rejeter
                                            </button>
                                        </form>
                                        <form action="{{ route('payments.validate', $payment) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">
                                                ✓ Valider
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{ $pendingPayments->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
