<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Gestion des Années Scolaires
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">

                <div class="mb-6 bg-gray-50 dark:bg-slate-700/50 p-4 rounded-lg">
                    <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-3">Créer une nouvelle année scolaire</h3>
                    <form action="{{ route('school-years.store') }}" method="POST" class="flex flex-wrap gap-3 items-end">
                        @csrf
                        <div class="flex-1 min-w-[200px]">
                            <label for="year_string" class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Année (ex: 2026-2027)</label>
                            <input type="text" name="year_string" id="year_string" placeholder="2026-2027" class="w-full border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 p-2 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        <div class="flex-1 min-w-[150px]">
                            <label for="start_date" class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Date de début</label>
                            <input type="date" name="start_date" id="start_date" class="w-full border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 p-2 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="flex-1 min-w-[150px]">
                            <label for="end_date" class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Date de fin</label>
                            <input type="date" name="end_date" id="end_date" class="w-full border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 p-2 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="flex items-center">
                            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <input type="checkbox" name="is_active" class="rounded text-blue-500">
                                Activer immédiatement
                            </label>
                        </div>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded transition">Créer</button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Année</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Période</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Jours restants</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                            @foreach($schoolYears as $year)
                            <tr class="{{ $year->is_active ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $year->year_string }}</span>
                                    @if($year->is_active)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300">
                                            En cours
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    @if($year->start_date && $year->end_date)
                                        {{ $year->start_date->format('d/m/Y') }} - {{ $year->end_date->format('d/m/Y') }}
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">Non définie</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @switch($year->status)
                                        @case('active')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300">Active</span>
                                            @break
                                        @case('upcoming')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300">À venir</span>
                                            @break
                                        @case('completed')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 dark:bg-slate-700 text-gray-800 dark:text-gray-300">Terminée</span>
                                            @break
                                        @default
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 dark:bg-slate-700 text-gray-800 dark:text-gray-300">{{ $year->status }}</span>
                                    @endswitch
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    @if($year->is_active && $year->remaining_days > 0)
                                        <span class="text-blue-600 dark:text-blue-400 font-medium">{{ $year->remaining_days }} jours</span>
                                    @elseif($year->is_active && $year->remaining_days <= 0)
                                        <span class="text-red-600 dark:text-red-400 font-medium">Expirée</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if(!$year->is_active)
                                        <form action="{{ route('school-years.activate', $year->id) }}" method="POST" class="inline" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Activer l’année scolaire', message: 'L’année {{ addslashes($year->year_string) }} deviendra le contexte actif de l’établissement. Vérifiez que les données de l’année précédente sont prêtes.', confirmLabel: 'Activer l’année' })">
                                            @csrf
                                            <button type="submit" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 font-medium mr-3">Activer</button>
                                        </form>
                                    @endif

                                    @if(!$year->is_active)
                                        <form action="{{ route('school-years.destroy', $year->id) }}" method="POST" class="inline" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Supprimer l’année scolaire', message: 'Cette opération peut supprimer les inscriptions et données associées à cette année. Vérifiez qu’elle n’est pas utilisée avant de confirmer.', confirmLabel: 'Supprimer définitivement' })">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 font-medium">Supprimer</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($schoolYears->isEmpty())
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <p>Aucune année scolaire créée.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
