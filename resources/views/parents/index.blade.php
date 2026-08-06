<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Gestion des Parents</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Recherche, statut et gestion des comptes parents.</p>
            </div>
            <a href="{{ route('parents.create') }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                Nouveau parent
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900 p-4 text-sm text-green-700 dark:text-green-200">
                    {{ session('success') }}
                </div>
            @endif

            <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 dark:text-gray-100">
                <form method="GET" action="{{ route('parents.index') }}" class="grid gap-4 md:grid-cols-3">
                    <div class="md:col-span-2">
                        <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Recherche</label>
                        <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" type="text" placeholder="Matricule, nom, prénom, email ou téléphone" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="statut" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Statut</label>
                        <select id="statut" name="statut" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Tous</option>
                            <option value="actif" @selected(($filters['statut'] ?? '') === 'actif')>Actifs</option>
                            <option value="en_attente_activation" @selected(($filters['statut'] ?? '') === 'en_attente_activation')>En attente</option>
                            <option value="archive" @selected(($filters['statut'] ?? '') === 'archive')>Archivés</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2 md:col-span-3">
                        <button type="submit" class="rounded-md bg-gray-900 dark:bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 dark:hover:bg-slate-600">Filtrer</button>
                        <a href="{{ route('parents.index') }}" class="rounded-md border border-gray-300 dark:border-slate-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700">Réinitialiser</a>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-lg bg-white dark:bg-slate-800 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-slate-900/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Matricule</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Parent</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Contact</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Enfants</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Statut</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                            @forelse($parents as $parent)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $parent->matricule_parent }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $parent->nom }} {{ $parent->prenom }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $parent->profession ?? 'Profession non renseignée' }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-gray-700 dark:text-gray-300">{{ $parent->email }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $parent->telephone ?? 'Téléphone non renseigné' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $parent->students->count() }} enfant(s)</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium 
                                            {{ $parent->statut === 'actif' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' : 
                                               ($parent->statut === 'en_attente_activation' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : 'bg-gray-100 text-gray-800 dark:bg-slate-700 dark:text-gray-200') }}">
                                            {{ $parent->statut === 'actif' ? 'Actif' : 
                                               ($parent->statut === 'en_attente_activation' ? 'En attente' : 'Archivé') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('parents.show', $parent) }}" class="rounded-md border border-indigo-200 dark:border-indigo-700 px-3 py-1.5 text-xs font-semibold text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30">Voir</a>
                                            <a href="{{ route('parents.edit', $parent) }}" class="rounded-md border border-gray-200 dark:border-slate-600 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700">Modifier</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Aucun parent trouvé.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 dark:border-slate-700 px-4 py-3">
                    {{ $parents->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
