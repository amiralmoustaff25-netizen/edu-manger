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

    <!-- Note : le rôle professeur ne peut pas être attribué ici. La création et la fiche complète
         d'un professeur (statut, diplômes, filiation, affectations...) se gèrent exclusivement
         via le module Professeurs, qui garde le compte Utilisateurs synchronisé. -->
    <div x-show="selectedRole === 'professeur'" x-transition class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-6 border border-indigo-200 dark:border-indigo-700">
        <p class="text-sm text-indigo-800 dark:text-indigo-300">
            Ce compte est un compte professeur. Modifiez sa fiche complète (statut, diplômes, affectations pédagogiques...) depuis le module
            <a href="{{ route('teachers.index') }}" class="font-semibold underline hover:no-underline">Professeurs</a>.
            Seuls le nom, l'email et l'activation du compte peuvent être modifiés ici.
        </p>
    </div>

    @if(! $user->exists)
        <div class="rounded-md bg-indigo-50 dark:bg-indigo-900/50 p-4 text-sm text-indigo-800 dark:text-indigo-300">
            <p>Le matricule sera généré automatiquement et le mot de passe temporaire sera <strong>password</strong>.</p>
        </div>
    @endif

    <div class="flex items-center gap-3 pt-4 border-t border-gray-200 dark:border-slate-600">
        <button type="submit" class="rounded-md bg-indigo-600 dark:bg-indigo-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 dark:hover:bg-indigo-600">
            {{ $user->exists ? 'Enregistrer les modifications' : 'Créer le compte' }}
        </button>
        <a href="{{ route('users.index') }}" class="rounded-md border border-gray-300 dark:border-slate-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700">Annuler</a>
    </div>
</div>
