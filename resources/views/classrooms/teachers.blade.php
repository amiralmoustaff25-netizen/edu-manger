<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Gestion des enseignants - {{ $classroom->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                
                <div class="mb-6">
                    <a href="{{ route('classrooms.index') }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                        ← Retour aux classes
                    </a>
                </div>

                <!-- Formulaire d'ajout d'un professeur -->
                <div class="mb-8 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <h3 class="text-lg font-medium mb-4">Affecter un nouveau professeur</h3>
                    <form action="{{ route('classrooms.attach-teacher', $classroom) }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="teacher_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Professeur</label>
                                <select name="teacher_id" id="teacher_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">-- Sélectionner un professeur --</option>
                                    @foreach($teachers as $teacher)
                                        <option value="{{ $teacher->id }}">{{ $teacher->user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="matiere_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Matière (optionnel)</label>
                                <select name="matiere_id" id="matiere_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">-- Sans matière --</option>
                                    @foreach($matieres as $matiere)
                                        <option value="{{ $matiere->id }}">{{ $matiere->nom }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="volume_horaire_hebdo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Volume horaire hebdo</label>
                                <input type="number" name="volume_horaire_hebdo" id="volume_horaire_hebdo" value="1" min="1" max="30" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                Affecter le professeur
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Liste des professeurs affectés -->
                <div>
                    <h3 class="text-lg font-medium mb-4">Professeurs affectés ({{ $activeYear->year_string ?? 'Année courante' }})</h3>
                    
                    @if($classroom->teachers->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Professeur</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Matière</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Volume horaire</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($classroom->teachers as $teacher)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $teacher->user->name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                                {{ $teacher->pivot->matiere_id ? \App\Models\Matiere::find($teacher->pivot->matiere_id)->nom : 'Non spécifiée' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                                {{ $teacher->pivot->volume_horaire_hebdo }}h/semaine
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <form action="{{ route('classrooms.detach-teacher', [$classroom, $teacher]) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300" onclick="return confirm('Êtes-vous sûr de vouloir retirer ce professeur de la classe ?');">
                                                        Retirer
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            Aucun professeur affecté à cette classe pour cette année scolaire.
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
