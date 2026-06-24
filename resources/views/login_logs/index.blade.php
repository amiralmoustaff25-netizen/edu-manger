<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Journal des Connexions
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                <!-- Filtres -->
                <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                    <form action="{{ route('login-logs.index') }}" method="GET" class="flex flex-wrap gap-3 items-end">
                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-sm text-gray-600 mb-1">Statut</label>
                            <select name="status" class="w-full border border-gray-300 p-2 rounded focus:ring-2 focus:ring-blue-500">
                                <option value="">Tous</option>
                                <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Réussies</option>
                                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Échouées</option>
                            </select>
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-sm text-gray-600 mb-1">Utilisateur</label>
                            <select name="user_id" class="w-full border border-gray-300 p-2 rounded focus:ring-2 focus:ring-blue-500">
                                <option value="">Tous</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-sm text-gray-600 mb-1">Date</label>
                            <input type="date" name="date" value="{{ request('date') }}" class="w-full border border-gray-300 p-2 rounded focus:ring-2 focus:ring-blue-500">
                        </div>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded transition">Filtrer</button>
                        <a href="{{ route('login-logs.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded transition">Réinitialiser</a>
                    </form>
                </div>

                <!-- Statistiques rapides -->
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600">{{ App\Models\LoginLog::count() }}</div>
                        <div class="text-sm text-gray-600">Total connexions</div>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-green-600">{{ App\Models\LoginLog::successful()->count() }}</div>
                        <div class="text-sm text-gray-600">Réussies</div>
                    </div>
                    <div class="bg-red-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-red-600">{{ App\Models\LoginLog::failed()->count() }}</div>
                        <div class="text-sm text-gray-600">Échouées</div>
                    </div>
                </div>

                <!-- Tableau des logs -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date/Heure</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Agent</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($logs as $log)
                            <tr class="{{ $log->status === 'failed' ? 'bg-red-50' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $log->login_at ? $log->login_at->format('d/m/Y H:i:s') : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($log->user)
                                        <a href="{{ route('users.show', $log->user->id) }}" class="text-blue-600 hover:text-blue-900">
                                            {{ $log->user->name }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $log->email ?? ($log->user ? $log->user->email : '-') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $log->ip_address ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($log->status === 'success')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Réussie</span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Échouée</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                    {{ $log->user_agent ?? '-' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                {{ $logs->links() }}

                @if($logs->isEmpty())
                    <div class="text-center py-8 text-gray-500">
                        <p>Aucun log de connexion trouvé.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
