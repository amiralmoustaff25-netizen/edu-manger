<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Double Authentification
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if(session('recoveryCodes'))
                    <div class="mb-6 rounded-md border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4">
                        <p class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-2">
                            Codes de secours — notez-les maintenant, ils ne seront plus jamais affichés :
                        </p>
                        <ul class="grid grid-cols-2 gap-1 font-mono text-sm text-amber-900 dark:text-amber-100">
                            @foreach(session('recoveryCodes') as $recoveryCode)
                                <li>{{ $recoveryCode }}</li>
                            @endforeach
                        </ul>
                        <p class="mt-2 text-xs text-amber-700 dark:text-amber-300">
                            Chaque code ne fonctionne qu'une seule fois, en remplacement de votre application d'authentification.
                        </p>
                    </div>
                @endif

                @if($enabled)
                    <div class="mb-6 rounded-md border border-green-200 dark:border-green-900 bg-green-50 dark:bg-green-900/20 p-4 text-sm text-green-800 dark:text-green-200">
                        Double authentification activée{{ $confirmedAt ? ' le '.$confirmedAt->format('d/m/Y à H:i') : '' }}. Elle sera exigée à chaque nouvelle connexion.
                    </div>

                    <form method="POST" action="{{ route('two-factor.destroy') }}" class="space-y-4">
                        @csrf
                        @method('DELETE')
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Mot de passe actuel (vérification d'identité)
                            </label>
                            <x-password-input name="current_password" id="current_password" required autocomplete="current-password"
                                   class="w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            @error('current_password')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                            Désactiver la double authentification
                        </button>
                    </form>
                @else
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Scannez ce code avec votre application d'authentification (Google Authenticator, Authy, 1Password...),
                        ou saisissez-y manuellement la clé ci-dessous, puis confirmez avec le code à 6 chiffres généré.
                    </p>

                    <div class="mb-4 rounded-md bg-gray-50 dark:bg-slate-700/50 p-4">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">URI de configuration (pour un lecteur de QR code)</p>
                        <p class="font-mono text-xs text-gray-900 dark:text-gray-100 break-all">{{ $provisioningUri }}</p>
                        <p class="mt-3 text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Clé de saisie manuelle</p>
                        <p class="font-mono text-sm text-gray-900 dark:text-gray-100 tracking-widest">{{ $secret }}</p>
                    </div>

                    <form method="POST" action="{{ route('two-factor.confirm') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Code de vérification
                            </label>
                            <input type="text" name="code" id="code" required inputmode="numeric" autocomplete="one-time-code"
                                   class="w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('code')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            Activer la double authentification
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
