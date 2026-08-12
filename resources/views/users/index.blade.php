<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Gestion des utilisateurs</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Comptes, rôles, accès et cycle de vie.</p>
            </div>
            <a href="{{ route('users.create') }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                Ajouter un utilisateur
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 dark:text-gray-100">
                <form method="GET" action="{{ route('users.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Recherche</label>
                        <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" type="text" placeholder="Matricule, nom ou email" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rôle</label>
                        <select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Tous les rôles</option>
                            @foreach($roles as $role)
                                <option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ $role }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Statut</label>
                        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Tous</option>
                            <option value="active" @selected(($filters['status'] ?? '') === 'active')>Actifs</option>
                            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactifs</option>
                            <option value="archived" @selected(($filters['status'] ?? '') === 'archived')>Archivés</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2 md:col-span-4">
                        <button type="submit" class="rounded-md bg-gray-900 dark:bg-gray-700 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 dark:hover:bg-gray-600">Filtrer</button>
                        <a href="{{ route('users.index') }}" class="rounded-md border border-gray-300 dark:border-slate-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700">Réinitialiser</a>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-lg bg-white dark:bg-slate-800 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-slate-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left align-middle whitespace-nowrap font-semibold text-gray-600 dark:text-gray-300">Matricule</th>
                                <th class="px-4 py-3 text-left align-middle font-semibold text-gray-600 dark:text-gray-300">Utilisateur</th>
                                <th class="px-4 py-3 text-left align-middle font-semibold text-gray-600 dark:text-gray-300">Rôle</th>
                                <th class="px-4 py-3 text-left align-middle font-semibold text-gray-600 dark:text-gray-300">Statut</th>
                                <th class="px-4 py-3 text-left align-middle font-semibold text-gray-600 dark:text-gray-300">Créé par</th>
                                <th class="px-4 py-3 text-right align-middle font-semibold text-gray-600 dark:text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                            @forelse($users as $user)
                                @php
                                    $isSelf = $user->is(auth()->user());
                                    $canTargetSuperAdmin = ! $user->hasRole('super-admin') || auth()->user()->hasRole('super-admin');
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 align-middle whitespace-nowrap font-mono text-xs text-gray-700 dark:text-gray-300">{{ $user->matricule }}</td>
                                    <td class="px-4 py-3 align-middle">
                                        <p class="font-medium text-gray-900 dark:text-gray-200">{{ $user->name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->display_email ?? 'Email non renseigné' }}</p>
                                    </td>
                                    <td class="px-4 py-3 align-middle">
                                        <x-badge color="blue">{{ $user->getRoleNames()->first() ?? 'Sans rôle' }}</x-badge>
                                    </td>
                                    <td class="px-4 py-3 align-middle">
                                        @if($user->trashed())
                                            <x-badge color="red">Archivé</x-badge>
                                        @else
                                            <x-badge :color="$user->is_active ? 'green' : 'gray'">
                                                {{ $user->is_active ? 'Actif' : 'Inactif' }}
                                            </x-badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-middle text-gray-600 dark:text-gray-400">{{ $user->creator->name ?? 'Système' }}</td>
                                    <td class="px-4 py-3 align-middle text-right whitespace-nowrap">
                                        <div class="inline-flex flex-nowrap items-center justify-end gap-2">
                                            @if($user->trashed())
                                                @can('supprimer-utilisateur', $user)
                                                    @if($canTargetSuperAdmin)
                                                        <form method="POST" action="{{ route('users.restore', $user->id) }}">
                                                            @csrf
                                                            <button type="submit" class="rounded-md border border-emerald-200 dark:border-emerald-700 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:text-emerald-300 hover:bg-emerald-50 dark:hover:bg-emerald-900/30">Restaurer</button>
                                                        </form>
                                                    @endif
                                                @endcan
                                            @else
                                                @can('modifier-utilisateur', $user)
                                                    @if($canTargetSuperAdmin)
                                                        <a href="{{ route('users.edit', $user) }}" class="rounded-md border border-gray-300 dark:border-slate-600 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700">Modifier</a>
                                                    @endif
                                                @endcan

                                                @can('reinitialiser-mot-de-passe-utilisateur', $user)
                                                    @if($canTargetSuperAdmin)
                                                        <form method="POST" action="{{ route('users.reset-password', $user) }}" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Réinitialiser le mot de passe', message: 'Le mot de passe sera remplacé par un mot de passe temporaire et l’utilisateur devra le changer à sa prochaine connexion.', confirmLabel: 'Réinitialiser' })">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="rounded-md border border-indigo-200 dark:border-indigo-700 px-3 py-1.5 text-xs font-semibold text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/50">Réinitialiser</button>
                                                        </form>
                                                    @endif
                                                @endcan

                                                @can('activer-desactiver-utilisateur', $user)
                                                    @if($canTargetSuperAdmin && ! $isSelf)
                                                        <form method="POST" action="{{ route('users.toggle', $user) }}" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: '{{ $user->is_active ? 'Désactiver' : 'Activer' }} l’utilisateur', message: '{{ $user->is_active ? 'Le compte sera désactivé mais son historique sera conservé.' : 'Le compte pourra de nouveau se connecter.' }}', confirmLabel: '{{ $user->is_active ? 'Désactiver' : 'Activer' }}' })">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="rounded-md border border-amber-200 dark:border-amber-700 px-3 py-1.5 text-xs font-semibold text-amber-700 dark:text-amber-300 hover:bg-amber-50 dark:hover:bg-amber-900/50">
                                                                {{ $user->is_active ? 'Désactiver' : 'Activer' }}
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endcan

                                                @can('supprimer-utilisateur', $user)
                                                    @if($canTargetSuperAdmin && ! $isSelf)
                                                        <form method="POST" action="{{ route('users.destroy', $user) }}" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Archiver l’utilisateur', message: 'Le compte {{ addslashes($user->name) }} sera désactivé et archivé. Son historique sera conservé.', confirmLabel: 'Archiver' })">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="rounded-md border border-red-200 dark:border-red-700 px-3 py-1.5 text-xs font-semibold text-red-700 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/50">Archiver</button>
                                                        </form>
                                                    @endif
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Aucun utilisateur trouvé.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-100 dark:border-slate-700 px-4 py-3">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
