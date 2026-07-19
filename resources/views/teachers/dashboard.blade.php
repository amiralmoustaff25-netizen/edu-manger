<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Espace Professeur') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Message de bienvenue -->
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg mb-6 border-t-4 border-indigo-500">
                <div class="p-6 text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mb-2">
                            {{ __('Bonjour, :name ! 👋', ['name' => auth()->user()->name]) }}
                        </h3>
                        <p class="text-gray-600 dark:text-gray-300">
                            {{ __('Bienvenue sur votre portail enseignant. Voici le résumé de vos classes et activités.') }}
                        </p>
                    </div>
                    <div class="hidden md:block">
                        <div class="h-16 w-16 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-2xl">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grille des statistiques - Première ligne -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                
                <!-- Carte 1 : Classes -->
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm p-6 border border-gray-100 dark:border-slate-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="mb-1 text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('Mes Classes') }}</p>
                            <p class="text-2xl font-bold text-gray-700 dark:text-gray-200">
                                {{ $stats['total_classes'] }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Carte 2 : Élèves -->
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm p-6 border border-gray-100 dark:border-slate-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 dark:bg-green-900/50 text-green-600 dark:text-green-400">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="mb-1 text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('Total Élèves') }}</p>
                            <p class="text-2xl font-bold text-gray-700 dark:text-gray-200">
                                {{ $stats['total_students'] }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Carte 3 : Taux de présence -->
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm p-6 border border-gray-100 dark:border-slate-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="mb-1 text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('Présence Moyenne') }}</p>
                            <p class="text-2xl font-bold text-gray-700 dark:text-gray-200">
                                {{ number_format($stats['average_attendance'], 1) }}%
                            </p>
                        </div>
                    </div>
                </div>
                
            </div>

            <!-- Grille des statistiques - Deuxième ligne -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

                <!-- Carte 4 : Progression des cours -->
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm p-6 border border-gray-100 dark:border-slate-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="mb-1 text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('Progression Cours') }}</p>
                            <p class="text-2xl font-bold text-gray-700 dark:text-gray-200">
                                {{ number_format($stats['average_progress'], 1) }}%
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Carte 5 : Volume horaire -->
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm p-6 border border-gray-100 dark:border-slate-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="mb-1 text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('Volume Horaire') }}</p>
                            <p class="text-2xl font-bold text-gray-700 dark:text-gray-200">
                                {{ $stats['total_hours'] }}h
                            </p>
                        </div>
                    </div>
                </div>
                
            </div>

            <!-- Section Classes & Planning -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Mes Classes -->
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between">
                        <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200">📚 {{ __('Mes Classes') }}</h4>
                        <a href="{{ route('professeur.classes.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                            {{ __('Voir tout') }}
                        </a>
                    </div>
                    <div class="p-6">
                        @if(count($classroomDetails) > 0)
                            <div class="space-y-3">
                                @foreach($classroomDetails as $detail)
                                    <div class="flex items-center justify-between p-4 rounded-lg bg-gray-50 dark:bg-slate-700/50 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">
                                        <div class="flex items-center">
                                            <div class="p-2 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 mr-3">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $detail['classroom']->name }}</p>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    {{ $detail['total_students'] }} {{ __('élèves') }}
                                                    @if($detail['matiere'])
                                                        • {{ $detail['matiere']->nom }}
                                                    @endif
                                                    • {{ $detail['volume_horaire'] }}h/semaine
                                                </p>
                                            </div>
                                        </div>
                                        <a href="{{ route('professeur.classes.show', $detail['classroom']) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 dark:text-gray-400 italic">{{ __('Aucune classe assignée pour le moment.') }}</p>
                        @endif
                    </div>
                </div>

                <!-- Détails des classes -->
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between">
                        <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200">� {{ __('Détails par Classe') }}</h4>
                    </div>
                    <div class="p-6">
                        @if(count($classroomDetails) > 0)
                            <div class="space-y-4">
                                @foreach($classroomDetails as $detail)
                                    <div class="p-4 rounded-lg bg-gray-50 dark:bg-slate-700/50 border border-gray-200 dark:border-slate-600">
                                        <div class="flex items-center justify-between mb-2">
                                            <h5 class="font-semibold text-gray-800 dark:text-gray-200">{{ $detail['classroom']->name }}</h5>
                                            <span class="text-xs px-2 py-1 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400">
                                                {{ $detail['annee_scolaire'] }}
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2 text-sm">
                                            <div class="text-gray-600 dark:text-gray-400">
                                                <span class="font-medium">Élèves:</span> {{ $detail['total_students'] }}
                                            </div>
                                            <div class="text-gray-600 dark:text-gray-400">
                                                <span class="font-medium">Présence:</span> {{ $detail['attendance_rate'] }}%
                                            </div>
                                            <div class="text-gray-600 dark:text-gray-400">
                                                <span class="font-medium">Moyenne:</span> {{ $detail['class_average'] }}/20
                                            </div>
                                            <div class="text-gray-600 dark:text-gray-400">
                                                <span class="font-medium">Progression:</span> {{ $detail['average_progress'] }}%
                                            </div>
                                        </div>
                                        @if($detail['matiere'])
                                            <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                                <span class="font-medium">Matière:</span> {{ $detail['matiere']->nom }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 dark:text-gray-400 italic">{{ __('Aucune classe assignée pour le moment.') }}</p>
                        @endif
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
