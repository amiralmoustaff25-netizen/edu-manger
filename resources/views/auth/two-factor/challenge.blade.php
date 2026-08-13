<x-guest-layout>
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
        Entrez le code à 6 chiffres généré par votre application d'authentification, ou l'un de vos codes de secours si vous n'y avez plus accès.
    </p>

    <form method="POST" action="{{ route('two-factor.challenge.store') }}">
        @csrf

        <div>
            <x-input-label for="code" value="Code de vérification" />
            <x-text-input id="code" class="block mt-1 w-full" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" required autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                Vérifier
            </x-primary-button>
        </div>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm text-gray-500 dark:text-gray-400 underline hover:text-gray-700 dark:hover:text-gray-200">
            Se déconnecter
        </button>
    </form>
</x-guest-layout>
