<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Gestion des professeurs</h2>
                <p class="mt-1 text-sm text-gray-500">Liste, recherche et suivi des enseignants.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @can('creer-professeur')
                    <a href="{{ route('teachers.create') }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                        Ajouter un professeur
                    </a>
                @endcan
                @can('voir-professeurs')
                    <a href="{{ route('teachers.export-pdf') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                        Export PDF
                    </a>
                    <a href="{{ route('teachers.export-csv') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                        Export CSV
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 dark:text-gray-100">
                <form method="GET" action="{{ route('teachers.index') }}" class="grid gap-4 lg:grid-cols-4">
                    <div class="lg:col-span-2">
                        <label for="search" class="block text-sm font-medium text-gray-700">Recherche</label>
                        <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" type="text" placeholder="Matricule, nom ou email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="statut" class="block text-sm font-medium text-gray-700">Statut</label>
                        <select id="statut" name="statut" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Tous</option>
                            @foreach($statuts as $statut)
                                <option value="{{ $statut }}" @selected(($filters['statut'] ?? '') === $statut)>{{ ucfirst($statut) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="matiere" class="block text-sm font-medium text-gray-700">Classe / Matière</label>
                        <input id="matiere" name="matiere" value="{{ $filters['matiere'] ?? '' }}" type="text" placeholder="Nom de la matière ou classe" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="anciennete" class="block text-sm font-medium text-gray-700">Ancienneté minimale (années)</label>
                        <input id="anciennete" name="anciennete" value="{{ $filters['anciennete'] ?? '' }}" type="number" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Matricule</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Professeur</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Statut</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Ancienneté</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Heures</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Volume légal</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($teachers as $teacher)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $teacher->matricule }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-900">{{ $teacher->user->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $teacher->user->email }}</p>
                                    </td>
                                    <td class="px-4 py-3">{{ ucfirst($teacher->statut) }}</td>
                                    <td class="px-4 py-3">{{ $teacher->anciennete() }}</td>
                                    <td class="px-4 py-3">{{ $teacher->volume_horaire_actuel }}h</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $teacher->depasseVolumeLegal() ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800' }}">
                                            {{ $teacher->depasseVolumeLegal() ? 'Dépassement' : 'OK' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a href="{{ route('teachers.show', $teacher) }}" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">Voir</a>
                                            @can('modifier-professeur')
                                                <a href="{{ route('teachers.edit', $teacher) }}" class="rounded-md border border-indigo-200 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Modifier</a>
                                            @endcan
                                            @can('supprimer-professeur')
                                                <form action="{{ route('teachers.destroy', $teacher) }}" method="POST" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Supprimer le professeur', message: 'Le profil de {{ addslashes($teacher->user->name) }} sera supprimé. Vérifiez ses affectations avant de confirmer.', confirmLabel: 'Supprimer' })">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">Supprimer</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">Aucun professeur trouvé.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-100 px-4 py-3">
                    {{ $teachers->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
