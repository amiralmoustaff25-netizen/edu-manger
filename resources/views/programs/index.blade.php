<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Programmes annuels</h2>
            <a href="{{ route('programs.create') }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">Nouveau programme</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-card padding="p-4" class="overflow-x-auto">
                @if ($programs->isEmpty())
                    <p class="py-8 text-center text-gray-500 dark:text-gray-400">Aucun programme annuel pour cette période.</p>
                @else
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-300">Classe</th>
                                <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-300">Matière</th>
                                <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-300">Statut</th>
                                <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-300">Enseignant</th>
                                <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-300">Avancement</th>
                                <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                            @foreach ($programs as $program)
                                <tr>
                                    <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $program->classroom?->name }}</td>
                                    <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $program->subject?->nom }}</td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-slate-700 dark:text-gray-200">{{ $program->status }}</span>
                                    </td>
                                    <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $program->teacher?->name }}</td>
                                    <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $program->progressPercentage }}%</td>
                                    <td class="px-4 py-2">
                                        <a href="{{ route('programs.show', $program) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium">Voir</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-4">{{ $programs->links() }}</div>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
