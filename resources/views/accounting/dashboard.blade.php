<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Dashboard Comptable
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="space-y-6">
                <!-- Statistiques principales -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6 ring-1 ring-gray-200 dark:ring-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Revenu total</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_revenue'], 0, ',', ' ') }} FCFA</p>
                            </div>
                            <div class="bg-green-100 dark:bg-green-900/30 p-3 rounded-full">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6 ring-1 ring-gray-200 dark:ring-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Revenu mensuel</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['monthly_revenue'], 0, ',', ' ') }} FCFA</p>
                            </div>
                            <div class="bg-blue-100 dark:bg-blue-900/30 p-3 rounded-full">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6 ring-1 ring-gray-200 dark:ring-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Paiements complets</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['complete_payments'] }}</p>
                            </div>
                            <div class="bg-green-100 dark:bg-green-900/30 p-3 rounded-full">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6 ring-1 ring-gray-200 dark:ring-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Paiements partiels</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['partial_payments'] }}</p>
                            </div>
                            <div class="bg-yellow-100 dark:bg-yellow-900/30 p-3 rounded-full">
                                <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenus mensuels -->
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6 ring-1 ring-gray-200 dark:ring-slate-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Revenus mensuels ({{ now()->year }})</h3>
                    <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        @foreach($monthlyRevenue as $revenue)
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 text-center">
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">{{ substr($revenue['month'], 0, 3) }}</p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($revenue['amount'], 0, ',', ' ') }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Paiements récents -->
                    <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6 ring-1 ring-gray-200 dark:ring-slate-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Paiements récents</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Élève</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Montant</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Statut</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($recentPayments as $payment)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                {{ $payment->registration->user->name }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                {{ number_format($payment->amount, 0, ',', ' ') }} FCFA
                                            </td>
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

                    <!-- Paiements partiels en attente -->
                    <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6 ring-1 ring-gray-200 dark:ring-slate-700">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Paiements partiels en attente</h3>
                            @can('valider-paiement-partiel')
                                <a href="{{ route('payments.validation') }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">
                                    Valider →
                                </a>
                            @endcan
                        </div>
                        @if($partialPayments->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Élève</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Reste</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Mois</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($partialPayments as $payment)
                                            <tr>
                                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                    {{ $payment->registration->user->name }}
                                                </td>
                                                <td class="px-4 py-3 text-sm text-red-600 dark:text-red-400 font-semibold">
                                                    {{ number_format($payment->remaining_balance, 0, ',', ' ') }} FCFA
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                    {{ $payment->month }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-gray-500 dark:text-gray-400 text-center py-4">Aucun paiement partiel en attente</p>
                        @endif
                    </div>
                </div>

                <!-- Élèves avec impayés -->
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6 ring-1 ring-gray-200 dark:ring-slate-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Élèves avec impayés</h3>
                    @if($studentsWithDebt->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Élève</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Classe</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Payé</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total dû</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Reste</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($studentsWithDebt as $student)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                {{ $student['student']->name }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                {{ $student['classroom']?->name ?? 'Non assigné' }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                {{ number_format($student['total_paid'], 0, ',', ' ') }} FCFA
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                {{ number_format($student['total_due'], 0, ',', ' ') }} FCFA
                                            </td>
                                            <td class="px-4 py-3 text-sm text-red-600 dark:text-red-400 font-semibold">
                                                {{ number_format($student['remaining'], 0, ',', ' ') }} FCFA
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">Aucun élève avec impayés</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
