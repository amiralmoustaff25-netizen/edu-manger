<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Mon Espace Élève
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900">Bienvenue, {{ Auth::user()->name }}</h3>
                    <p class="text-gray-600">Matricule : {{ Auth::user()->matricule }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-blue-50 p-6 rounded-lg">
                        <h4 class="font-semibold text-blue-800 mb-2">Mes Informations</h4>
                        <p class="text-sm text-gray-600">Consultez et modifiez vos informations personnelles</p>
                        <a href="{{ route('profile.show') }}" class="mt-3 inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                            Voir mon profil
                        </a>
                    </div>

                    <div class="bg-green-50 p-6 rounded-lg">
                        <h4 class="font-semibold text-green-800 mb-2">Ma Classe</h4>
                        <p class="text-sm text-gray-600">Accédez aux informations de votre classe</p>
                        @if(Auth::user()->latestRegistration && Auth::user()->latestRegistration->classroom)
                            <p class="mt-2 font-medium">{{ Auth::user()->latestRegistration->classroom->name }}</p>
                        @else
                            <p class="mt-2 text-gray-500">Non assigné</p>
                        @endif
                    </div>

                    <div class="bg-purple-50 p-6 rounded-lg">
                        <h4 class="font-semibold text-purple-800 mb-2">Mes Notes</h4>
                        <p class="text-sm text-gray-600">Consultez vos notes et bulletins</p>
                        <p class="mt-2 text-gray-500">Bientôt disponible</p>
                    </div>
                </div>

                <div class="mt-8">
                    <h4 class="font-semibold text-gray-900 mb-4">Actions rapides</h4>
                    <div class="flex gap-4">
                        <a href="{{ route('profile.show') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded transition">
                            Mon Profil
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="bg-red-100 hover:bg-red-200 text-red-700 px-4 py-2 rounded transition">
                                Déconnexion
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
