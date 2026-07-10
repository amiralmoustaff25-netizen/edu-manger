<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Détails de la classe') }} : {{ $classroom->name }}
            </h2>
            <a href="{{ route('professeur.classes.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                {{ __('Retour aux classes') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Informations de la classe -->
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 mr-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200">{{ $classroom->name }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $classroom->cycle ?? '' }} • {{ $classroom->schoolYear->name ?? __('Année non définie') }}</p>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <a href="{{ route('professeur.notes.index', ['classroom_id' => $classroom->id]) }}" class="flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors text-sm font-medium">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                {{ __('Saisir notes') }}
                            </a>
                            <a href="{{ route('professeur.attendances.index', ['classroom_id' => $classroom->id]) }}" class="flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg transition-colors text-sm font-medium">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                {{ __('Présences') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des élèves -->
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                    <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        {{ __('Élèves inscrits') }} ({{ $students->count() }})
                    </h4>
                </div>
                <div class="p-6">
                    @if($students->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                                <thead class="bg-gray-50 dark:bg-slate-700/50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Élève') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Matricule') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                                    @foreach($students as $student)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-medium">
                                                        {{ substr($student->name, 0, 1) }}
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-200">{{ $student->name }}</div>
                                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $student->email }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm text-gray-900 dark:text-gray-200 font-mono">{{ $student->matricule }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <a href="{{ route('professeur.notes.index', ['classroom_id' => $classroom->id, 'student_id' => $student->id]) }}" class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300" title="{{ __('Voir notes') }}">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="p-4 rounded-full bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-gray-400 mx-auto mb-4 w-16 h-16 flex items-center justify-center">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400">{{ __('Aucun élève inscrit dans cette classe.') }}</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
