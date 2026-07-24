<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Modifier la notification') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-100 dark:border-slate-700">
                    <form action="{{ route('announcements.update', $announcement) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        @include('announcements._form')

                        <div class="mt-8 flex flex-wrap gap-3 border-t border-gray-200 dark:border-slate-700 pt-6">
                            <a href="{{ route('announcements.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 dark:bg-slate-700 dark:text-gray-200 dark:hover:bg-slate-600">
                                {{ __('Annuler') }}
                            </a>
                            <button type="submit" name="action" value="draft" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 dark:bg-slate-600 dark:text-gray-200 dark:hover:bg-slate-500">
                                {{ __('Enregistrer les modifications') }}
                            </button>
                            <button type="submit" name="action" value="schedule" class="px-4 py-2 bg-amber-100 text-amber-700 rounded-md hover:bg-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:hover:bg-amber-900/60">
                                {{ __('Programmer') }}
                            </button>
                            <button type="submit" name="action" value="publish" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                {{ __('Publier maintenant') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
