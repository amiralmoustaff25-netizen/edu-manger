<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestion des Classes') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                
                <div class="flex justify-between items-center mb-6">
                    @if (session('success'))
                        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif
                    <h3 class="text-lg font-medium">Liste des classes</h3>
                    <a href="{{ route('classrooms.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition">
                        + Ajouter une classe
                    </a>
                </div>

                @if($classrooms->isEmpty())
                    <p class="text-gray-500 text-center py-4">Aucune classe n'a été créée pour le moment.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-700 text-gray-400">
                                    <th class="py-3 px-4">ID</th>
                                    <th class="py-3 px-4">Nom de la classe</th>
                                    <th class="py-3 px-4">Cycle</th>
                                    <th class="py-3 px-4">Enseignant Titulaire</th>
                                    <th class="py-3 px-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($classrooms as $classroom)
                                    <tr class="border-b border-gray-700 hover:bg-gray-700/50">
                                        <td class="py-3 px-4">{{ $classroom->id }}</td>
                                        <td class="py-3 px-4 font-semibold">{{ $classroom->name }}</td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-1 text-xs rounded bg-gray-700 text-gray-300 capitalize">
                                                {{ $classroom->cycle }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-gray-300">
                                            {{ $classroom->teacher->name ?? 'Aucun' }}
                                        </td>
                                        <td class="py-3 px-4 flex space-x-3">
                                            <a href="{{ route('classrooms.teachers', $classroom->id) }}" class="text-indigo-500 hover:text-indigo-600 font-medium transition">
                                                Enseignants
                                            </a>
                                            <a href="{{ route('classrooms.edit', $classroom->id) }}" class="text-yellow-500 hover:text-yellow-600 font-medium transition">
                                                Modifier
                                            </a>

                                            <form action="{{ route('classrooms.destroy', $classroom->id) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette classe ? Cette action est irréversible.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-600 font-medium transition">
                                                    Supprimer
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>