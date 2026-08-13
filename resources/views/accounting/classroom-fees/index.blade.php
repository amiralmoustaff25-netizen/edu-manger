<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Gestion des Frais par Classe
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Formulaire de filtres -->
                    <form action="{{ route('classroom-fees.index') }}" method="GET" class="mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="classroom_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Classe</label>
                                <select name="classroom_id" id="classroom_id" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Toutes</option>
                                    @foreach($classrooms as $classroom)
                                        <option value="{{ $classroom->id }}" {{ request('classroom_id') == $classroom->id ? 'selected' : '' }}>{{ $classroom->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="fee_type_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type de frais</label>
                                <select name="fee_type_id" id="fee_type_id" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Tous</option>
                                    @foreach($feeTypes as $feeType)
                                        <option value="{{ $feeType->id }}" {{ request('fee_type_id') == $feeType->id ? 'selected' : '' }}>{{ $feeType->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="school_year_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Année scolaire</label>
                                <select name="school_year_id" id="school_year_id" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Toutes</option>
                                    @foreach($schoolYears as $schoolYear)
                                        <option value="{{ $schoolYear->id }}" {{ request('school_year_id') == $schoolYear->id ? 'selected' : '' }}>{{ $schoolYear->year_string }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                Filtrer
                            </button>
                            <a href="{{ route('classroom-fees.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                Réinitialiser
                            </a>
                            <a href="{{ route('classroom-fees.create') }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                                + Nouveaux Frais
                            </a>
                        </div>
                    </form>

                    <!-- Tableau des frais par classe -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Classe</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type de frais</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Année scolaire</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Montant</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Version</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($classroomFees as $classroomFee)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $classroomFee->classroom->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ $classroomFee->feeType->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ $classroomFee->schoolYear->year_string }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ number_format($classroomFee->amount, 0, ',', ' ') }} FCFA
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <a href="{{ route('classroom-fees.history', $classroomFee) }}" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200 hover:underline">
                                                v{{ $classroomFee->version }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('classroom-fees.edit', $classroomFee) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 mr-2">Modifier</a>
                                            <form action="{{ route('classroom-fees.destroy', $classroomFee) }}" method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ces frais ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                            Aucun frais de classe trouvé
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($classroomFees->hasPages())
                        <div class="mt-6">
                            {{ $classroomFees->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
