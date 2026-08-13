<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Gestion des élèves
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Actions + Recherche -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
                <form method="GET" action="{{ route('students.index') }}" class="w-full lg:flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Rechercher par nom, matricule..."
                               aria-label="Rechercher un élève"
                               class="flex-1 min-w-[12rem] border-gray-300 dark:border-slate-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-slate-700 dark:text-gray-100">
                        <select name="classroom_id" aria-label="Filtrer par classe" class="border-gray-300 dark:border-slate-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-slate-700 dark:text-gray-100">
                            <option value="">Toutes les classes</option>
                            @foreach($classrooms as $classroom)
                                <option value="{{ $classroom->id }}" {{ request('classroom_id') == $classroom->id ? 'selected' : '' }}>{{ $classroom->name }}</option>
                            @endforeach
                        </select>
                        <select name="status" aria-label="Filtrer par statut" class="border-gray-300 dark:border-slate-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-slate-700 dark:text-gray-100">
                            <option value="">Tous statuts</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actif</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactif</option>
                            <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archivé</option>
                        </select>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">Filtrer</button>
                        <a href="{{ route('students.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded-md hover:bg-gray-300 text-center dark:bg-slate-700 dark:text-gray-200 dark:hover:bg-slate-600">Réinit.</a>
                    </div>
                </form>
            </div>

            <!-- Élèves : cartes empilées sur mobile, tableau à partir de md -->
            <div class="bg-white dark:bg-slate-800 shadow-sm rounded-lg border border-gray-200 dark:border-slate-700 overflow-hidden md:hidden divide-y divide-gray-200 dark:divide-slate-700">
                @forelse ($students as $student)
                    <div class="p-4 flex items-start gap-3">
                        <img src="{{ $student->profile_photo_url }}"
                             alt="{{ $student->name }}"
                             loading="lazy"
                             class="w-12 h-12 rounded-full object-cover flex-shrink-0">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ $student->name }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ $student->matricule }} · {{ $student->latestRegistration?->classroom?->name ?? 'Non assigné' }}</p>
                                </div>
                                <div class="flex-shrink-0">
                                    @if($student->trashed())
                                        <x-badge color="red">Archivé</x-badge>
                                    @elseif(!$student->is_active)
                                        <x-badge color="red">Inactif</x-badge>
                                    @elseif($student->latestRegistration?->status === 'pending')
                                        <x-badge color="amber">En attente</x-badge>
                                    @else
                                        <x-badge color="green">Actif</x-badge>
                                    @endif
                                </div>
                            </div>
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                Inscrit le {{ $student->latestRegistration?->registration_date?->format('d/m/Y') ?? '—' }}
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @if($student->trashed())
                                    @can('supprimer-eleve', $student)
                                        <form action="{{ route('students.restore', $student->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex whitespace-nowrap items-center px-2.5 py-1 rounded-md text-xs font-medium bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-300 dark:hover:bg-emerald-900/50 transition-colors">
                                                Restaurer
                                            </button>
                                        </form>
                                    @endcan
                                @else
                                    <a href="{{ route('students.show', $student) }}" class="inline-flex whitespace-nowrap items-center px-2.5 py-1 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-300 dark:hover:bg-indigo-900/50 transition-colors">
                                        Voir
                                    </a>
                                    @can('enregistrer-paiement')
                                        <a href="{{ route('payments.create', ['matricule' => $student->matricule]) }}" class="inline-flex whitespace-nowrap items-center px-2.5 py-1 rounded-md text-xs font-medium bg-green-50 text-green-700 hover:bg-green-100 dark:bg-green-900/30 dark:text-green-300 dark:hover:bg-green-900/50 transition-colors">
                                            Payer
                                        </a>
                                    @endcan
                                    @can('modifier-eleve', $student)
                                        <a href="{{ route('students.edit', $student) }}" class="inline-flex whitespace-nowrap items-center px-2.5 py-1 rounded-md text-xs font-medium bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-900/30 dark:text-amber-300 dark:hover:bg-amber-900/50 transition-colors">
                                            Modifier
                                        </a>
                                    @endcan
                                    @can('supprimer-eleve', $student)
                                        <form action="{{ route('students.destroy', $student) }}" method="POST" class="inline" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Archiver l’élève', message: 'Le dossier de {{ addslashes($student->name) }} sera archivé. Les données historiques seront conservées.', confirmLabel: 'Archiver' })">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex whitespace-nowrap items-center px-2.5 py-1 rounded-md text-xs font-medium bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50 transition-colors">
                                                Archiver
                                            </button>
                                        </form>
                                    @endcan
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-500 dark:text-gray-400">Aucun élève trouvé.</div>
                @endforelse
            </div>

            <!-- Tableau des élèves -->
            <div class="hidden md:block bg-white dark:bg-slate-800 shadow-sm rounded-lg border border-gray-200 dark:border-slate-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full align-middle [&_th]:align-middle [&_td]:align-middle [&_th]:whitespace-nowrap [&_td]:whitespace-nowrap divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Photo</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Matricule</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Nom & Prénom</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Classe</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date d'inscription</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Statut</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[12rem]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                        @forelse ($students as $student)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <img src="{{ $student->profile_photo_url }}"
                                         alt="{{ $student->name }}"
                                         loading="lazy"
                                         class="w-10 h-10 rounded-full object-cover">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $student->matricule }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $student->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $student->latestRegistration?->classroom?->name ?? 'Non assigné' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $student->latestRegistration?->registration_date?->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($student->trashed())
                                        <x-badge color="red">Archivé</x-badge>
                                    @elseif(!$student->is_active)
                                        <x-badge color="red">Inactif</x-badge>
                                    @elseif($student->latestRegistration?->status === 'pending')
                                        <x-badge color="amber">En attente</x-badge>
                                    @else
                                        <x-badge color="green">Actif</x-badge>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right align-middle">
                                    <div class="flex flex-nowrap justify-end gap-2">
                                        @if($student->trashed())
                                            @can('supprimer-eleve', $student)
                                                <form action="{{ route('students.restore', $student->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="inline-flex whitespace-nowrap items-center px-2.5 py-1 rounded-md text-xs font-medium bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-300 dark:hover:bg-emerald-900/50 transition-colors">
                                                        Restaurer
                                                    </button>
                                                </form>
                                            @endcan
                                        @else
                                            <a href="{{ route('students.show', $student) }}" class="inline-flex whitespace-nowrap items-center px-2.5 py-1 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-300 dark:hover:bg-indigo-900/50 transition-colors">
                                                Voir
                                            </a>
                                            @can('enregistrer-paiement')
                                                <a href="{{ route('payments.create', ['matricule' => $student->matricule]) }}" class="inline-flex whitespace-nowrap items-center px-2.5 py-1 rounded-md text-xs font-medium bg-green-50 text-green-700 hover:bg-green-100 dark:bg-green-900/30 dark:text-green-300 dark:hover:bg-green-900/50 transition-colors">
                                                    Payer
                                                </a>
                                            @endcan
                                            @can('modifier-eleve', $student)
                                                <a href="{{ route('students.edit', $student) }}" class="inline-flex whitespace-nowrap items-center px-2.5 py-1 rounded-md text-xs font-medium bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-900/30 dark:text-amber-300 dark:hover:bg-amber-900/50 transition-colors">
                                                    Modifier
                                                </a>
                                            @endcan
                                            @can('supprimer-eleve', $student)
                                                <form action="{{ route('students.destroy', $student) }}" method="POST" class="inline" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Archiver l’élève', message: 'Le dossier de {{ addslashes($student->name) }} sera archivé. Les données historiques seront conservées.', confirmLabel: 'Archiver' })">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex whitespace-nowrap items-center px-2.5 py-1 rounded-md text-xs font-medium bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50 transition-colors">
                                                        Archiver
                                                    </button>
                                                </form>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    Aucun élève trouvé.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-700">
                {{ $students->links() }}
            </div>
        </div>
        </div>
    </div>
</x-app-layout>