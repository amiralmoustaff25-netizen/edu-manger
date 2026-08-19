@csrf

<div x-data="{ selectedRole: '{{ old('role', $user->role ?? '') }}' }" class="space-y-6">
    <!-- Section 1: Informations de base (tous les utilisateurs) -->
    <div class="bg-gray-50 dark:bg-slate-700/30 rounded-lg p-6 border border-gray-200 dark:border-slate-600">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            Informations de base
        </h3>
        
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label for="nom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom</label>
                <input id="nom" name="nom" value="{{ old('nom', $user->nom) }}" type="text" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <x-input-error :messages="$errors->get('nom')" class="mt-2" />
            </div>

            <div>
                <label for="prenom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prénom</label>
                <input id="prenom" name="prenom" value="{{ old('prenom', $user->prenom) }}" type="text" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <x-input-error :messages="$errors->get('prenom')" class="mt-2" />
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                <input id="email" name="email" value="{{ old('email', $user->email) }}" type="email" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rôle</label>
                <select id="role" name="role" x-model="selectedRole" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Sélectionner un rôle</option>
                    @foreach($roles as $role)
                        <option value="{{ $role }}" @selected(old('role', $user->role) === $role)>{{ $role }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('role')" class="mt-2" />
            </div>

            <div>
                <label for="telephone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Téléphone</label>
                <input id="telephone" name="telephone" value="{{ old('telephone', $user->telephone) }}" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <x-input-error :messages="$errors->get('telephone')" class="mt-2" />
            </div>

            <div x-show="!['professeur', 'eleve', 'parent'].includes(selectedRole)" x-transition>
                <label for="contract_started_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Début de contrat</label>
                <input id="contract_started_at" name="contract_started_at" value="{{ old('contract_started_at', optional($user->contract_started_at)->format('Y-m-d')) }}" type="date" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <x-input-error :messages="$errors->get('contract_started_at')" class="mt-2" />
            </div>

            <div class="md:col-span-2">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true)) class="rounded border-gray-300 dark:border-slate-600 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Compte actif</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Note : les rôles élève/parent ne peuvent pas être attribués ici. La création et la
         fiche complète (inscription pour un élève, filiation pour un parent) se gèrent
         exclusivement via leur module dédié, qui garde le compte Utilisateurs synchronisé.
         Le rôle professeur, lui, est créé directement ici (fiche métier ci-dessous) — seule
         sa MODIFICATION complète reste réservée au module Professeurs (voir plus bas). -->
    @if(! $user->exists)
        <!-- Fiche professeur (Teacher) : mêmes champs que teachers/_form.blade.php, affichés
             uniquement à la création puisque l'édition complète reste gérée par le module
             Professeurs (voir UserController::storeProfesseur()). -->
        <div x-show="selectedRole === 'professeur'" x-transition class="bg-gray-50 dark:bg-slate-700/30 rounded-lg p-6 border border-gray-200 dark:border-slate-600 space-y-5">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                </svg>
                Fiche professeur
            </h3>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label for="date_naissance" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date de naissance</label>
                    <input id="date_naissance" name="date_naissance" type="date" value="{{ old('date_naissance') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('date_naissance')" class="mt-2" />
                </div>

                <div>
                    <label for="lieu_naissance" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lieu de naissance</label>
                    <input id="lieu_naissance" name="lieu_naissance" type="text" value="{{ old('lieu_naissance') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('lieu_naissance')" class="mt-2" />
                </div>

                <div>
                    <label for="sexe" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sexe</label>
                    <select id="sexe" name="sexe" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Sélectionner</option>
                        <option value="masculin" @selected(old('sexe') === 'masculin')>Masculin</option>
                        <option value="feminin" @selected(old('sexe') === 'feminin')>Féminin</option>
                    </select>
                    <x-input-error :messages="$errors->get('sexe')" class="mt-2" />
                </div>

                <div>
                    <label for="nationalite" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nationalité</label>
                    <input id="nationalite" name="nationalite" type="text" value="{{ old('nationalite') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('nationalite')" class="mt-2" />
                </div>

                <div>
                    <label for="date_recrutement" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date de recrutement</label>
                    <input id="date_recrutement" name="date_recrutement" type="date" value="{{ old('date_recrutement') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('date_recrutement')" class="mt-2" />
                </div>

                <div>
                    <label for="statut" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Statut</label>
                    <select id="statut" name="statut" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Sélectionner</option>
                        <option value="fonctionnaire" @selected(old('statut') === 'fonctionnaire')>Fonctionnaire</option>
                        <option value="contractuel" @selected(old('statut') === 'contractuel')>Contractuel</option>
                        <option value="vacataire" @selected(old('statut') === 'vacataire')>Vacataire</option>
                    </select>
                    <x-input-error :messages="$errors->get('statut')" class="mt-2" />
                </div>

                <div class="md:col-span-2">
                    <label for="diplomes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Diplômes</label>
                    <textarea id="diplomes" name="diplomes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('diplomes') }}</textarea>
                    <x-input-error :messages="$errors->get('diplomes')" class="mt-2" />
                </div>

                <div class="md:col-span-2">
                    <label for="etablissements_formation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Établissement(s) de formation</label>
                    <textarea id="etablissements_formation" name="etablissements_formation" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('etablissements_formation') }}</textarea>
                    <x-input-error :messages="$errors->get('etablissements_formation')" class="mt-2" />
                </div>

                <div class="md:col-span-2">
                    <label for="specialites" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Spécialités (séparées par des virgules)</label>
                    <input id="specialites" name="specialites" type="text" value="{{ is_array(old('specialites')) ? implode(', ', old('specialites')) : old('specialites', '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('specialites')" class="mt-2" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ex. Mathématiques, Physique</p>
                </div>

                <div class="md:col-span-2">
                    <label for="filiation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Filiation</label>
                    <textarea id="filiation" name="filiation" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('filiation') }}</textarea>
                    <x-input-error :messages="$errors->get('filiation')" class="mt-2" />
                </div>

                <div>
                    <label for="contact_urgence_nom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contact urgence (nom)</label>
                    <input id="contact_urgence_nom" name="contact_urgence_nom" type="text" value="{{ old('contact_urgence_nom') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('contact_urgence_nom')" class="mt-2" />
                </div>

                <div>
                    <label for="contact_urgence_tel" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contact urgence (téléphone)</label>
                    <input id="contact_urgence_tel" name="contact_urgence_tel" type="text" value="{{ old('contact_urgence_tel') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('contact_urgence_tel')" class="mt-2" />
                </div>

                <div>
                    <label for="nombre_heures_semaine" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre d'heures/semaine</label>
                    <input id="nombre_heures_semaine" name="nombre_heures_semaine" type="number" min="0" value="{{ old('nombre_heures_semaine', 0) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('nombre_heures_semaine')" class="mt-2" />
                </div>
            </div>
        </div>
    @else
        <div x-show="selectedRole === 'professeur'" x-transition class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-6 border border-indigo-200 dark:border-indigo-700">
            <p class="text-sm text-indigo-800 dark:text-indigo-300">
                Ce compte est un compte professeur. Modifiez sa fiche complète (statut, diplômes, affectations pédagogiques...) depuis le module
                <a href="{{ route('teachers.index') }}" class="font-semibold underline hover:no-underline">Professeurs</a>.
                Seuls le nom, l'email et l'activation du compte peuvent être modifiés ici.
            </p>
        </div>
    @endif

    <div x-show="selectedRole === 'eleve'" x-transition class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-6 border border-indigo-200 dark:border-indigo-700">
        <p class="text-sm text-indigo-800 dark:text-indigo-300">
            Ce compte est un compte élève. Gérez son inscription, sa classe et son statut depuis le module
            <a href="{{ route('students.index') }}" class="font-semibold underline hover:no-underline">Élèves</a>.
            Seuls le nom, l'email et l'activation du compte peuvent être modifiés ici.
        </p>
    </div>

    <div x-show="selectedRole === 'parent'" x-transition class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-6 border border-indigo-200 dark:border-indigo-700">
        <p class="text-sm text-indigo-800 dark:text-indigo-300">
            Ce compte est un compte parent. Gérez ses enfants liés depuis le module
            <a href="{{ route('parents.index') }}" class="font-semibold underline hover:no-underline">Parents</a>.
            Seuls le nom, l'email et l'activation du compte peuvent être modifiés ici.
        </p>
    </div>

    @if(! $user->exists)
        <div class="rounded-md bg-indigo-50 dark:bg-indigo-900/50 p-4 text-sm text-indigo-800 dark:text-indigo-300">
            <p>Le matricule et un mot de passe temporaire seront générés automatiquement et affichés après la création du compte.</p>
        </div>
    @endif

    <div class="flex items-center gap-3 pt-4 border-t border-gray-200 dark:border-slate-600">
        <button type="submit" class="rounded-md bg-indigo-600 dark:bg-indigo-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 dark:hover:bg-indigo-600">
            {{ $user->exists ? 'Enregistrer les modifications' : 'Créer le compte' }}
        </button>
        <a href="{{ route('users.index') }}" class="rounded-md border border-gray-300 dark:border-slate-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700">Annuler</a>
    </div>
</div>
