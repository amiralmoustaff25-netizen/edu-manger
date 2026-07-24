<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Mon Emploi du temps') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if($registration && $registration->classroom)
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-2">
                        {{ $registration->classroom->name }} — {{ $registration->schoolYear->year_string ?? $registration->classroom->schoolYear?->year_string ?? '' }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Horaire hebdomadaire de la classe') }}</p>
                </div>

                @php
                    $teachers = $registration->classroom->teachers->filter(fn ($teacher) => $teacher->pivot->annee_scolaire === ($registration->schoolYear->year_string ?? $registration->classroom->schoolYear?->year_string ?? ''));
                @endphp

                @if($teachers->count() > 0)
                    <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 border-b border-gray-100 dark:border-slate-700">
                            <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">📅 {{ __('Matières & horaires') }}</h4>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                                    <thead class="bg-gray-50 dark:bg-slate-700">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">{{ __('Matière') }}</th>
                                            <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">{{ __('Professeur') }}</th>
                                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">{{ __('Heures / semaine') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                                        @foreach($teachers as $teacher)
                                            <tr>
                                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ optional($matieres->get($teacher->pivot->matiere_id))->nom ?? '—' }}</td>
                                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $teacher->user->name ?? '—' }}</td>
                                                <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-gray-100">{{ $teacher->pivot->volume_horaire_hebdo }}h</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-900 rounded-lg p-6 text-amber-800 dark:text-amber-200">
                        {{ __('Aucun enseignant associé à cette classe pour l\'année en cours.') }}
                    </div>
                @endif
            @else
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-900 rounded-lg p-6 text-amber-800 dark:text-amber-200">
                    {{ __('Vous n\'êtes inscrit dans aucune classe pour le moment.') }}
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
