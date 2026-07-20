<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Modifier la Facture
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <a href="{{ route('invoices.show', $invoice) }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                            ← Retour
                        </a>
                    </div>

                    <form action="{{ route('invoices.update', $invoice) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="due_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date d'échéance</label>
                                <input type="date" name="due_date" id="due_date" value="{{ $invoice->due_date->format('Y-m-d') }}" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Statut</label>
                                <select name="status" id="status" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="pending" {{ $invoice->status === 'pending' ? 'selected' : '' }}>En attente</option>
                                    <option value="paid" {{ $invoice->status === 'paid' ? 'selected' : '' }}>Payée</option>
                                    <option value="overdue" {{ $invoice->status === 'overdue' ? 'selected' : '' }}>En retard</option>
                                    <option value="cancelled" {{ $invoice->status === 'cancelled' ? 'selected' : '' }}>Annulée</option>
                                </select>
                            </div>

                            <div>
                                <label for="remaining_balance" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reste à payer (FCFA)</label>
                                <input type="number" name="remaining_balance" id="remaining_balance" value="{{ $invoice->remaining_balance }}" step="0.01" min="0" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                Enregistrer les modifications
                            </button>
                            <a href="{{ route('invoices.show', $invoice) }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                Annuler
                            </a>
                        </div>
                    </form>

                    <!-- Informations de la facture (lecture seule) -->
                    <div class="mt-8 bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informations de la facture</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">Numéro de facture:</span>
                                <span class="font-medium text-gray-900 dark:text-white ml-2">{{ $invoice->invoice_number }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">Élève:</span>
                                <span class="font-medium text-gray-900 dark:text-white ml-2">{{ $invoice->registration->user->name }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">Classe:</span>
                                <span class="font-medium text-gray-900 dark:text-white ml-2">{{ $invoice->registration->classroom?->name ?? 'Non assigné' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">Total:</span>
                                <span class="font-medium text-gray-900 dark:text-white ml-2">{{ number_format($invoice->total_amount, 0, ',', ' ') }} FCFA</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
