<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Code de Sécurité Administrateur
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">

                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                    Ce code est distinct de votre mot de passe de connexion. Une fois défini, il est exigé en plus
                    de votre session normale pour certaines actions critiques et irréversibles (par exemple :
                    suppression définitive d'une année scolaire). Il n'est jamais affiché en clair après sa création.
                </p>

                @if($hasCode)
                    <div class="mb-6 rounded-md border border-green-200 dark:border-green-900 bg-green-50 dark:bg-green-900/20 p-4 text-sm text-green-800 dark:text-green-200">
                        Un code de sécurité est actuellement défini{{ $updatedAt ? ' (dernière modification le '.$updatedAt->format('d/m/Y à H:i').')' : '' }}.
                    </div>
                @else
                    <div class="mb-6 rounded-md border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4 text-sm text-amber-800 dark:text-amber-200">
                        Aucun code de sécurité n'est défini pour votre compte. Tant qu'il ne l'est pas, les actions
                        critiques restent accessibles sans cette vérification supplémentaire.
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.security-code.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

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

                    <div>
                        <label for="security_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $hasCode ? 'Nouveau code de sécurité' : 'Code de sécurité' }}
                        </label>
                        <x-password-input name="security_code" id="security_code" required minlength="6" autocomplete="off"
                               class="w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        @error('security_code')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="security_code_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Confirmer le code
                        </label>
                        <x-password-input name="security_code_confirmation" id="security_code_confirmation" required minlength="6" autocomplete="off"
                               class="w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            {{ $hasCode ? 'Modifier le code' : 'Définir le code' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
