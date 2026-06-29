<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mon Espace Élève</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Bandeau Annonces (Priorité Haute) -->
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded shadow-sm">
                <div class="flex">
                    <x-heroicon-o-megaphone class="h-6 w-6 text-yellow-500 mr-3"/>
                    <div>
                        <p class="font-bold text-yellow-800">Annonces</p>
                        <p class="text-sm text-yellow-700">Bienvenue Moustapha. Les examens du semestre 2 débutent le 15 juillet.</p>
                    </div>
                </div>
            </div>

            <!-- Grille des Widgets -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                
                <!-- Widget 1: Notes (Style standard) -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="font-semibold text-gray-700 mb-4 flex items-center">
                        <x-heroicon-o-pencil class="w-5 h-5 mr-2 text-blue-500"/> Dernières notes
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span>Français</span>
                            <span class="bg-emerald-100 text-emerald-800 px-2 py-1 rounded text-xs font-bold">16/20</span>
                        </div>
                    </div>
                </div>

                <!-- Widget 2: Paiements (Header Violet) -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 border-t-4 border-t-purple-500">
                    <h3 class="font-semibold text-gray-700 mb-4">Paiements</h3>
                    <p class="text-2xl font-bold">80 000 FCFA</p>
                    <p class="text-xs text-gray-500">Encaissé le 27/06/2026</p>
                </div>

                <!-- Widget 3: Absences (Header Rouge) -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 border-t-4 border-t-red-500">
                    <h3 class="font-semibold text-gray-700 mb-4">Absences</h3>
                    <p class="text-red-600 font-bold">1 non justifiée</p>
                </div>

                <!-- Widget 4: Emploi du temps (Header Vert) -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 border-t-4 border-t-teal-500">
                    <h3 class="font-semibold text-gray-700 mb-4">Emploi du temps</h3>
                    <button class="text-sm text-blue-600 underline">Télécharger PDF</button>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>