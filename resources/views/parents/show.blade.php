<x-app-layout>
    <x-slot name="header">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Fiche Parent</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Informations détaillées du parent et de ses enfants.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('parents.edit', $parent) }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 dark:border-slate-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700">
                Modifier
            </a>
            <a href="{{ route('parents.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 dark:border-slate-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700">
                Retour
            </a>
        </div>
    </div>
</x-slot>

<div class="py-12">
    <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/50 p-4 text-sm text-green-700 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif

        <!-- Informations du parent -->
        <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $parent->nom }} {{ $parent->prenom }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Matricule : {{ $parent->matricule_parent }}</p>
                </div>
                <span class="rounded-full px-3 py-1 text-sm font-medium 
                    {{ $parent->statut === 'actif' ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-800 dark:text-emerald-200' : 
                       ($parent->statut === 'en_attente_activation' ? 'bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-200' : 'bg-gray-100 dark:bg-slate-700 text-gray-800 dark:text-gray-200') }}">
                    {{ $parent->statut === 'actif' ? 'Actif' : 
                       ($parent->statut === 'en_attente_activation' ? 'En attente' : 'Archivé') }}
                </span>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Informations de contact</h4>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Email</dt>
                            <dd class="text-gray-900 dark:text-gray-100">{{ $parent->email }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Téléphone</dt>
                            <dd class="text-gray-900 dark:text-gray-100">{{ $parent->telephone ?? 'Non renseigné' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Adresse</dt>
                            <dd class="text-gray-900 dark:text-gray-100">{{ $parent->adresse ?? 'Non renseignée' }}</dd>
                        </div>
                    </dl>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Informations professionnelles</h4>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Profession</dt>
                            <dd class="text-gray-900 dark:text-gray-100">{{ $parent->profession ?? 'Non renseignée' }}</dd>
                        </div>
                        @if($parent->user)
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Compte utilisateur</dt>
                            <dd class="text-gray-900 dark:text-gray-100">{{ $parent->user->matricule }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Statut compte</dt>
                            <dd class="text-gray-900 dark:text-gray-100">
                                <span class="{{ $parent->user->is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $parent->user->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="mt-6 flex gap-3 pt-4 border-t border-gray-200 dark:border-slate-700">
                @if($parent->trashed())
                    @can('restaurer-parent', $parent)
                        <form method="POST" action="{{ route('parents.restore', $parent->id) }}">
                            @csrf
                            <button type="submit" class="rounded-md border border-emerald-200 dark:border-emerald-700 px-4 py-2 text-sm font-semibold text-emerald-700 dark:text-emerald-300 hover:bg-emerald-50 dark:hover:bg-emerald-900/30">
                                Restaurer
                            </button>
                        </form>
                    @endcan
                @else
                    <form method="POST" action="{{ route('parents.archive', $parent) }}" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Archiver le parent', message: 'Le parent sera archivé et son accès pourra être désactivé. Les associations avec les élèves seront conservées.', confirmLabel: 'Archiver' })">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="rounded-md border border-gray-300 dark:border-slate-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700">
                            Archiver
                        </button>
                    </form>
                @endif
                @if($parent->user)
                <form method="POST" action="{{ route('parents.reset-password', $parent) }}" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Réinitialiser le mot de passe', message: 'Un mot de passe temporaire sera généré pour ce parent.', confirmLabel: 'Réinitialiser' })">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="rounded-md border border-gray-300 dark:border-slate-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700">
                        Réinitialiser mot de passe
                    </button>
                </form>
                @endif
            </div>
        </div>

        <!-- Liste des enfants -->
        <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Enfants associés ({{ $parent->students->count() }})</h3>
                <button onclick="document.getElementById('attachStudentForm').classList.toggle('hidden')" class="rounded-md bg-indigo-600 dark:bg-indigo-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 dark:hover:bg-indigo-600">
                    Associer un élève
                </button>
            </div>

            <!-- Formulaire d'association d'élève -->
            <div id="attachStudentForm" class="hidden mb-6 p-4 bg-gray-50 dark:bg-slate-700 rounded-lg">
                <form method="POST" action="{{ route('parents.attach-student', $parent) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Élève</label>
                        <select id="user_id" name="user_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-slate-800 dark:text-gray-100">
                            <option value="">Sélectionner un élève</option>
                            @foreach(\App\Models\User::role('eleve')->whereNotIn('id', $parent->students->pluck('id'))->get() as $student)
                                <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->matricule }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="lien_parente" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lien de parenté</label>
                        <select id="lien_parente" name="lien_parente" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-slate-800 dark:text-gray-100">
                            <option value="Pere">Père</option>
                            <option value="Mere">Mère</option>
                            <option value="Tuteur">Tuteur</option>
                            <option value="Tutrice">Tutrice</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                    <div class="flex gap-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="est_responsable_financier" value="1" class="rounded border-gray-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Responsable financier</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="est_contact_urgence" value="1" class="rounded border-gray-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Contact d'urgence</span>
                        </label>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" class="rounded-md bg-indigo-600 dark:bg-indigo-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 dark:hover:bg-indigo-600">
                            Associer
                        </button>
                        <button type="button" onclick="document.getElementById('attachStudentForm').classList.add('hidden')" class="rounded-md border border-gray-300 dark:border-slate-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700">
                            Annuler
                        </button>
                    </div>
                </form>
            </div>

            @forelse($studentsData as $data)
                @php($student = $data['student'])
                <div class="mb-6 p-4 border border-gray-200 dark:border-slate-700 rounded-lg last:mb-0">
                    <div class="flex items-start justify-between">
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ $student->name }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Matricule : {{ $student->matricule }}</p>
                        </div>
                        <div class="flex gap-2">
                            @if($student->pivot->lien_parente)
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200">
                                {{ $student->pivot->lien_parente }}
                            </span>
                            @endif
                            @if($student->pivot->est_responsable_financier)
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-200">
                                Responsable financier
                            </span>
                            @endif
                            @if($student->pivot->est_contact_urgence)
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-200">
                                Contact urgence
                            </span>
                            @endif
                        </div>
                    </div>

                    @if($data['currentRegistration'])
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="bg-gray-50 dark:bg-slate-700 p-3 rounded">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Classe actuelle</p>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $data['currentRegistration']->classroom->name ?? 'Non assigné' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-slate-700 p-3 rounded">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Année scolaire</p>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $data['currentRegistration']->schoolYear->year_string ?? 'N/A' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-slate-700 p-3 rounded">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Total payé</p>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($data['totalPaid'], 0, ',', ' ') }} FCFA</p>
                        </div>
                    </div>
                    @else
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Aucune inscription active</p>
                    @endif

                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('students.show', $student) }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">
                            Voir la fiche élève
                        </a>
                        <form method="POST" action="{{ route('parents.detach-student', [$parent, $student]) }}" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Dissocier l’élève', message: 'L’élève ne sera plus associé à ce parent. Le dossier élève ne sera pas supprimé.', confirmLabel: 'Dissocier' })" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">
                                Dissocier
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="text-center text-gray-500 dark:text-gray-400 py-4">Aucun enfant associé à ce parent.</p>
            @endforelse
        </div>
    </div>
</div>
</x-app-layout>
