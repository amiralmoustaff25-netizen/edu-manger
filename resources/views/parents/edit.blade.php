<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Modifier Parent</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Modifier les informations du parent.</p>
            </div>
            <a href="{{ route('parents.show', $parent) }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700">
                Retour
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                <form method="POST" action="{{ route('parents.update', $parent) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    @if ($errors->any())
                        <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900 p-4 text-sm text-red-700 dark:text-red-200">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label for="nom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom</label>
                            <input id="nom" name="nom" type="text" value="{{ old('nom', $parent->nom) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label for="prenom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prénom</label>
                            <input id="prenom" name="prenom" type="text" value="{{ old('prenom', $parent->prenom) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $parent->email) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="telephone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Téléphone</label>
                        <input id="telephone" name="telephone" type="text" value="{{ old('telephone', $parent->telephone) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="adresse" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Adresse</label>
                        <textarea id="adresse" name="adresse" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('adresse', $parent->adresse) }}</textarea>
                    </div>

                    <div>
                        <label for="profession" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Profession</label>
                        <input id="profession" name="profession" type="text" value="{{ old('profession', $parent->profession) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="statut" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Statut</label>
                        <select id="statut" name="statut" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="en_attente_activation" @selected(old('statut', $parent->statut) === 'en_attente_activation')>En attente d'activation</option>
                            <option value="actif" @selected(old('statut', $parent->statut) === 'actif')>Actif</option>
                            <option value="archive" @selected(old('statut', $parent->statut) === 'archive')>Archivé</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-slate-700">
                        <a href="{{ route('parents.show', $parent) }}" class="rounded-md border border-gray-300 dark:border-slate-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700">
                            Annuler
                        </a>
                        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                            Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
