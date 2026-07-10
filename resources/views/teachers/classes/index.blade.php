<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Mes Classes') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- En-tête -->
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 mr-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200">{{ __('Gestion de mes Classes') }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Consultez et gérez les classes qui vous sont assignées') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grille des classes -->
            @if($classrooms->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($classrooms as $classroom)
                        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-gray-100 dark:border-slate-700 hover:shadow-md transition-shadow overflow-hidden">
                            <!-- En-tête de la carte -->
                            <div class="p-6 border-b border-gray-100 dark:border-slate-700 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="p-2 rounded-full bg-blue-500 text-white mr-3">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                        </div>
                                        <div>
                                            <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ $classroom->name }}</h4>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $classroom->cycle ?? '' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Contenu de la carte -->
                            <div class="p-6">
                                <div class="space-y-3">
                                    <!-- Année scolaire -->
                                    <div class="flex items-center text-sm">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        <span class="text-gray-600 dark:text-gray-400">{{ __('Année') }}:</span>
                                        <span class="ml-2 font-medium text-gray-800 dark:text-gray-200">{{ $classroom->schoolYear->name ?? __('Non définie') }}</span>
                                    </div>

                                    <!-- Nombre d'élèves -->
                                    <div class="flex items-center text-sm">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                        <span class="text-gray-600 dark:text-gray-400">{{ __('Élèves') }}:</span>
                                        <span class="ml-2 font-medium text-gray-800 dark:text-gray-200">{{ $classroom->registrations_count }}</span>
                                    </div>

                                    <!-- Professeur principal -->
                                    <div class="flex items-center text-sm">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        <span class="text-gray-600 dark:text-gray-400">{{ __('Prof. Principal') }}:</span>
                                        <span class="ml-2 font-medium text-gray-800 dark:text-gray-200">{{ $classroom->teacher->name ?? __('Non assigné') }}</span>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="mt-6 flex space-x-2">
                                    <a href="{{ route('professeur.classes.show', $classroom) }}" class="flex-1 flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors text-sm font-medium">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        {{ __('Voir détails') }}
                                    </a>
                                    <a href="{{ route('professeur.notes.index', ['classroom_id' => $classroom->id]) }}" class="flex-1 flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors text-sm font-medium">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        {{ __('Notes') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm p-12 text-center border border-gray-100 dark:border-slate-700">
                    <div class="p-4 rounded-full bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-gray-400 mx-auto mb-4 w-16 h-16 flex items-center justify-center">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-2">{{ __('Aucune classe assignée') }}</h3>
                    <p class="text-gray-600 dark:text-gray-400">{{ __('Vous n\'avez aucune classe assignée pour le moment. Contactez l\'administration.') }}</p>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
