<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Détails du Log de Connexion
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="mb-6">
                    <a href="{{ route('login-logs.index') }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                        &larr; Retour à la liste
                    </a>
                </div>

                <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Utilisateur</p>
                            <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">{{ $loginLog->user->name ?? 'N/A' }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $loginLog->email }}</p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Matricule / Rôle</p>
                            <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">{{ $loginLog->matricule ?? '-' }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $loginLog->role ?? '-' }}</p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Adresse IP</p>
                            <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">{{ $loginLog->ip_address }}</p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Connexion</p>
                            <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">{{ $loginLog->login_at->format('d/m/Y H:i:s') }}</p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Déconnexion</p>
                            <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">{{ $loginLog->logout_at?->format('d/m/Y H:i:s') ?? 'Session en cours ou non fermée explicitement' }}</p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Durée de session</p>
                            <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                                @if($loginLog->duration_in_seconds !== null)
                                    {{ gmdate('H:i:s', $loginLog->duration_in_seconds) }}
                                @else
                                    -
                                @endif
                            </p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Statut</p>
                            <p class="mt-1">
                                @if($loginLog->status === 'success')
                                    <span class="px-3 py-1 bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300 rounded-full text-sm font-medium">Succès</span>
                                @else
                                    <span class="px-3 py-1 bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300 rounded-full text-sm font-medium">Échec</span>
                                @endif
                            </p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Navigateur / OS / Appareil</p>
                            <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">{{ $loginLog->browser ?? '-' }} · {{ $loginLog->platform ?? '-' }} · <span class="capitalize">{{ $loginLog->device_type ?? '-' }}</span></p>
                        </div>

                        <div class="md:col-span-2">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">User Agent</p>
                            <p class="mt-1 text-gray-900 dark:text-gray-100 break-all">{{ $loginLog->user_agent }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
