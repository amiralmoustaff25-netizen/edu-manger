<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Saisie des Notes') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Sélection de la classe -->
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 dark:bg-green-900/50 text-green-600 dark:text-green-400 mr-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200">{{ __('Saisie des Notes') }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Sélectionnez une classe pour saisir les notes') }}</p>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('professeur.notes.index') }}" method="GET" class="flex items-center space-x-4">
                        <div class="flex-1">
                            <label for="classroomSelect" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Classe') }}</label>
                            <select name="classroom_id" id="classroomSelect" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('Sélectionner une classe') }}</option>
                                @foreach($classrooms as $classroom)
                                    <option value="{{ $classroom->id }}" {{ request('classroom_id') == $classroom->id ? 'selected' : '' }}>
                                        {{ $classroom->name }} ({{ $classroom->schoolYear->year_string ?? '' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1">
                            <label for="matiereSelect" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Matière') }}</label>
                            <select name="matiere_id" id="matiereSelect" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('Sélectionner une matière') }}</option>
                                @if(request('classroom_id'))
                                    @php
                                        $dropdownClassroom = \App\Models\Classroom::find(request('classroom_id'));
                                        $dropdownGradeService = app(\App\Services\GradeCalculationService::class);
                                        $dropdownUsesBareme = $dropdownClassroom && $dropdownGradeService->usesBaremeSystem($dropdownClassroom, $dropdownClassroom->school_year_id);
                                    @endphp
                                    @foreach($matieres as $matiere)
                                        <option value="{{ $matiere->id }}" data-coefficient="{{ $matiere->coefficient }}" {{ request('matiere_id') == $matiere->id ? 'selected' : '' }}>
                                            {{ $matiere->nom }}
                                            @if($dropdownUsesBareme)
                                                (Barème: {{ $dropdownGradeService->resolveBareme($matiere, $dropdownClassroom, $dropdownClassroom->school_year_id) }})
                                            @else
                                                (Coef: {{ $matiere->coefficient }})
                                            @endif
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="pt-6">
                            <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors font-medium">
                                {{ __('Charger') }}
                            </button>
                        </div>
                    </form>

                    {{--
                        Recherche par matricule : même portée de données (les matières que ce
                        professeur enseigne, dans ses classes assignées), juste une autre façon
                        d'y entrer — un élève, toutes ses matières, plutôt qu'une classe, une
                        matière, tous ses élèves. Repliable pour ne pas alourdir l'écran par
                        défaut.
                    --}}
                    <details class="mt-4 border-t border-gray-200 pt-4 dark:border-slate-700">
                        <summary class="cursor-pointer text-sm font-medium text-indigo-600 dark:text-indigo-400">
                            {{ __('Ou rechercher un seul élève par matricule (toutes ses matières)') }}
                        </summary>
                        <form action="{{ route('professeur.notes.eleve') }}" method="GET" class="mt-3 flex flex-wrap items-end gap-4">
                            <div class="flex-1 min-w-[200px]">
                                <label for="matricule" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Matricule de l\'élève') }}</label>
                                <input type="text" name="matricule" id="matricule" placeholder="Ex. EL-26-0001" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <button type="submit" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-slate-600 dark:hover:bg-slate-500 text-gray-800 dark:text-gray-100 rounded-lg font-medium">
                                {{ __('Rechercher') }}
                            </button>
                        </form>
                    </details>
                </div>
            </div>

            <!-- Grille de saisie des notes -->
            @if(request('classroom_id') && request('matiere_id'))
                @php
                    $selectedClassroom = \App\Models\Classroom::find(request('classroom_id'));
                    $selectedMatiere = \App\Models\Matiere::find(request('matiere_id'));
                    $students = \App\Models\User::role('eleve')
                        ->whereHas('registrations', function($query) use ($selectedClassroom) {
                            $query->where('classroom_id', $selectedClassroom->id)->where('status', 'active');
                        })
                        ->with('latestRegistration')
                        ->get();
                    // Barème par matière (système "sunuBulletin" du primaire, ex. Mathématiques
                    // /80) : la note max de cette grille n'est pas toujours /20, voir
                    // GradeCalculationService::resolveBareme().
                    $gradeCalculationService = app(\App\Services\GradeCalculationService::class);
                    $usesBaremeSystem = $gradeCalculationService->usesBaremeSystem($selectedClassroom, $selectedClassroom->school_year_id);
                    $maxValeur = $usesBaremeSystem
                        ? $gradeCalculationService->resolveBareme($selectedMatiere, $selectedClassroom, $selectedClassroom->school_year_id)
                        : 20;
                @endphp
                
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                {{ __('Grille de saisie') }} - {{ $selectedClassroom->name }} | {{ $selectedMatiere->nom }} ({{ $usesBaremeSystem ? 'Barème: '.$maxValeur : 'Coef: '.$selectedMatiere->coefficient }})
                            </h4>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $students->count() }} élève(s)</span>
                        </div>
                    </div>
                    
                    @php
                        $isSecondaryCycle = in_array($selectedClassroom->cycle, ['college', 'lycee'], true);
                        $maxDevoirs = (int) (config('edu.max_evaluations_per_period.devoir') ?? 1);
                    @endphp
                    <form action="{{ route('professeur.notes.store') }}" method="POST" x-data="{ periode: '{{ \App\Support\AcademicPeriods::defaultFor($selectedClassroom->cycle) }}', typeEvaluation: '{{ \App\Support\EvaluationTypeScope::allowedFor($selectedClassroom->cycle)[0] }}' }">
                        @csrf
                        <input type="hidden" name="classroom_id" value="{{ request('classroom_id') }}">
                        <input type="hidden" name="matiere_id" value="{{ request('matiere_id') }}">

                        <div class="p-6">
                            <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="typeEvaluationSelect" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Type d\'évaluation') }}</label>
                                    <select name="type_evaluation" id="typeEvaluationSelect" x-model="typeEvaluation" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        @foreach(\App\Support\EvaluationTypeScope::allowedFor($selectedClassroom->cycle) as $type)
                                            <option value="{{ $type }}">{{ __(\App\Support\EvaluationTypeScope::LABELS[$type]) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="periodeSelect" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Période') }}</label>
                                    <select name="periode" id="periodeSelect" x-model="periode" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        @foreach(\App\Support\AcademicPeriods::forCycle($selectedClassroom->cycle) as $code => $label)
                                            <option value="{{ $code }}">{{ __($label) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @if($isSecondaryCycle && $maxDevoirs > 1)
                                    <div x-show="typeEvaluation === 'devoir'" x-cloak>
                                        <label for="evaluationNumberSelect" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('N° du devoir') }}</label>
                                        <select name="evaluation_number" id="evaluationNumberSelect" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            @for($n = 1; $n <= $maxDevoirs; $n++)
                                                <option value="{{ $n }}">{{ __('Devoir :n', ['n' => $n]) }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                @endif
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                                    <thead class="bg-gray-50 dark:bg-slate-700/50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('N°') }}</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Élève') }}</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Matricule') }}</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $usesBaremeSystem ? __('Note (/'.$maxValeur.')') : __('Note (/20)') }}</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Appréciation') }}</th>
                                            @can('generer-bulletins')
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Bulletin') }}</th>
                                            @endcan
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                                        @foreach($students as $index => $student)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $index + 1 }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-200">
                                                {{ $student->name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $student->matricule }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="number" name="grades[{{ $index }}][valeur]" min="0" max="{{ $maxValeur }}" step="0.5" class="w-24 rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="0-{{ $maxValeur }}">
                                                <input type="hidden" name="grades[{{ $index }}][user_id]" value="{{ $student->id }}">
                                                <input type="hidden" name="grades[{{ $index }}][matiere_id]" value="{{ request('matiere_id') }}">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="text" name="grades[{{ $index }}][appreciation]" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="{{ __('Appréciation optionnelle') }}">
                                            </td>
                                            @can('generer-bulletins')
                                                {{--
                                                    Aperçu du bulletin réel (bulletins.show), recalculé en direct à
                                                    partir des notes déjà enregistrées (pas la saisie en cours ci-dessus,
                                                    non sauvegardée) — suit la période choisie au-dessus sans recharger
                                                    la page.
                                                --}}
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    <a target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline"
                                                       :href="{{ Illuminate\Support\Js::from(
                                                           collect(\App\Support\AcademicPeriods::forCycle($selectedClassroom->cycle))->keys()->mapWithKeys(fn ($code) => [$code => route('bulletins.show', [$student, $code])])->all()
                                                       ) }}[periode]">{{ __('Aperçu') }} ↗</a>
                                                </td>
                                            @endcan
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-6 flex justify-between items-center">
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('Les notes non saisies seront ignorées') }}
                                </div>
                                <button type="submit" class="flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors font-medium shadow-sm">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    {{ __('Enregistrer les résultats') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
