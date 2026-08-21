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

                @if($registration->classroom->cycle === 'primaire')
                    {{-- Primaire : vraie grille horaire (App\Models\TimetableEntry), pas la liste
                         matières/heures ci-dessous (réservée au secondaire, où plusieurs
                         professeurs de matière interviennent). Lecture seule ici : la
                         modification se fait depuis timetable.edit (prof principal/surveillant/admin). --}}
                    <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between">
                            <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200">📅 {{ __('Emploi du temps de la semaine') }}</h4>
                            <a href="{{ route('timetable.print', $registration->classroom) }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">{{ __('Imprimer / PDF') }}</a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full border-collapse text-sm">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-slate-700">
                                        <th class="border border-gray-200 dark:border-slate-600 px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">{{ __('Horaire') }}</th>
                                        @foreach(\App\Support\TimetableGrid::DAYS as $day)
                                            <th class="border border-gray-200 dark:border-slate-600 px-3 py-3 text-center font-semibold text-gray-600 dark:text-gray-300">{{ $day }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-slate-800">
                                    @foreach(\App\Support\TimetableGrid::SLOTS as $slot)
                                        <tr>
                                            <th class="border border-gray-200 dark:border-slate-600 px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200 whitespace-nowrap bg-gray-50 dark:bg-slate-700">{{ $slot }}</th>
                                            @foreach(\App\Support\TimetableGrid::DAYS as $day)
                                                <td class="border border-gray-200 dark:border-slate-600 px-3 py-2 text-center text-gray-700 dark:text-gray-300">{{ $timetableEntries[$day][$slot] ?: '—' }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
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
                                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $teacher->user?->name ?? '—' }}</td>
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
                @endif
            @else
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-900 rounded-lg p-6 text-amber-800 dark:text-amber-200">
                    {{ __('Vous n\'êtes inscrit dans aucune classe pour le moment.') }}
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
