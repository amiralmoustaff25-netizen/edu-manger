<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Journal d'Audit
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">

                <!-- Filtres -->
                <div class="mb-6 bg-gray-50 dark:bg-slate-700/50 p-4 rounded-lg">
                    <form action="{{ route('audit-logs.index') }}" method="GET" class="flex flex-wrap gap-3 items-end">
                        <div class="flex-1 min-w-[200px]">
                            <label for="audit-log-user" class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Utilisateur</label>
                            <select name="user_id" id="audit-log-user" class="w-full border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 p-2 rounded focus:ring-2 focus:ring-blue-500">
                                <option value="">Tous</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1 min-w-[180px]">
                            <label for="audit-log-action" class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Action</label>
                            <select name="action" id="audit-log-action" class="w-full border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 p-2 rounded focus:ring-2 focus:ring-blue-500">
                                <option value="">Toutes</option>
                                @foreach($actions as $action)
                                    <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ $action }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1 min-w-[180px]">
                            <label for="audit-log-model" class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Module</label>
                            <select name="model_type" id="audit-log-model" class="w-full border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 p-2 rounded focus:ring-2 focus:ring-blue-500">
                                <option value="">Tous</option>
                                @foreach($modelTypes as $modelType)
                                    <option value="{{ $modelType }}" {{ request('model_type') === $modelType ? 'selected' : '' }}>{{ class_basename($modelType) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1 min-w-[150px]">
                            <label for="audit-log-date" class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Date</label>
                            <input type="date" name="date" id="audit-log-date" value="{{ request('date') }}" class="w-full border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 p-2 rounded focus:ring-2 focus:ring-blue-500">
                        </div>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded transition">Filtrer</button>
                        <a href="{{ route('audit-logs.index') }}" class="bg-gray-200 dark:bg-slate-600 hover:bg-gray-300 dark:hover:bg-slate-500 text-gray-700 dark:text-gray-200 px-6 py-2 rounded transition">Réinitialiser</a>
                    </form>
                </div>

                <!-- Tableau des entrées -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date/Heure</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Utilisateur</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Module</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">IP</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                            @foreach($logs as $log)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                    {{ $log->created_at->format('d/m/Y H:i:s') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                    {{ $log->user->name ?? 'Système' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-300">{{ $log->action }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ class_basename($log->model_type) }} @if($log->model_id) #{{ $log->model_id }} @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $log->ip_address ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @can('view', $log)
                                        <a href="{{ route('audit-logs.show', $log) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">Détails</a>
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                {{ $logs->links() }}

                @if($logs->isEmpty())
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <p>Aucune entrée trouvée dans le journal d'audit.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
