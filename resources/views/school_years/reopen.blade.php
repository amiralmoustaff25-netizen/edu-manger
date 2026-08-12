<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Réouverture exceptionnelle</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $schoolYear->year_string }} — action réservée au Super Administrateur</p>
            </div>
            <a href="{{ route('school-years.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700">
                Retour
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 space-y-6">

                <div class="rounded-lg bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 p-4 text-sm text-amber-800 dark:text-amber-200">
                    Cette année scolaire est clôturée et verrouillée : notes, paiements, bulletins, affectations et suppressions y sont bloqués. La réouvrir lève temporairement ce verrou pour tout le monde tant qu'elle n'est pas re-clôturée. Cette action est enregistrée dans le journal d'audit.
                </div>

                <form method="POST" action="{{ route('school-years.reopen', $schoolYear) }}" class="space-y-6">
                    @csrf

                    @if ($errors->schoolYearReopen->any())
                        <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900 p-4 text-sm text-red-700 dark:text-red-200">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->schoolYearReopen->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirmez votre mot de passe</label>
                        <input id="password" name="password" type="password" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-slate-700">
                        <a href="{{ route('school-years.index') }}" class="rounded-md border border-gray-300 dark:border-slate-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700">
                            Annuler
                        </a>
                        <button type="submit" class="rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-700">
                            Rouvrir exceptionnellement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
