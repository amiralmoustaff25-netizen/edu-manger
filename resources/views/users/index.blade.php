<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestion des utilisateurs</h2>
                <p class="mt-1 text-sm text-gray-500">Comptes, rôles, accès et cycle de vie.</p>
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
                <form method="GET" action="{{ route('users.index') }}" class="grid gap-4 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <label for="search" class="block text-sm font-medium text-gray-700">Recherche</label>
                        <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" type="text" placeholder="Matricule, nom ou email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700">Rôle</label>
                        <select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Tous les rôles</option>
                            @foreach($roles as $role)
                                <option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ $role }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Statut</label>
                        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Tous</option>
                            <option value="active" @selected(($filters['status'] ?? '') === 'active')>Actifs</option>
                            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactifs</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2 md:col-span-4">
                        <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">Filtrer</button>
                        <a href="{{ route('users.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Réinitialiser</a>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Matricule</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Utilisateur</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Rôle</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Statut</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Créé par</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($users as $user)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $user->matricule }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-900">{{ $user->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $user->email ?? 'Email non renseigné' }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-medium text-sky-800">{{ $user->role }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $user->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700' }}">
                                            {{ $user->is_active ? 'Actif' : 'Inactif' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $user->creator->name ?? 'Système' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a href="{{ route('users.edit', $user) }}" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">Modifier</a>

                                            <form method="POST" action="{{ route('users.reset-password', $user) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="rounded-md border border-indigo-200 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Réinitialiser</button>
                                            </form>

                                            <form method="POST" action="{{ route('users.toggle', $user) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="rounded-md border border-amber-200 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-50">
                                                    {{ $user->is_active ? 'Désactiver' : 'Activer' }}
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Archiver cet utilisateur ? Son historique sera conservé.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">Archiver</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">Aucun utilisateur trouvé.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-100 px-4 py-3">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
