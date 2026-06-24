<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Fiche Parent</h2>
                <p class="mt-1 text-sm text-gray-500">Informations détaillées du parent et de ses enfants.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('parents.edit', $parent) }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                    Modifier
                </a>
                <a href="{{ route('parents.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                    Retour
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Informations du parent -->
            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $parent->nom }} {{ $parent->prenom }}</h3>
                        <p class="mt-1 text-sm text-gray-500">Matricule : {{ $parent->matricule_parent }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-sm font-medium 
                        {{ $parent->statut === 'actif' ? 'bg-emerald-100 text-emerald-800' : 
                           ($parent->statut === 'en_attente_activation' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-800') }}">
                        {{ $parent->statut === 'actif' ? 'Actif' : 
                           ($parent->statut === 'en_attente_activation' ? 'En attente' : 'Archivé') }}
                    </span>
                </div>

                <div class="mt-6 grid gap-6 md:grid-cols-2">
                    <div>
                        <h4 class="text-sm font-medium text-gray-700">Informations de contact</h4>
                        <dl class="mt-3 space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Email</dt>
                                <dd class="text-gray-900">{{ $parent->email }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Téléphone</dt>
                                <dd class="text-gray-900">{{ $parent->telephone ?? 'Non renseigné' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Adresse</dt>
                                <dd class="text-gray-900">{{ $parent->adresse ?? 'Non renseignée' }}</dd>
                            </div>
                        </dl>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-700">Informations professionnelles</h4>
                        <dl class="mt-3 space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Profession</dt>
                                <dd class="text-gray-900">{{ $parent->profession ?? 'Non renseignée' }}</dd>
                            </div>
                            @if($parent->user)
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Compte utilisateur</dt>
                                <dd class="text-gray-900">{{ $parent->user->matricule }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Statut compte</dt>
                                <dd class="text-gray-900">
                                    <span class="{{ $parent->user->is_active ? 'text-emerald-600' : 'text-red-600' }}">
                                        {{ $parent->user->is_active ? 'Actif' : 'Inactif' }}
                                    </span>
                                </dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <div class="mt-6 flex gap-3 pt-4 border-t border-gray-200">
                    <form method="POST" action="{{ route('parents.archive', $parent) }}" onsubmit="return confirm('Êtes-vous sûr de vouloir archiver ce parent ?');">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Archiver
                        </button>
                    </form>
                    @if($parent->user)
                    <form method="POST" action="{{ route('parents.reset-password', $parent) }}" onsubmit="return confirm('Êtes-vous sûr de vouloir réinitialiser le mot de passe ?');">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Réinitialiser mot de passe
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            <!-- Liste des enfants -->
            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Enfants associés ({{ $parent->students->count() }})</h3>
                    <button onclick="document.getElementById('attachStudentForm').classList.toggle('hidden')" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                        Associer un élève
                    </button>
                </div>

                <!-- Formulaire d'association d'élève -->
                <div id="attachStudentForm" class="hidden mb-6 p-4 bg-gray-50 rounded-lg">
                    <form method="POST" action="{{ route('parents.attach-student', $parent) }}" class="space-y-4">
                        @csrf
                        <div>
                            <label for="user_id" class="block text-sm font-medium text-gray-700">Élève</label>
                            <select id="user_id" name="user_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Sélectionner un élève</option>
                                @foreach(\App\Models\User::where('role', 'eleve')->whereNotIn('id', $parent->students->pluck('id'))->get() as $student)
                                    <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->matricule }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="lien_parente" class="block text-sm font-medium text-gray-700">Lien de parenté</label>
                            <select id="lien_parente" name="lien_parente" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="Pere">Père</option>
                                <option value="Mere">Mère</option>
                                <option value="Tuteur">Tuteur</option>
                                <option value="Tutrice">Tutrice</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>
                        <div class="flex gap-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="est_responsable_financier" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Responsable financier</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="est_contact_urgence" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Contact d'urgence</span>
                            </label>
                        </div>
                        <div class="flex gap-3">
                            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                                Associer
                            </button>
                            <button type="button" onclick="document.getElementById('attachStudentForm').classList.add('hidden')" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                Annuler
                            </button>
                        </div>
                    </form>
                </div>

                @forelse($studentsData as $data)
                    @php($student = $data['student'])
                    <div class="mb-6 p-4 border border-gray-200 rounded-lg last:mb-0">
                        <div class="flex items-start justify-between">
                            <div>
                                <h4 class="font-medium text-gray-900">{{ $student->name }}</h4>
                                <p class="text-sm text-gray-500">Matricule : {{ $student->matricule }}</p>
                            </div>
                            <div class="flex gap-2">
                                @if($student->pivot->lien_parente)
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $student->pivot->lien_parente }}
                                </span>
                                @endif
                                @if($student->pivot->est_responsable_financier)
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium bg-green-100 text-green-800">
                                    Responsable financier
                                </span>
                                @endif
                                @if($student->pivot->est_contact_urgence)
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium bg-red-100 text-red-800">
                                    Contact urgence
                                </span>
                                @endif
                            </div>
                        </div>

                        @if($data['currentRegistration'])
                        <div class="mt-4 grid gap-4 md:grid-cols-3">
                            <div class="bg-gray-50 p-3 rounded">
                                <p class="text-xs text-gray-500">Classe actuelle</p>
                                <p class="font-medium text-gray-900">{{ $data['currentRegistration']->classroom->name ?? 'Non assigné' }}</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded">
                                <p class="text-xs text-gray-500">Année scolaire</p>
                                <p class="font-medium text-gray-900">{{ $data['currentRegistration']->schoolYear->year_string ?? 'N/A' }}</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded">
                                <p class="text-xs text-gray-500">Total payé</p>
                                <p class="font-medium text-gray-900">{{ number_format($data['totalPaid'], 0, ',', ' ') }} FCFA</p>
                            </div>
                        </div>
                        @else
                        <p class="mt-4 text-sm text-gray-500">Aucune inscription active</p>
                        @endif

                        <div class="mt-4 flex gap-2">
                            <a href="{{ route('students.show', $student) }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                                Voir la fiche élève
                            </a>
                            <form method="POST" action="{{ route('parents.detach-student', [$parent, $student]) }}" onsubmit="return confirm('Êtes-vous sûr de vouloir dissocier cet élève ?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                                    Dissocier
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-gray-500 py-4">Aucun enfant associé à ce parent.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
