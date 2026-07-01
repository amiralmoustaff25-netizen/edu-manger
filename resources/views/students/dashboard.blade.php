<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Mon Espace Élève') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Message de bienvenue -->
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg mb-6 @if(Auth::user()->cycle === 'primaire') border-t-4 border-emerald-500 @elseif(Auth::user()->cycle === 'college') border-t-4 border-orange-500 @else border-t-4 border-red-500 @endif">
                <div class="p-6 text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-bold @if(Auth::user()->cycle === 'primaire') text-emerald-600 dark:text-emerald-400 @elseif(Auth::user()->cycle === 'college') text-orange-600 dark:text-orange-400 @else text-red-600 dark:text-red-400 @endif mb-2">
                            Bonjour, {{ Auth::user()->name }} ! 👋
                        </h3>
                        <p class="text-gray-600 dark:text-gray-300">
                            Bienvenue sur ton portail EduManager. Voici le résumé de tes informations scolaires.
                        </p>
                    </div>
                    <!-- Optionnel : Avatar de l'élève -->
                    <div class="hidden md:block">
                        <div class="h-16 w-16 rounded-full @if(Auth::user()->cycle === 'primaire') bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400 @elseif(Auth::user()->cycle === 'college') bg-orange-100 dark:bg-orange-900/50 flex items-center justify-center text-orange-600 dark:text-orange-400 @else bg-red-100 dark:bg-red-900/50 flex items-center justify-center text-red-600 dark:text-red-400 @endif font-bold text-2xl">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                    </div>
                </div>
 

            </div>

            <!-- Grille des statistiques / widgets -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                
                <!-- Carte 1 : Classe -->
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm p-6 border border-gray-100 dark:border-slate-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">Ma Classe</p>
                            <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                                {{ $registration->classroom->name ?? 'Classe non assignée' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Carte 2 : Professeur Principal -->
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm p-6 border border-gray-100 dark:border-slate-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 dark:bg-green-900/50 text-green-600 dark:text-green-400">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">Professeur Principal</p>
                            <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                                {{ $registration->classroom->teacher->name ?? 'Aucun professeur' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Carte 3 : Statut de l'inscription -->
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm p-6 border border-gray-100 dark:border-slate-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">Statut Inscription</p>
                            <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                                @if($registration)
                                    <span class="text-green-600 dark:text-green-400">Active</span>
                                @else
                                    <span class="text-red-600 dark:text-red-400">En attente</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                
            </div>

            <!-- Section de contenu principal (Actualités ou Emploi du temps) -->
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 border-b border-gray-100 dark:border-slate-700">
                    
                    @if(Auth::user()->cycle === 'primaire')
                        <!-- Primaire : Cahier de texte et communications prioritaires -->
                        <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">📚 Mon Cahier de Texte</h4>
                        <div class="space-y-4">
                            <div class="border-l-4 border-indigo-500 dark:border-indigo-400 bg-indigo-50 dark:bg-indigo-900/50 p-4 rounded-r-lg">
                                <p class="font-semibold text-indigo-800 dark:text-indigo-200">Devoirs pour demain</p>
                                <p class="text-sm text-indigo-700 dark:text-indigo-300 mt-1">Mathématiques : Exercices 1 à 5 page 42</p>
                            </div>
                            <div class="border-l-4 border-green-500 dark:border-green-400 bg-green-50 dark:bg-green-900/50 p-4 rounded-r-lg">
                                <p class="font-semibold text-green-800 dark:text-green-200">Lecture du soir</p>
                                <p class="text-sm text-green-700 dark:text-green-300 mt-1">Lire le chapitre 3 de "Le Petit Prince"</p>
                            </div>
                        </div>
                    @elseif(Auth::user()->cycle === 'college')
                        <!-- Collège : Équilibre entre devoirs et communications -->
                        <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">📋 Mes Devoirs & Actualités</h4>
                        <div class="space-y-4">
                            <div class="border-l-4 border-indigo-500 dark:border-indigo-400 bg-indigo-50 dark:bg-indigo-900/50 p-4 rounded-r-lg">
                                <p class="font-semibold text-indigo-800 dark:text-indigo-200">Devoirs à rendre</p>
                                <p class="text-sm text-indigo-700 dark:text-indigo-300 mt-1">Français : Rédaction sur "Mon héros préféré" (pour vendredi)</p>
                            </div>
                            <div class="border-l-4 border-amber-500 dark:border-amber-400 bg-amber-50 dark:bg-amber-900/50 p-4 rounded-r-lg">
                                <p class="font-semibold text-amber-800 dark:text-amber-200">Interrogation prévue</p>
                                <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">Histoire-Géographie : Chapitre sur la Révolution française (mercredi)</p>
                            </div>
                            <div class="border-l-4 border-green-500 dark:border-green-400 bg-green-50 dark:bg-green-900/50 p-4 rounded-r-lg">
                                <p class="font-semibold text-green-800 dark:text-green-200">Actualité de l'école</p>
                                <p class="text-sm text-green-700 dark:text-green-300 mt-1">Les bulletins du trimestre sont disponibles</p>
                            </div>
                        </div>
                    @else
                        <!-- Lycée : Vue orientée vers les examens et l'autonomie -->
                        <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">🎯 Examens & Planning</h4>
                        <div class="space-y-4">
                            <div class="border-l-4 border-red-500 dark:border-red-400 bg-red-50 dark:bg-red-900/50 p-4 rounded-r-lg">
                                <p class="font-semibold text-red-800 dark:text-red-200">Bac Blanc - Semaine prochaine</p>
                                <p class="text-sm text-red-700 dark:text-red-300 mt-1">Préparez-vous : Philosophie, Histoire, Anglais</p>
                            </div>
                            <div class="border-l-4 border-indigo-500 dark:border-indigo-400 bg-indigo-50 dark:bg-indigo-900/50 p-4 rounded-r-lg">
                                <p class="font-semibold text-indigo-800 dark:text-indigo-200">Orientation Post-Bac</p>
                                <p class="text-sm text-indigo-700 dark:text-indigo-300 mt-1">Journée portes ouvertes universités : 15 mars</p>
                            </div>
                            <div class="border-l-4 border-purple-500 dark:border-purple-400 bg-purple-50 dark:bg-purple-900/50 p-4 rounded-r-lg">
                                <p class="font-semibold text-purple-800 dark:text-purple-200">Notes récentes</p>
                                <p class="text-sm text-purple-700 dark:text-purple-300 mt-1">Moyenne trimestrielle : 14.5/20 - Progression : +1.2</p>
                            </div>
                        </div>
                    @endif
                    
                </div>
            </div>

        </div>
    </div>
</x-app-layout>