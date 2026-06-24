<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestion des Parents</h2>
                <p class="mt-1 text-sm text-gray-500">Recherche, statut et gestion des comptes parents.</p>
            </div>
            <a href="{{ route('parents.create') }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                Nouveau parent
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <form method="GET" action="{{ route('parents.index') }}" class="grid gap-4 md:grid-cols-3">
                    <div class="md:col-span-2">
                        <label for="search" class="block text-sm font-medium text-gray-700">Recherche</label>
                        <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" type="text" placeholder="Matricule, nom, prénom, email ou téléphone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="statut" class="block text-sm font-medium text-gray-700">Statut</label>
                        <select id="statut" name="statut" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Tous</option>
                            <option value="actif" @selected(($filters['statut'] ?? '') === 'actif')">Actifs</option>
                            <option value="en_attente_activation" @selected(($filters['statut'] ?? '') === 'en_attente_activation')">En attente</option>
                            <option value="archive" @selected(($filters['statut'] ?? '') === 'archive')">Archivés</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2 md:col-span-3">
                        <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">Filtrer</button>
                        <a href="{{ route('parents.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Réinitialiser</a>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Matricule</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Parent</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Contact</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Enfants</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Statut</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($parents as $parent)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $parent->matricule_parent }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-900">{{ $parent->nom }} {{ $parent->prenom }}</p>
                                        <p class="text-xs text-gray-500">{{ $parent->profession ?? 'Profession non renseignée' }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-gray-700">{{ $parent->email }}</p>
                                        <p class="text-xs text-gray-500">{{ $parent->telephone ?? 'Téléphone non renseigné' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">{{ $parent->students->count() }} enfant(s)</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium 
                                            {{ $parent->statut === 'actif' ? 'bg-emerald-100 text-emerald-800' : 
                                               ($parent->statut === 'en_attente_activation' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-800') }}">
                                            {{ $parent->statut === 'actif' ? 'Actif' : 
                                               ($parent->statut === 'en_attente_activation' ? 'En attente' : 'Archivé') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('parents.show', $parent) }}" class="rounded-md border border-indigo-200 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Voir</a>
                                            <a href="{{ route('parents.edit', $parent) }}" class="rounded-md border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">Modifier</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">Aucun parent trouvé.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-4 py-3">
                    {{ $parents->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
