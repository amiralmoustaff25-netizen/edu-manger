<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Sélection du cahier de textes
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <section class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                <form action="{{ route('cahier-textes.index') }}" method="GET" class="space-y-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        Classe
                        <select name="classroom_id" class="mt-1 w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach (App\Models\Classroom::all() as $classroom)
                                <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        Matière
                        <select name="subject_id" class="mt-1 w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach (App\Models\Matiere::all() as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->nom }}</option>
                            @endforeach
                        </select>
                    </label>
                    <x-primary-button type="submit" class="w-full justify-center">Accéder au cahier</x-primary-button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
