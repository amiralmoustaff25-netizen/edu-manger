<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Modifier la classe : ') }} {{ $classroom->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                
                <form action="{{ route('classrooms.update', $classroom->id) }}" method="POST">
                    @csrf 
                    @method('PUT')

                    {{-- Logique pour séparer le niveau et la section du nom actuel --}}
                    @php 
                        $parts = explode(' ', $classroom->name); 
                        $currentLevel = $parts[0]; 
                        $currentSection = $parts[1] ?? ''; 
                    @endphp

                    <div class="mb-4">
                        <label for="level" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Niveau</label>
                        <select name="level" id="level" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
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
                    </div>
                    
                    <div class="mb-4">
                        <label for="teacher_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Enseignant titulaire</label>
                        <select name="teacher_id" id="teacher_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Aucun enseignant --</option>
                            @foreach(\App\Models\User::role(['professeur'])->get() as $teacher)
                                <option value="{{ $teacher->id }}" {{ $classroom->teacher_id == $teacher->id ? 'selected' : '' }}>
                                    {{ $teacher->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-6">
                        <label for="section" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Section</label>
                        <input type="text" name="section" id="section" value="{{ $currentSection }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="flex justify-end space-x-3 border-t border-gray-200 dark:border-gray-700 pt-4 mt-6">
                        <a href="{{ route('classrooms.index') }}" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">Annuler</a>
                        <button type="submit" class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition">Mettre à jour</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>