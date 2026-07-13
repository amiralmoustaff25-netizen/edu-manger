<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Bulletin Scolaire - {{ $bulletin['student']->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="mb-6 flex justify-between items-center">
                <div class="flex gap-2">
                    <a href="{{ route('bulletins.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Retour
                    </a>
                </div>
                <div class="flex gap-2">
                    <form action="{{ route('bulletins.pdf', [$bulletin['student'], $period]) }}" method="GET" class="inline">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Télécharger PDF
                        </button>
                    </form>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm overflow-hidden">
                <div class="p-8">
                    
                    <!-- En-tête -->
                    <div class="text-center mb-8 border-b-2 border-gray-300 dark:border-slate-600 pb-4">
                        <h1 class="text-2xl font-bold uppercase text-gray-900 dark:text-white">École - Bulletin Scolaire</h1>
                        <h2 class="text-lg text-gray-600 dark:text-gray-300 mt-2">
                            Année Scolaire {{ $bulletin['classroom']->schoolYear->year_string ?? 'N/A' }}
                        </h2>
                    </div>

                    <!-- Informations élève -->
                    <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-6 mb-8">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <span class="block text-sm text-gray-500 dark:text-gray-400">Nom & Prénom</span>
                                <span class="block font-semibold text-gray-900 dark:text-white">{{ $bulletin['student']->name }}</span>
                            </div>
                            <div>
                                <span class="block text-sm text-gray-500 dark:text-gray-400">Matricule</span>
                                <span class="block font-semibold text-gray-900 dark:text-white">{{ $bulletin['student']->matricule }}</span>
                            </div>
                            <div>
                                <span class="block text-sm text-gray-500 dark:text-gray-400">Classe</span>
                                <span class="block font-semibold text-gray-900 dark:text-white">{{ $bulletin['classroom']->name }}</span>
                            </div>
                            <div>
                                <span class="block text-sm text-gray-500 dark:text-gray-400">Période</span>
                                <span class="block font-semibold text-gray-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $period)) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tableau des notes -->
                    <div class="overflow-x-auto mb-8">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                            <thead class="bg-gray-800 dark:bg-slate-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Matière</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">Coef</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">Notes</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">Moyenne</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">Moy. × Coef</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">Appréciation</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                                @foreach($bulletin['subjects'] as $subject)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $subject['matiere'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">{{ $subject['coefficient'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">{{ implode(', ', $subject['notes']) ?: '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white text-center">{{ $subject['average'] > 0 ? number_format($subject['average'], 2) : '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">{{ $subject['weighted_average'] > 0 ? number_format($subject['weighted_average'], 2) : '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">{{ $subject['appreciation'] ?: '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-100 dark:bg-slate-700">
                                <tr>
                                    <td colspan="2" class="px-6 py-3 text-right text-sm font-bold text-gray-900 dark:text-white">Total Coefficients :</td>
                                    <td colspan="4" class="px-6 py-3 text-sm font-bold text-gray-900 dark:text-white">{{ $bulletin['total_coefficients'] }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Résumé -->
                    <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-6">
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <span class="block text-sm text-gray-500 dark:text-gray-400">Moyenne Générale</span>
                                <span class="block text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($bulletin['general_average'], 2) }}/20</span>
                            </div>
                            <div>
                                <span class="block text-sm text-gray-500 dark:text-gray-400">Classement</span>
                                <span class="block text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ $bulletin['rank'] }}<sup>ème</sup></span>
                            </div>
                        </div>
                        
                        @php
                            $mentionClass = match($bulletin['mention']) {
                                'Excellent' => 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300',
                                'Très Bien' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300',
                                'Bien' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300',
                                'Assez Bien' => 'bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-300',
                                'Passable' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300',
                                default => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
                            };
                        @endphp
                        
                        <div class="mt-6 text-center">
                            <span class="inline-block px-6 py-3 rounded-lg text-xl font-bold {{ $mentionClass }}">
                                {{ $bulletin['mention'] }}
                            </span>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
