<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Éditer le programme</h2>
            <a href="{{ route('programs.show', $program) }}" class="rounded-md border border-gray-300 dark:border-slate-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700">Retour</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <form action="{{ route('programs.update', $program) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        Titre du chapitre
                        <input type="text" name="chapters[0][titre]" value="{{ $program->chapters->first()?->titre ?? '' }}" class="mt-1 w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        <input type="hidden" name="chapters[0][type]" value="chapitre">
                        <input type="hidden" name="chapters[0][volume_horaire_prevu]" value="2.5">
                    </label>
                    <div class="flex justify-end">
                        <x-primary-button type="submit">Enregistrer</x-primary-button>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
