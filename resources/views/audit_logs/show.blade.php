<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Détails de l'entrée d'audit
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="mb-6">
                    <a href="{{ route('audit-logs.index') }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                        &larr; Retour à la liste
                    </a>
                </div>

                <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Utilisateur</p>
                            <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">{{ $auditLog->user->name ?? 'Système' }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $auditLog->user->email ?? '' }}</p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Date et heure</p>
                            <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">{{ $auditLog->created_at->format('d/m/Y H:i:s') }}</p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Action</p>
                            <p class="mt-1">
                                <span class="px-3 py-1 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-300 rounded-full text-sm font-medium">{{ $auditLog->action }}</span>
                            </p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Module concerné</p>
                            <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">{{ class_basename($auditLog->model_type) }} @if($auditLog->model_id) #{{ $auditLog->model_id }} @endif</p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Adresse IP</p>
                            <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">{{ $auditLog->ip_address ?? '-' }}</p>
                        </div>

                        @if($auditLog->comment)
                            <div class="md:col-span-2">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Commentaire</p>
                                <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $auditLog->comment }}</p>
                            </div>
                        @endif

                        @if($auditLog->old_values)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Valeurs avant</p>
                                <pre class="mt-1 text-sm text-gray-900 dark:text-gray-100 bg-white dark:bg-slate-800 rounded p-3 overflow-x-auto">{{ json_encode($auditLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        @endif

                        @if($auditLog->new_values)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Valeurs après</p>
                                <pre class="mt-1 text-sm text-gray-900 dark:text-gray-100 bg-white dark:bg-slate-800 rounded p-3 overflow-x-auto">{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        @endif

                        <div class="md:col-span-2">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Navigateur / Appareil</p>
                            <p class="mt-1 text-gray-900 dark:text-gray-100 break-all">{{ $auditLog->user_agent ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
