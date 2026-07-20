<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Rapports Financiers
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="space-y-6">
                <!-- Rapport journalier du mois -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Rapport journalier - {{ now()->format('F Y') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Nombre de paiements</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Montant total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($dailyReport as $day)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $day['date'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $day['count'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ number_format($day['amount'], 0, ',', ' ') }} FCFA</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-white">Total</td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-white">{{ collect($dailyReport)->sum('count') }}</td>
                                    <td class="px-4 py-3 text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ number_format(collect($dailyReport)->sum('amount'), 0, ',', ' ') }} FCFA</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Rapport par classe -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Rapport par classe ({{ now()->year }})</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Classe</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Paiements</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($classReport as $className => $data)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $className }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $data['count'] }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ number_format($data['total'], 0, ',', ' ') }} FCFA</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Rapport par type de paiement -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Rapport par type de paiement ({{ now()->year }})</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Nombre</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($paymentTypeReport as $type)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ ucfirst($type->payment_type) }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $type->count }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ number_format($type->total, 0, ',', ' ') }} FCFA</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
