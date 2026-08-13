<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Gestion des professeurs</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Liste, recherche et suivi des enseignants.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @can('creer-professeur')
                    <a href="{{ route('teachers.create') }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                        Ajouter un professeur
                    </a>
                @endcan
                @can('voir-professeurs')
                    <a href="{{ route('teachers.export-pdf') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700">
                        Export PDF
                    </a>
                    <a href="{{ route('teachers.export-csv') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700">
                        Export CSV
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 dark:text-gray-100">
                <form method="GET" action="{{ route('teachers.index') }}" class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Recherche</label>
                        <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" type="text" placeholder="Matricule, nom ou email" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="statut" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Statut</label>
                        <select id="statut" name="statut" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Tous</option>
                            @foreach($statuts as $statut)
                                <option value="{{ $statut }}" @selected(($filters['statut'] ?? '') === $statut)>{{ ucfirst($statut) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="matiere" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Classe / Matière</label>
                        <input id="matiere" name="matiere" value="{{ $filters['matiere'] ?? '' }}" type="text" placeholder="Nom de la matière ou classe" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="statut_compte" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Compte</label>
                        <select id="statut_compte" name="statut_compte" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Actifs</option>
                            <option value="archived" @selected(($filters['statut_compte'] ?? '') === 'archived')>Archivés</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="rounded-md bg-gray-900 dark:bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 dark:hover:bg-slate-600">Filtrer</button>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-lg bg-white dark:bg-slate-800 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-slate-900/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Matricule</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Professeur</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Statut</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Heures</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                            @forelse($teachers as $teacher)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap font-mono text-xs text-gray-700 dark:text-gray-300">{{ $teacher->matricule }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $teacher->user?->name ?? '—' }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $teacher->user?->display_email ?? $teacher->user?->email ?? '—' }}</p>
                                    </td>
                                    <td class="px-4 py-3 dark:text-gray-200">
                                        @if($teacher->trashed())
                                            <x-badge color="red">Archivé</x-badge>
                                        @else
                                            {{ ucfirst($teacher->statut) }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 dark:text-gray-200">{{ $teacher->volume_horaire_actuel }}h</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="flex justify-end gap-2 whitespace-nowrap">
                                            @if($teacher->trashed())
                                                @can('supprimer-professeur')
                                                    <form action="{{ route('teachers.restore', $teacher->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="rounded-md border border-emerald-200 dark:border-emerald-700 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:text-emerald-300 hover:bg-emerald-50 dark:hover:bg-emerald-900/30">Restaurer</button>
                                                    </form>
                                                @endcan
                                            @else
                                                <a href="{{ route('teachers.show', $teacher) }}" class="rounded-md border border-gray-300 dark:border-slate-600 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700">Voir</a>
                                                @can('modifier-professeur')
                                                    <a href="{{ route('teachers.edit', $teacher) }}" class="rounded-md border border-indigo-200 dark:border-indigo-700 px-3 py-1.5 text-xs font-semibold text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30">Modifier</a>
                                                @endcan
                                                @can('supprimer-professeur')
                                                    <form action="{{ route('teachers.destroy', $teacher) }}" method="POST" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Archiver le professeur', message: 'Le compte de {{ addslashes((string) ($teacher->user?->name ?? '—')) }} sera désactivé et archivé. Impossible si des affectations pédagogiques actives existent.', confirmLabel: 'Archiver' })">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="rounded-md border border-red-200 dark:border-red-800 px-3 py-1.5 text-xs font-semibold text-red-700 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30">Archiver</button>
                                                    </form>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Aucun professeur trouvé.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-100 dark:border-slate-700 px-4 py-3">
                    {{ $teachers->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
