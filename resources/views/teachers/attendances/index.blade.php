<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Gestion des Présences') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Filtres -->
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 mr-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200">{{ __('Gestion des Présences') }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Sélectionnez une classe et une date pour enregistrer les présences') }}</p>
                            </div>
                        </div>
                        <a href="{{ route('professeur.attendances.history') }}" class="rounded-md border border-indigo-600 px-4 py-2 text-sm font-semibold text-indigo-600 hover:bg-indigo-50 dark:border-indigo-400 dark:text-indigo-400 dark:hover:bg-slate-700">Voir l'historique</a>
                    </div>

                    <form action="{{ route('professeur.attendances.index') }}" method="GET" class="flex items-center space-x-4">
                        <div class="flex-1">
                            <label for="attendance-classroom" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Classe') }}</label>
                            <select name="classroom_id" id="attendance-classroom" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('Sélectionner une classe') }}</option>
                                @foreach($classrooms as $classroom)
                                    <option value="{{ $classroom->id }}" {{ request('classroom_id') == $classroom->id ? 'selected' : '' }}>
                                        {{ $classroom->name }} ({{ $classroom->schoolYear->name ?? '' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1">
                            <label for="attendance-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Date') }}</label>
                            <input type="date" name="date" id="attendance-date" value="{{ $selectedDate }}" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div class="pt-6">
                            <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors font-medium">
                                {{ __('Charger') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Grille de saisie des présences -->
            @if($selectedClassroom && $students->count() > 0)
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                {{ __('Appel du :date', ['date' => \Carbon\Carbon::parse($selectedDate)->format('d/m/Y')]) }}
                            </h4>
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $students->count() }} {{ __('élèves') }}</span>
                        </div>
                    </div>
                    
                    <form action="{{ route('professeur.attendances.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="classroom_id" value="{{ $selectedClassroom }}">
                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                        
                        <div class="p-6">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                                    <thead class="bg-gray-50 dark:bg-slate-700/50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Élève') }}</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                <span class="flex items-center justify-center">
                                                    <svg class="w-4 h-4 mr-1 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                    {{ __('Présent') }}
                                                </span>
                                            </th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                <span class="flex items-center justify-center">
                                                    <svg class="w-4 h-4 mr-1 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                    {{ __('Absent') }}
                                                </span>
                                            </th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                <span class="flex items-center justify-center">
                                                    <svg class="w-4 h-4 mr-1 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    {{ __('Retard') }}
                                                </span>
                                            </th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                <span class="flex items-center justify-center">
                                                    <svg class="w-4 h-4 mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                    {{ __('Excusé') }}
                                                </span>
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Notes') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                                        @foreach($students as $student)
                                            <?php
                                                $attendance = $attendances->get($student->id);
                                                $status = $attendance ? $attendance->status : 'present';
                                                $notes = $attendance ? $attendance->notes : '';
                                            ?>
                                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-medium">
                                                            {{ substr($student->name, 0, 1) }}
                                                        </div>
                                                        <div class="ml-4">
                                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-200">{{ $student->name }}</div>
                                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $student->matricule }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                                    <label class="inline-flex items-center">
                                                        <input type="radio" name="attendances[{{ $student->id }}][status]" value="present" {{ $status === 'present' ? 'checked' : '' }} class="form-radio text-green-600 focus:ring-green-500">
                                                    </label>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                                    <label class="inline-flex items-center">
                                                        <input type="radio" name="attendances[{{ $student->id }}][status]" value="absent" {{ $status === 'absent' ? 'checked' : '' }} class="form-radio text-red-600 focus:ring-red-500">
                                                    </label>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                                    <label class="inline-flex items-center">
                                                        <input type="radio" name="attendances[{{ $student->id }}][status]" value="late" {{ $status === 'late' ? 'checked' : '' }} class="form-radio text-yellow-600 focus:ring-yellow-500">
                                                    </label>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                                    <label class="inline-flex items-center">
                                                        <input type="radio" name="attendances[{{ $student->id }}][status]" value="excused" {{ $status === 'excused' ? 'checked' : '' }} class="form-radio text-blue-600 focus:ring-blue-500">
                                                    </label>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <input type="hidden" name="attendances[{{ $student->id }}][user_id]" value="{{ $student->id }}">
                                                    <input type="text" name="attendances[{{ $student->id }}][notes]" value="{{ $notes }}" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="{{ __('Notes optionnelles') }}">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-6 flex justify-between items-center">
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    <span class="font-medium">{{ __('Rappel') }}:</span> {{ __('Vous ne pouvez modifier les présences que jusqu\'à 7 jours après la date.') }}
                                </div>
                                <button type="submit" class="flex items-center px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white rounded-lg transition-colors font-medium">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    {{ __('Enregistrer les présences') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            @elseif($selectedClassroom)
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm p-12 text-center border border-gray-100 dark:border-slate-700">
                    <div class="p-4 rounded-full bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-gray-400 mx-auto mb-4 w-16 h-16 flex items-center justify-center">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-2">{{ __('Aucun élève inscrit') }}</h3>
                    <p class="text-gray-600 dark:text-gray-400">{{ __('Aucun élève n\'est inscrit dans cette classe pour le moment.') }}</p>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
