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
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Classe') }}</label>
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
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Matière') }}</label>
                            <select name="matiere_id" id="matiereSelect" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('Sélectionner une matière') }}</option>
                                @if(request('classroom_id'))
                                    @foreach($matieres as $matiere)
                                        <option value="{{ $matiere->id }}" data-coefficient="{{ $matiere->coefficient }}" {{ request('matiere_id') == $matiere->id ? 'selected' : '' }}>
                                            {{ $matiere->nom }} (Coef: {{ $matiere->coefficient }})
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
                @endphp
                
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                {{ __('Grille de saisie') }} - {{ $selectedClassroom->name }} | {{ $selectedMatiere->nom }} (Coef: {{ $selectedMatiere->coefficient }})
                            </h4>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $students->count() }} élève(s)</span>
                        </div>
                    </div>
                    
                    <form action="{{ route('professeur.notes.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="classroom_id" value="{{ request('classroom_id') }}">
                        <input type="hidden" name="matiere_id" value="{{ request('matiere_id') }}">
                        
                        <div class="p-6">
                            <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Type d\'évaluation') }}</label>
                                    <select name="type_evaluation" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        @if(in_array($selectedClassroom->cycle, ['college', 'lycee']))
                                            <option value="devoir">{{ __('Devoir') }}</option>
                                            <option value="composition">{{ __('Composition') }}</option>
                                        @else
                                            <option value="composition">{{ __('Composition') }}</option>
                                        @endif
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Période') }}</label>
                                    <select name="periode" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="trimestre_1">{{ __('Trimestre 1') }}</option>
                                        <option value="trimestre_2">{{ __('Trimestre 2') }}</option>
                                        <option value="trimestre_3">{{ __('Trimestre 3') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                                    <thead class="bg-gray-50 dark:bg-slate-700/50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('N°') }}</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Élève') }}</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Matricule') }}</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Note (/20)') }}</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Appréciation') }}</th>
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
                                                <input type="number" name="grades[{{ $index }}][valeur]" min="0" max="20" step="0.5" class="w-24 rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="0-20">
                                                <input type="hidden" name="grades[{{ $index }}][user_id]" value="{{ $student->id }}">
                                                <input type="hidden" name="grades[{{ $index }}][matiere_id]" value="{{ request('matiere_id') }}">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="text" name="grades[{{ $index }}][appreciation]" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="{{ __('Appréciation optionnelle') }}">
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-6 flex justify-between items-center">
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('Les notes non saisies seront ignorées') }}
                                </div>
                                <button type="submit" class="flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors font-medium">
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
