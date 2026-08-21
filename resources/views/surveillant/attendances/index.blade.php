<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Présences des élèves') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-lg bg-white dark:bg-slate-800 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Sélectionner une classe</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Cliquez sur une classe pour consulter ou modifier sa feuille de présence.</p>
                </div>
                <div class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse($classrooms as $classroom)
                        <a href="{{ route('surveillant.attendances.class', ['classroom' => $classroom->id]) }}" class="rounded-lg border border-gray-200 dark:border-slate-700 p-4 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $classroom->name }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $classroom->schoolYear?->year_string ?? '—' }}</p>
                            <p class="mt-2 text-sm text-indigo-600 dark:text-indigo-400">Voir la feuille de présence &rarr;</p>
                        </a>
                    @empty
                        <p class="col-span-full text-center text-sm text-gray-500 dark:text-gray-400 py-8">Aucune classe disponible.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
