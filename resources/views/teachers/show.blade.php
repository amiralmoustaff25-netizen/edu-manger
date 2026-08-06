<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Fiche professeur</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Détails de {{ $teacher->user->name }}.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('teachers.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700">
                    Retour
                </a>
                @can('modifier-professeur')
                    <a href="{{ route('teachers.edit', $teacher) }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                        Modifier
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 dark:text-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Identité</h3>
                    <div class="mt-4 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                        <p><span class="font-semibold text-gray-800 dark:text-gray-200">Matricule :</span> {{ $teacher->matricule }}</p>
                        <p><span class="font-semibold text-gray-800 dark:text-gray-200">Nom :</span> {{ $teacher->user->name }}</p>
                        <p><span class="font-semibold text-gray-800 dark:text-gray-200">Email :</span> {{ $teacher->user->email }}</p>
                        <p><span class="font-semibold text-gray-800 dark:text-gray-200">Date de naissance :</span> {{ $teacher->date_naissance?->format('d/m/Y') }}</p>
                        <p><span class="font-semibold text-gray-800 dark:text-gray-200">Lieu de naissance :</span> {{ $teacher->lieu_naissance }}</p>
                        <p><span class="font-semibold text-gray-800 dark:text-gray-200">Sexe :</span> {{ ucfirst($teacher->sexe) }}</p>
                        <p><span class="font-semibold text-gray-800 dark:text-gray-200">Nationalité :</span> {{ $teacher->nationalite }}</p>
                        <p><span class="font-semibold text-gray-800 dark:text-gray-200">Statut :</span> {{ ucfirst($teacher->statut) }}</p>
                        <p><span class="font-semibold text-gray-800 dark:text-gray-200">Date de recrutement :</span> {{ $teacher->date_recrutement?->format('d/m/Y') }}</p>
                        <p><span class="font-semibold text-gray-800 dark:text-gray-200">Ancienneté :</span> {{ $teacher->anciennete() }}</p>
                    </div>
                </div>

                <div class="lg:col-span-2 space-y-6">
                    <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Formation</h3>
                        <div class="mt-4 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                            <p><span class="font-semibold text-gray-800 dark:text-gray-200">Diplômes :</span></p>
                            <p class="whitespace-pre-line">{{ $teacher->diplomes }}</p>
                            <p><span class="font-semibold text-gray-800 dark:text-gray-200">Établissement(s) :</span></p>
                            <p class="whitespace-pre-line">{{ $teacher->etablissements_formation }}</p>
                            <p><span class="font-semibold text-gray-800 dark:text-gray-200">Spécialités :</span> {{ implode(', ', $teacher->specialites ?? []) }}</p>
                        </div>
                    </div>

                    <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Classes et évolution des cours</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Semaine du {{ \Carbon\Carbon::parse($weekStart)->format('d/m') }} au {{ \Carbon\Carbon::parse($weekEnd)->format('d/m/Y') }}</p>
                        </div>
                        <div class="mt-4 space-y-4 text-sm text-gray-600 dark:text-gray-300">
                            @forelse($assignments as $assignment)
                                <div class="rounded-md border border-gray-200 dark:border-slate-700 p-4">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $assignment->classroom->name }} — {{ $assignment->matiere->nom }}</p>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $assignment->schoolYear->year_string }} · {{ $assignment->teaching_sessions_count }} cours pointé(s)</p>
                                        </div>
                                        <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">{{ $assignment->remaining_weekly_hours }} h restantes cette semaine</p>
                                    </div>
                                    <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-gray-200 dark:bg-slate-700">
                                        <div class="h-full rounded-full {{ $assignment->weekly_progress >= 100 ? 'bg-emerald-500' : 'bg-indigo-600' }}" style="width: {{ $assignment->weekly_progress }}%"></div>
                                    </div>
                                    <div class="mt-2 flex flex-wrap justify-between gap-2 text-xs text-gray-500 dark:text-gray-400">
                                        <span>{{ $assignment->weekly_taught_hours }} h réalisées cette semaine</span>
                                        <span>{{ $assignment->volume_horaire_hebdo }} h prévues / semaine</span>
                                        <span>{{ $assignment->total_taught_hours }} h réalisées au total</span>
                                    </div>
                                </div>
                            @empty
                                <p>Aucune affectation pédagogique active pour ce professeur.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Contact d'urgence</h3>
                        <div class="mt-4 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                            <p><span class="font-semibold text-gray-800 dark:text-gray-200">Nom :</span> {{ $teacher->contact_urgence_nom }}</p>
                            <p><span class="font-semibold text-gray-800 dark:text-gray-200">Téléphone :</span> {{ $teacher->contact_urgence_tel }}</p>
                        </div>
                    </div>

                    @if($canViewRib)
                        <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">RIB</h3>
                            <div class="mt-4 text-sm text-gray-600 dark:text-gray-300">
                                <p class="break-words">{{ $teacher->rib ? 'Donnée enregistrée' : 'Aucun RIB enregistré' }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
