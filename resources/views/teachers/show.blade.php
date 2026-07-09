<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Fiche professeur</h2>
                <p class="mt-1 text-sm text-gray-500">Détails de {{ $teacher->user->name }}.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('teachers.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
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
                    <h3 class="text-lg font-semibold text-gray-900">Identité</h3>
                    <div class="mt-4 space-y-3 text-sm text-gray-600">
                        <p><span class="font-semibold text-gray-800">Matricule :</span> {{ $teacher->matricule }}</p>
                        <p><span class="font-semibold text-gray-800">Nom :</span> {{ $teacher->user->name }}</p>
                        <p><span class="font-semibold text-gray-800">Email :</span> {{ $teacher->user->email }}</p>
                        <p><span class="font-semibold text-gray-800">Date de naissance :</span> {{ $teacher->date_naissance?->format('d/m/Y') }}</p>
                        <p><span class="font-semibold text-gray-800">Lieu de naissance :</span> {{ $teacher->lieu_naissance }}</p>
                        <p><span class="font-semibold text-gray-800">Sexe :</span> {{ ucfirst($teacher->sexe) }}</p>
                        <p><span class="font-semibold text-gray-800">Nationalité :</span> {{ $teacher->nationalite }}</p>
                        <p><span class="font-semibold text-gray-800">Statut :</span> {{ ucfirst($teacher->statut) }}</p>
                        <p><span class="font-semibold text-gray-800">Date de recrutement :</span> {{ $teacher->date_recrutement?->format('d/m/Y') }}</p>
                        <p><span class="font-semibold text-gray-800">Ancienneté :</span> {{ $teacher->anciennete() }}</p>
                    </div>
                </div>

                <div class="lg:col-span-2 space-y-6">
                    <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Formation</h3>
                        <div class="mt-4 space-y-3 text-sm text-gray-600">
                            <p><span class="font-semibold text-gray-800">Diplômes :</span></p>
                            <p class="whitespace-pre-line">{{ $teacher->diplomes }}</p>
                            <p><span class="font-semibold text-gray-800">Établissement(s) :</span></p>
                            <p class="whitespace-pre-line">{{ $teacher->etablissements_formation }}</p>
                            <p><span class="font-semibold text-gray-800">Spécialités :</span> {{ implode(', ', $teacher->specialites ?? []) }}</p>
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Classes affectées</h3>
                        <div class="mt-4 space-y-4 text-sm text-gray-600">
                            @forelse($teacher->classrooms as $classroom)
                                <div class="rounded-md border border-gray-200 p-4">
                                    <p><span class="font-semibold text-gray-800">Classe :</span> {{ $classroom->name }}</p>
                                    <p><span class="font-semibold text-gray-800">Année scolaire :</span> {{ $classroom->pivot->annee_scolaire }}</p>
                                    <p><span class="font-semibold text-gray-800">Heures :</span> {{ $classroom->pivot->volume_horaire_hebdo }}h</p>
                                </div>
                            @empty
                                <p>Aucune classe affectée.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Contact d'urgence</h3>
                        <div class="mt-4 space-y-3 text-sm text-gray-600">
                            <p><span class="font-semibold text-gray-800">Nom :</span> {{ $teacher->contact_urgence_nom }}</p>
                            <p><span class="font-semibold text-gray-800">Téléphone :</span> {{ $teacher->contact_urgence_tel }}</p>
                        </div>
                    </div>

                    @if($canViewRib)
                        <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">RIB</h3>
                            <div class="mt-4 text-sm text-gray-600">
                                <p class="break-words">{{ $teacher->rib ? 'Donnée enregistrée' : 'Aucun RIB enregistré' }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
