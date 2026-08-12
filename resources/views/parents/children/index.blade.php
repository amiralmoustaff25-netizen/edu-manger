<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Mes enfants</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if($parent->students->isEmpty())
                <div class="rounded-lg bg-white dark:bg-slate-800 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 p-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Aucun enfant associé à ce compte.</p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    @foreach($parent->students as $student)
                        <x-card>
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ $student->name }}</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Matricule : {{ $student->matricule }}</p>
                            <div class="mt-4 flex gap-2 text-sm">
                                <a href="{{ route('parents.children.profile', ['student' => $student->id]) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">Voir le profil</a>
                                <span class="text-gray-300 dark:text-slate-600">|</span>
                                <a href="{{ route('parents.children.notes', ['student' => $student->id]) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">Notes</a>
                                <span class="text-gray-300 dark:text-slate-600">|</span>
                                <a href="{{ route('parents.children.bulletins', ['student' => $student->id]) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">Bulletins</a>
                            </div>
                        </x-card>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
