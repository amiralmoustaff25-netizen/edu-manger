<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Créer un utilisateur</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 dark:text-gray-100">
                <form method="POST" action="{{ route('users.store') }}">
                    @include('users._form')
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
