<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Rapports Financiers Avancés
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="space-y-6">
                <!-- Formulaire de filtres -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <form action="{{ route('accounting.advanced-reports') }}" method="GET">
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date début</label>
                                <input type="date" name="start_date" id="start_date" value="{{ $startDate }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date fin</label>
                                <input type="date" name="end_date" id="end_date" value="{{ $endDate }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label for="classroom_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Classe</label>
                                <select name="classroom_id" id="classroom_id" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Toutes</option>
                                    @foreach($classrooms as $classroom)
                                        <option value="{{ $classroom->id }}" {{ $classroomId == $classroom->id ? 'selected' : '' }}>{{ $classroom->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="fee_type_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type de frais</label>
                                <select name="fee_type_id" id="fee_type_id" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Tous</option>
                                    @foreach($feeTypes as $feeType)
                                        <option value="{{ $feeType->id }}" {{ $feeTypeId == $feeType->id ? 'selected' : '' }}>{{ $feeType->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                    Générer le rapport
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Statistiques du rapport -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Revenu total</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalRevenue, 0, ',', ' ') }} FCFA</p>
                            </div>
                            <div class="bg-green-100 dark:bg-green-900/30 p-3 rounded-full">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Paiements partiels</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($partialRevenue, 0, ',', ' ') }} FCFA</p>
                            </div>
                            <div class="bg-yellow-100 dark:bg-yellow-900/30 p-3 rounded-full">
                                <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Nombre de paiements</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalPayments }}</p>
                            </div>
                            <div class="bg-blue-100 dark:bg-blue-900/30 p-3 rounded-full">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Moyenne par paiement</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($averagePayment, 0, ',', ' ') }} FCFA</p>
                            </div>
                            <div class="bg-purple-100 dark:bg-purple-900/30 p-3 rounded-full">
                                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Répartition par méthode de paiement -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Par méthode de paiement</h3>
                        <div class="space-y-3">
                            @forelse($paymentMethods as $method => $data)
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-700 dark:text-gray-300">{{ ucfirst($method) }}</span>
                                    <div class="text-right">
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $data['count'] }}</span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">{{ number_format($data['total'], 0, ',', ' ') }} FCFA</span>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400">Aucun paiement sur cette période.</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Répartition par classe (fusionnée depuis l'ancienne page "Rapports financiers") -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Par classe</h3>
                        <div class="space-y-3">
                            @forelse($classroomBreakdown as $classroom => $data)
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-700 dark:text-gray-300">{{ $classroom }}</span>
                                    <div class="text-right">
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $data['count'] }}</span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">{{ number_format($data['total'], 0, ',', ' ') }} FCFA</span>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400">Aucun paiement sur cette période.</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Répartition par type de paiement (fusionnée depuis l'ancienne page "Rapports financiers") -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Par type de paiement</h3>
                        <div class="space-y-3">
                            @forelse($paymentTypeBreakdown as $type => $data)
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-700 dark:text-gray-300">{{ ucfirst($type) }}</span>
                                    <div class="text-right">
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $data['count'] }}</span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">{{ number_format($data['total'], 0, ',', ' ') }} FCFA</span>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400">Aucun paiement sur cette période.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Export Excel -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <form action="{{ route('accounting.export-advanced-reports') }}" method="GET" data-no-pjax class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Exporter le rapport</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Export Excel des paiements de la période et des filtres sélectionnés ci-dessus.</p>
                        </div>
                        <input type="hidden" name="start_date" value="{{ $startDate }}">
                        <input type="hidden" name="end_date" value="{{ $endDate }}">
                        <input type="hidden" name="classroom_id" value="{{ $classroomId }}">
                        <input type="hidden" name="fee_type_id" value="{{ $feeTypeId }}">
                        <button type="submit" class="shrink-0 px-4 py-3 bg-green-600 text-white rounded hover:bg-green-700 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Télécharger Excel
                        </button>
                    </form>
                </div>

                <!-- Liste des paiements -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Détail des paiements</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Reçu</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Élève</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Classe</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Montant</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Statut</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Méthode</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($payments as $payment)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $payment->payment_date->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $payment->receipt_number }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $payment->registration->user->name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $payment->registration->classroom?->name ?? 'Non assigné' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                                        <td class="px-4 py-3">
                                            @if($payment->status === 'complet')
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Complet</span>
                                            @else
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">Partiel</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ ucfirst($payment->payment_method) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">
                                            Aucun paiement trouvé pour cette période
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
