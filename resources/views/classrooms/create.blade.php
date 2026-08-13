<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Ajouter une nouvelle classe') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                
                <form action="{{ route('classrooms.store') }}" method="POST">
                    @csrf

                    @if ($errors->any())
                        <div class="mb-6 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-900/20 dark:text-red-200">
                            <p class="font-semibold">La classe n'a pas pu être enregistrée :</p>
                            <ul class="mt-2 list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-4">
                        <label for="level" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Niveau</label>
                        <select name="level" id="level" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="" disabled {{ old('level') ? '' : 'selected' }}>-- Sélectionnez un niveau --</option>
                            <optgroup label="Primaire">
                                <option value="CI" @selected(old('level') === 'CI')>CI</option>
                                <option value="CP" @selected(old('level') === 'CP')>CP</option>
                                <option value="CE1" @selected(old('level') === 'CE1')>CE1</option>
                                <option value="CE2" @selected(old('level') === 'CE2')>CE2</option>
                                <option value="CM1" @selected(old('level') === 'CM1')>CM1</option>
                                <option value="CM2" @selected(old('level') === 'CM2')>CM2</option>
                            </optgroup>
                            <optgroup label="Collège">
                                <option value="6ème" @selected(old('level') === '6ème')>6ème</option>
                                <option value="5ème" @selected(old('level') === '5ème')>5ème</option>
                                <option value="4ème" @selected(old('level') === '4ème')>4ème</option>
                                <option value="3ème" @selected(old('level') === '3ème')>3ème</option>
                            </optgroup>
                            <optgroup label="Lycée">
                                <option value="Seconde" @selected(old('level') === 'Seconde')>Seconde</option>
                                <option value="Première" @selected(old('level') === 'Première')>Première</option>
                                <option value="Terminale" @selected(old('level') === 'Terminale')>Terminale</option>
                            </optgroup>
                        </select>
                        <x-input-error :messages="$errors->get('level')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <label for="teacher_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Enseignant titulaire</label>
                        <select name="teacher_id" id="teacher_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Aucun enseignant --</option>
                            @foreach(\App\Models\User::role('professeur')->get() as $teacher)
                                <option value="{{ $teacher->id }}" @selected((string) old('teacher_id') === (string) $teacher->id)>{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('teacher_id')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <label for="max_students" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre maximum d'élèves</label>
                        <input type="number" name="max_students" id="max_students" value="{{ old('max_students', 30) }}" min="1" max="60" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <x-input-error :messages="$errors->get('max_students')" class="mt-2" />
                    </div>

                    <div class="mb-6">
                        <label for="section" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Section (Optionnel : A, B...)</label>
                        <input type="text" name="section" id="section" value="{{ old('section') }}" placeholder="Ex: A" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <x-input-error :messages="$errors->get('section')" class="mt-2" />
                    </div>

                    <div class="flex justify-end space-x-3 border-t border-gray-200 dark:border-gray-700 pt-4 mt-6">
                        <a href="{{ route('classrooms.index') }}" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">Annuler</a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Enregistrer</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>