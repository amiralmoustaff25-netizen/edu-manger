<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Détails du Log de Connexion
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="mb-6">
                    <a href="{{ route('login-logs.index') }}" class="text-indigo-600 hover:text-indigo-900">
                        &larr; Retour à la liste
                    </a>
                </div>

                <div class="bg-gray-50 rounded-lg p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Utilisateur</p>
                            <p class="mt-1 text-lg text-gray-900">{{ $loginLog->user->name ?? 'N/A' }}</p>
                            <p class="text-sm text-gray-600">{{ $loginLog->email }}</p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500">Adresse IP</p>
                            <p class="mt-1 text-lg text-gray-900">{{ $loginLog->ip_address }}</p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500">Date et heure</p>
                            <p class="mt-1 text-lg text-gray-900">{{ $loginLog->login_at->format('d/m/Y H:i:s') }}</p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500">Statut</p>
                            <p class="mt-1">
                                @if($loginLog->status === 'success')
                                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">Succès</span>
                                @else
                                    <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">Échec</span>
                                @endif
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <p class="text-sm font-medium text-gray-500">User Agent</p>
                            <p class="mt-1 text-gray-900 break-all">{{ $loginLog->user_agent }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
