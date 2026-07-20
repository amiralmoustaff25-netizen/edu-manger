<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Tableau de Bord de Trésorerie
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="space-y-6">
                <!-- Statistiques mensuelles -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Entrées du mois</h3>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Ce mois</p>
                                <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format($monthlyInflow, 0, ',', ' ') }} FCFA</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-600 dark:text-gray-400">Mois précédent</p>
                                <p class="text-xl font-medium text-gray-900 dark:text-white">{{ number_format($previousMonthInflow, 0, ',', ' ') }} FCFA</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            @if($previousMonthInflow > 0)
                                @php
                                    $evolution = (($monthlyInflow - $previousMonthInflow) / $previousMonthInflow) * 100;
                                @endphp
                                <span class="text-sm @if($evolution >= 0) text-green-600 dark:text-green-400 @else text-red-600 dark:text-red-400 @endif">
                                    @if($evolution >= 0) + @endif {{ number_format($evolution, 1) }}% vs mois précédent
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Moyenne journalière</h3>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Ce mois</p>
                                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($monthlyInflow / now()->daysInMonth, 0, ',', ' ') }} FCFA</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-600 dark:text-gray-400">Mois précédent</p>
                                <p class="text-xl font-medium text-gray-900 dark:text-white">{{ number_format($previousMonthInflow / now()->subMonth()->daysInMonth, 0, ',', ' ') }} FCFA</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Évolution mensuelle -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Évolution mensuelle (6 derniers mois)</h3>
                    <div class="space-y-3">
                        @foreach($monthlyEvolution as $month)
                            <div class="flex items-center">
                                <div class="w-24 text-sm text-gray-600 dark:text-gray-400">{{ $month['month'] }}</div>
                                <div class="flex-1 mx-4 bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                                    @php
                                        $maxAmount = collect($monthlyEvolution)->max('amount');
                                        $percentage = $maxAmount > 0 ? ($month['amount'] / $maxAmount) * 100 : 0;
                                    @endphp
                                    <div class="bg-indigo-600 dark:bg-indigo-400 h-4 rounded-full" style="width: {{ $percentage }}%"></div>
                                </div>
                                <div class="w-32 text-right text-sm font-medium text-gray-900 dark:text-white">
                                    {{ number_format($month['amount'], 0, ',', ' ') }} FCFA
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Flux journalier du mois -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Flux journalier - {{ now()->format('F Y') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($dailyCashFlow as $day)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded">
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ $day['date'] }}</div>
                                <div class="text-sm font-medium @if($day['amount'] > 0) text-green-600 dark:text-green-400 @else text-gray-400 @endif">
                                    {{ $day['amount'] > 0 ? number_format($day['amount'], 0, ',', ' ') . ' FCFA' : '-' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Résumé -->
                <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Résumé de la période</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="text-center">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Total du mois</p>
                            <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($monthlyInflow, 0, ',', ' ') }} FCFA</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Nombre de jours avec entrées</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ collect($dailyCashFlow)->where('amount', '>', 0)->count() }} / {{ now()->daysInMonth }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Moyenne par jour actif</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ collect($dailyCashFlow)->where('amount', '>', 0)->count() > 0 
                                    ? number_format($monthlyInflow / collect($dailyCashFlow)->where('amount', '>', 0)->count(), 0, ',', ' ') 
                                    : '0' }} FCFA
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
