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
        
        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label for="nom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom</label>
                <input id="nom" name="nom" value="{{ old('nom', $user->prenom ? explode(' ', $user->name)[0] : $user->name) }}" type="text" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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

            <div class="md:col-span-2">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true)) class="rounded border-gray-300 dark:border-slate-600 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Compte actif</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Section 2: Informations professeur (conditionnelle) -->
    <div x-show="selectedRole === 'professeur'" x-transition class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-6 border border-indigo-200 dark:border-indigo-700">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 180 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
            </svg>
            Informations professeur
        </h3>
        
        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label for="date_naissance" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date de naissance</label>
                <input id="date_naissance" name="date_naissance" value="{{ old('date_naissance') }}" type="date" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <x-input-error :messages="$errors->get('date_naissance')" class="mt-2" />
            </div>

            <div>
                <label for="lieu_naissance" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lieu de naissance</label>
                <input id="lieu_naissance" name="lieu_naissance" value="{{ old('lieu_naissance') }}" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <x-input-error :messages="$errors->get('lieu_naissance')" class="mt-2" />
            </div>

            <div>
                <label for="sexe" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sexe</label>
                <select id="sexe" name="sexe" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Sélectionner</option>
                    <option value="masculin" @selected(old('sexe') === 'masculin')">Masculin</option>
                    <option value="feminin" @selected(old('sexe') === 'feminin')">Féminin</option>
                </select>
                <x-input-error :messages="$errors->get('sexe')" class="mt-2" />
            </div>

            <div>
                <label for="nationalite" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nationalité</label>
                <input id="nationalite" name="nationalite" value="{{ old('nationalite') }}" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <x-input-error :messages="$errors->get('nationalite')" class="mt-2" />
            </div>

            <div>
                <label for="telephone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Téléphone</label>
                <input id="telephone" name="telephone" value="{{ old('telephone') }}" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <x-input-error :messages="$errors->get('telephone')" class="mt-2" />
            </div>

            <div>
                <label for="statut" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Statut</label>
                <select id="statut" name="statut" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Sélectionner</option>
                    <option value="fonctionnaire" @selected(old('statut') === 'fonctionnaire')">Fonctionnaire</option>
                    <option value="contractuel" @selected(old('statut') === 'contractuel')">Contractuel</option>
                    <option value="vacataire" @selected(old('statut') === 'vacataire')">Vacataire</option>
                </select>
                <x-input-error :messages="$errors->get('statut')" class="mt-2" />
            </div>

            <div class="md:col-span-2">
                <label for="diplomes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Diplômes</label>
                <textarea id="diplomes" name="diplomes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('diplomes') }}</textarea>
                <x-input-error :messages="$errors->get('diplomes')" class="mt-2" />
            </div>

            <div class="md:col-span-2">
                <label for="specialites" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Spécialités (séparées par des virgules)</label>
                <input id="specialites" name="specialites" value="{{ old('specialites') }}" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <x-input-error :messages="$errors->get('specialites')" class="mt-2" />
            </div>
        </div>
    </div>

    <!-- Section 3: Affectation pédagogique (conditionnelle professeur) -->
    <div x-show="selectedRole === 'professeur'" x-transition class="bg-green-50 dark:bg-green-900/20 rounded-lg p-6 border border-green-200 dark:border-green-700">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            Affectation pédagogique
            <span class="text-sm font-normal text-gray-600 dark:text-gray-400">(Optionnel - peut être fait plus tard)</span>
        </h3>
        
        <div class="bg-white dark:bg-slate-800 rounded-lg p-4 border border-gray-200 dark:border-slate-600">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Vous pourrez affecter des classes et des matières à ce professeur après sa création via la page de gestion des classes.
            </p>
            <a href="{{ route('classrooms.index') }}" class="inline-flex items-center text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-sm font-medium">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
                Gérer les affectations après création
            </a>
        </div>
    </div>

    @if(! $user->exists)
        <div class="rounded-md bg-indigo-50 dark:bg-indigo-900/50 p-4 text-sm text-indigo-800 dark:text-indigo-300">
            <p>Le matricule sera généré automatiquement et le mot de passe temporaire sera <strong>password</strong>.</p>
            <p x-show="selectedRole === 'professeur'" class="mt-2">Les informations professeur sont facultatives mais recommandées pour une meilleure gestion.</p>
        </div>
    @endif

    <div class="flex items-center gap-3 pt-4 border-t border-gray-200 dark:border-slate-600">
        <button type="submit" class="rounded-md bg-indigo-600 dark:bg-indigo-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 dark:hover:bg-indigo-600">
            {{ $user->exists ? 'Enregistrer les modifications' : 'Créer le compte' }}
        </button>
        <a href="{{ route('users.index') }}" class="rounded-md border border-gray-300 dark:border-slate-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700">Annuler</a>
    </div>
</div>
