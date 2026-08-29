<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Gestion des Factures
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Formulaire de recherche et filtres -->
                    <form action="{{ route('invoices.index') }}" method="GET" class="mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Recherche</label>
                                <input type="text" name="search" id="search" value="{{ request('search') }}" 
                                    placeholder="Nom de l'élève..." 
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Statut</label>
                                <select name="status" id="status" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Tous</option>
                                    @foreach(\App\Models\Invoice::LABELS as $value => $label)
                                        <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="year" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Année</label>
                                <select name="year" id="year" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Toutes</option>
                                    @for($y = now()->year; $y >= now()->year - 2; $y--)
                                        <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                Filtrer
                            </button>
                            <a href="{{ route('invoices.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                Réinitialiser
                            </a>
                            <a href="{{ route('invoices.create') }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                                + Nouvelle Facture
                            </a>
                        </div>
                    </form>

                    <!-- Tableau des factures -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">N° Facture</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Élève</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Montant</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reste</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Statut</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Échéance</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($invoices as $invoice)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $invoice->invoice_number }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ $invoice->registration->user->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ number_format($invoice->total_amount, 0, ',', ' ') }} FCFA
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ number_format($invoice->remaining_balance, 0, ',', ' ') }} FCFA
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ \App\Models\Invoice::BADGE_COLORS[$invoice->status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400' }}">
                                                {{ \App\Models\Invoice::LABELS[$invoice->status] ?? $invoice->status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ $invoice->due_date->format('d/m/Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('invoices.show', $invoice) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-2">Voir</a>
                                            <a href="{{ route('invoices.pdf', $invoice) }}" class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300 mr-2">PDF</a>
                                            <a href="{{ route('invoices.edit', $invoice) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 mr-2">Modifier</a>
                                            <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" class="inline" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Supprimer la facture', message: 'La facture {{ addslashes($invoice->invoice_number) }} sera supprimée. Vérifiez qu’aucun paiement ne lui est associé.', confirmLabel: 'Supprimer' })">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                            Aucune facture trouvée
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($invoices->hasPages())
                        <div class="mt-6">
                            {{ $invoices->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
