<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Gestion des Rappels
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <div class="flex gap-2">
                            <a href="{{ route('payments.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 dark:bg-slate-700 dark:text-gray-200 dark:hover:bg-slate-600">
                                ← Retour
                            </a>
                        </div>
                        <div class="flex gap-2">
                            <form action="{{ route('reminders.generate-overdue') }}" method="POST">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700">
                                    Générer Rappels de Retard
                                </button>
                            </form>
                            <form action="{{ route('reminders.generate-upcoming') }}" method="POST">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                    Générer Rappels d'Échéances
                                </button>
                            </form>
                            <a href="{{ route('reminders.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                + Nouveau Rappel
                            </a>
                        </div>
                    </div>

                    @if($reminders->count() === 0)
                        <div class="text-center py-12">
                            <div class="text-6xl mb-4">📬</div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Aucun rappel</h3>
                            <p class="text-gray-600 dark:text-gray-400">Créez des rappels ou générez-les automatiquement.</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($reminders as $reminder)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <div class="md:col-span-2">
                                            <div class="flex items-center gap-3 mb-2">
                                                <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center">
                                                    <span class="text-xl">👤</span>
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-gray-900 dark:text-white">
                                                        {{ $reminder->registration->user->name }}
                                                    </p>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                                        {{ $reminder->registration->classroom->name ?? 'Non assigné' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">Type</p>
                                            <p class="font-semibold text-gray-900 dark:text-white">
                                                {{ ucfirst($reminder->type) }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                {{ $reminder->scheduled_at->format('d/m/Y H:i') }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">Statut</p>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                                {{ $reminder->status === 'sent' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                                   ($reminder->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                                                   'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200') }}">
                                                {{ ucfirst($reminder->status) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mt-4 p-3 bg-gray-100 dark:bg-gray-600 rounded text-sm text-gray-700 dark:text-gray-300">
                                        {{ $reminder->message }}
                                    </div>

                                    @if($reminder->status === 'pending')
                                        <div class="mt-4 flex gap-2 justify-end">
                                            <form action="{{ route('reminders.destroy', $reminder) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm">
                                                    Supprimer
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{ $reminders->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
