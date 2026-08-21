<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Modifier la classe : ') }} {{ $classroom->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                
                <form action="{{ route('classrooms.update', $classroom->id) }}" method="POST" x-data="{ level: '{{ old('level', explode(' ', $classroom->name)[0] ?? '') }}' }">
                    @csrf
                    @method('PUT')

                    {{-- Logique pour séparer le niveau et la section du nom actuel --}}
                    @php
                        $parts = explode(' ', $classroom->name);
                        $currentLevel = old('level', $parts[0]);
                        $currentSection = old('section', $parts[1] ?? '');
                    @endphp

                    @if ($errors->any())
                        <div class="mb-6 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-900/20 dark:text-red-200">
                            <p class="font-semibold">La classe n'a pas pu être mise à jour :</p>
                            <ul class="mt-2 list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-4">
                        <label for="level" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Niveau</label>
                        <select name="level" id="level" x-model="level" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <optgroup label="Primaire">
                                @foreach(['CI', 'CP', 'CE1', 'CE2', 'CM1', 'CM2'] as $level)
                                    <option value="{{ $level }}" {{ $currentLevel == $level ? 'selected' : '' }}>{{ $level }}</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Collège">
                                @foreach(['6ème', '5ème', '4ème', '3ème'] as $level)
                                    <option value="{{ $level }}" {{ $currentLevel == $level ? 'selected' : '' }}>{{ $level }}</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Lycée">
                                @foreach(['Seconde', 'Première', 'Terminale'] as $level)
                                    <option value="{{ $level }}" {{ $currentLevel == $level ? 'selected' : '' }}>{{ $level }}</option>
                                @endforeach
                            </optgroup>
                        </select>
                        <x-input-error :messages="$errors->get('level')" class="mt-2" />
                    </div>

                    <div class="mb-4" x-show="['Seconde', 'Première', 'Terminale'].includes(level)" x-cloak>
                        <label for="serie" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Série</label>
                        <input type="text" name="serie" id="serie" value="{{ old('serie', $classroom->serie) }}" placeholder="Ex. L, S, ES" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <x-input-error :messages="$errors->get('serie')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <label for="teacher_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Enseignant titulaire</label>
                        <select name="teacher_id" id="teacher_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Aucun enseignant --</option>
                            @foreach(\App\Models\User::role('professeur')->get() as $teacher)
                                <option value="{{ $teacher->id }}" {{ (string) old('teacher_id', $classroom->teacher_id) === (string) $teacher->id ? 'selected' : '' }}>
                                    {{ $teacher->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('teacher_id')" class="mt-2" />
                        @if($classroom->cycle === 'primaire')
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Pour le primaire, cet enseignant devient automatiquement le professeur principal (matières générales) dans "Affectations pédagogiques" — aucune affectation manuelle supplémentaire n'est nécessaire.</p>
                        @endif
                    </div>

                    <div class="mb-4">
                        <label for="max_students" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre maximum d'élèves</label>
                        <input type="number" name="max_students" id="max_students" value="{{ old('max_students', $classroom->max_students ?? 30) }}" min="1" max="60" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <x-input-error :messages="$errors->get('max_students')" class="mt-2" />
                    </div>

                    <div class="mb-6">
                        <label for="section" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Section</label>
                        <input type="text" name="section" id="section" value="{{ $currentSection }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <x-input-error :messages="$errors->get('section')" class="mt-2" />
                    </div>

                    <div class="flex justify-end space-x-3 border-t border-gray-200 dark:border-gray-700 pt-4 mt-6">
                        <a href="{{ route('classrooms.index') }}" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">Annuler</a>
                        <button type="submit" class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition">Mettre à jour</button>
                    </div>
                </form>

                @if ($canManageTeachers)
                    <div class="mt-10 border-t border-gray-200 dark:border-gray-700 pt-8">
                        <h3 class="text-lg font-medium mb-4">Enseignants affectés à cette classe ({{ $activeYear->year_string ?? 'Année courante' }})</h3>

                        <div class="mb-8 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <h4 class="font-medium mb-4">Affecter un nouveau professeur</h4>
                            <form action="{{ route('classrooms.attach-teacher', $classroom) }}" method="POST">
                                @csrf
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="teacher_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Professeur</label>
                                        <select name="teacher_id" id="teacher_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">-- Sélectionner un professeur --</option>
                                            @foreach($teachers as $teacher)
                                                <option value="{{ $teacher->id }}">{{ $teacher->user?->name ?? '—' }}</option>
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
                                                    {{ $teacher->user?->name ?? '—' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                                    {{ $teacher->pivot->matiere_id ? \App\Models\Matiere::find($teacher->pivot->matiere_id)->nom : 'Non spécifiée' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                                    {{ $teacher->pivot->volume_horaire_hebdo }}h/semaine
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <form action="{{ route('classrooms.detach-teacher', [$classroom, $teacher]) }}" method="POST" class="inline" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Retirer le professeur', message: 'Retirer {{ addslashes((string) ($teacher->user?->name ?? '—')) }} de la classe {{ addslashes((string) $classroom->name) }} ?', confirmLabel: 'Retirer' })">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
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
                @endif

            </div>
        </div>
    </div>
</x-app-layout>