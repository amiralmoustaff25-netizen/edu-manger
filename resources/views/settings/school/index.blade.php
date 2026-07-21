<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Configuration de l'École
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (!$config->is_configured)
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 mb-6">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <h3 class="text-amber-800 dark:text-amber-200 font-semibold">Configuration requise</h3>
                            <p class="text-amber-700 dark:text-amber-300 text-sm mt-1">Veuillez compléter la configuration de l'école avant de continuer.</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Informations de l'école -->
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                        Informations de l'École
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                        Informations générales sur votre établissement scolaire.
                    </p>
                </div>
                <div class="px-4 py-5 sm:px-6">
                    <form action="{{ route('settings.school.update-info') }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div class="col-span-2">
                                <label for="school_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom de l'école *</label>
                                <input type="text" name="school_name" id="school_name" value="{{ $config->school_name ?? '' }}" required
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div class="col-span-2">
                                <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Adresse</label>
                                <input type="text" name="address" id="address" value="{{ $config->address ?? '' }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Téléphone</label>
                                <input type="text" name="phone" id="phone" value="{{ $config->phone ?? '' }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                                <input type="email" name="email" id="email" value="{{ $config->email ?? '' }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div class="col-span-2">
                                <label for="website" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Site web</label>
                                <input type="url" name="website" id="website" value="{{ $config->website ?? '' }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div class="mt-6">
                            <button type="submit" class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Informations bancaires -->
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                        Informations Bancaires
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                        Coordonnées bancaires pour les paiements.
                    </p>
                </div>
                <div class="px-4 py-5 sm:px-6">
                    <form action="{{ route('settings.school.update-bank') }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="bank_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom de la banque</label>
                                <input type="text" name="bank_name" id="bank_name" value="{{ $config->bank_name ?? '' }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label for="account_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Numéro de compte</label>
                                <input type="text" name="account_number" id="account_number" value="{{ $config->account_number ?? '' }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label for="iban" class="block text-sm font-medium text-gray-700 dark:text-gray-300">IBAN</label>
                                <input type="text" name="iban" id="iban" value="{{ $config->iban ?? '' }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label for="swift" class="block text-sm font-medium text-gray-700 dark:text-gray-300">SWIFT/BIC</label>
                                <input type="text" name="swift" id="swift" value="{{ $config->swift ?? '' }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div class="mt-6">
                            <button type="submit" class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Paramètres comptables -->
            @role('manager-comptable|comptable|super-admin|admin')
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                        Paramètres Comptables
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                        Configuration des règles de paiement.
                    </p>
                </div>
                <div class="px-4 py-5 sm:px-6">
                    <form action="{{ route('settings.school.update-accounting') }}" method="POST">
                        @csrf
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Mode de gestion du trop-perçu</label>
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input type="radio" name="overpayment_mode" value="change" {{ $config->overpayment_mode === 'change' ? 'checked' : '' }} class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Calcul automatique de la monnaie à rendre</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="radio" name="overpayment_mode" value="credit" {{ $config->overpayment_mode === 'credit' ? 'checked' : '' }} class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Transformation en avoir (crédit client)</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" name="sequential_payment_rule" value="1" {{ $config->sequential_payment_rule ? 'checked' : '' }} class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Appliquer la règle de paiement séquentiel (interdire le paiement de mois futurs si les mois précédents ne sont pas réglés)</span>
                                </label>
                            </div>
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" name="allow_future_payment" value="1" {{ $config->allow_future_payment ? 'checked' : '' }} class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Autoriser les paiements anticipés (désactive la règle séquentielle)</span>
                                </label>
                            </div>
                        </div>
                        <div class="mt-6">
                            <button type="submit" class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endrole

            <!-- Bouton de finalisation -->
            @if (!$config->is_configured)
                <div class="flex justify-end">
                    <form action="{{ route('settings.school.complete') }}" method="POST">
                        @csrf
                        <button type="submit" class="inline-flex justify-center rounded-md border border-transparent bg-green-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                            Terminer la configuration
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
