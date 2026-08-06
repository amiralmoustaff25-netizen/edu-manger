<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Nouveau professeur</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Créer un compte professeur et compléter sa fiche métier.</p>
            </div>
            <a href="{{ route('teachers.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700">
                Retour à la liste
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 dark:text-gray-100">
                <form method="POST" action="{{ route('teachers.store') }}" class="space-y-6">
                    @include('teachers._form')
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
