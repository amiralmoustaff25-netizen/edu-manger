<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Modifier le Paiement
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Boutons de retour -->
                    <div class="mb-6">
                        <a href="{{ route('payments.show', $payment) }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                            ← Retour
                        </a>
                    </div>

                    <!-- Formulaire de modification -->
                    <form action="{{ route('payments.update', $payment) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Montant payé (FCFA)</label>
                                <input type="number" name="amount" id="amount" value="{{ $payment->amount }}" required step="0.01" min="0"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Statut</label>
                                <select name="status" id="status" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="complet" {{ $payment->status === 'complet' ? 'selected' : '' }}>Complet</option>
                                    <option value="partiel" {{ $payment->status === 'partiel' ? 'selected' : '' }}>Partiel</option>
                                </select>
                            </div>

                            <div>
                                <label for="remaining_balance" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reste à payer (FCFA)</label>
                                <input type="number" name="remaining_balance" id="remaining_balance" value="{{ $payment->remaining_balance }}" step="0.01" min="0"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label for="month" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mois</label>
                                <select name="month" id="month" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach(config('edu.school_months') as $m)
                                        <option value="{{ $m }}" {{ $payment->month === $m ? 'selected' : '' }}>{{ $m }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="payment_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date de paiement</label>
                                <input type="date" name="payment_date" id="payment_date" value="{{ $payment->payment_date->format('Y-m-d') }}" required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label for="payment_method" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Méthode de paiement</label>
                                <select name="payment_method" id="payment_method" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="espèces" {{ $payment->payment_method === 'espèces' ? 'selected' : '' }}>Espèces</option>
                                    <option value="virement" {{ $payment->payment_method === 'virement' ? 'selected' : '' }}>Virement</option>
                                    <option value="chèque" {{ $payment->payment_method === 'chèque' ? 'selected' : '' }}>Chèque</option>
                                    <option value="carte" {{ $payment->payment_method === 'carte' ? 'selected' : '' }}>Carte bancaire</option>
                                    <option value="mobile" {{ $payment->payment_method === 'mobile' ? 'selected' : '' }}>Mobile money</option>
                                </select>
                            </div>

                            <div>
                                <label for="payment_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type de paiement</label>
                                <select name="payment_type" id="payment_type" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="inscription" {{ $payment->payment_type === 'inscription' ? 'selected' : '' }}>Inscription</option>
                                    <option value="mensualité" {{ $payment->payment_type === 'mensualité' ? 'selected' : '' }}>Mensualité</option>
                                    <option value="autre" {{ $payment->payment_type === 'autre' ? 'selected' : '' }}>Autre</option>
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <label for="comment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Commentaire</label>
                                <textarea name="comment" id="comment" rows="3"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $payment->comment }}</textarea>
                            </div>
                        </div>

                        <div class="mt-6 flex gap-2">
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                Enregistrer les modifications
                            </button>
                            <a href="{{ route('payments.show', $payment) }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                Annuler
                            </a>
                        </div>
                    </form>

                    <!-- Informations de l'élève (lecture seule) -->
                    <div class="mt-8 bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informations de l'élève</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">Nom:</span>
                                <span class="font-medium text-gray-900 dark:text-white ml-2">{{ $payment->registration->user->name }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">Classe:</span>
                                <span class="font-medium text-gray-900 dark:text-white ml-2">{{ $payment->registration->classroom?->name ?? 'Non assigné' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">Mensualité attendue:</span>
                                <span class="font-medium text-gray-900 dark:text-white ml-2">{{ number_format($payment->registration->monthly_fee, 0, ',', ' ') }} FCFA</span>
                            </div>
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">Numéro de reçu:</span>
                                <span class="font-medium text-gray-900 dark:text-white ml-2">{{ $payment->receipt_number }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
