<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Historique des présences</h2>
            <a href="{{ route('professeur.attendances.index') }}" class="rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Saisir les présences</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="GET" class="grid grid-cols-1 gap-4 rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200 md:grid-cols-3 dark:bg-slate-800 dark:ring-slate-700">
                <div>
                    <label for="classroom_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Classe</label>
                    <select id="classroom_id" name="classroom_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                        <option value="">Toutes mes classes</option>
                        @foreach($classrooms as $classroom)
                            <option value="{{ $classroom->id }}" @selected($selectedClassroom === $classroom->id)>{{ $classroom->name }} — {{ $classroom->schoolYear?->year_string }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
                    <input id="date" name="date" type="date" value="{{ request('date') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                </div>
                <div class="flex items-end gap-3">
                    <x-primary-button type="submit">Filtrer</x-primary-button>
                    <a href="{{ route('professeur.attendances.history') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-slate-600 dark:text-gray-200 dark:hover:bg-slate-700">Réinitialiser</a>
                </div>
            </form>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200 dark:bg-slate-800 dark:ring-slate-700">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-slate-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Présences enregistrées</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-700/50"><tr><th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500">Date</th><th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500">Classe</th><th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500">Élève</th><th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500">Statut</th><th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500">Observation</th></tr></thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                            @forelse($attendances as $attendance)
                                <tr class="text-sm text-gray-700 dark:text-gray-200"><td class="whitespace-nowrap px-5 py-4">{{ $attendance->date->format('d/m/Y') }}</td><td class="px-5 py-4">{{ $attendance->classroom->name }}</td><td class="px-5 py-4">{{ $attendance->student->name }}</td><td class="px-5 py-4"><x-badge :color="$attendance->status === 'present' ? 'green' : ($attendance->status === 'absent' ? 'red' : 'amber')">{{ $attendance->status_label }}</x-badge></td><td class="px-5 py-4">{{ $attendance->notes ?: '—' }}</td></tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Aucune présence enregistrée pour ces critères.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($attendances->hasPages())<div class="border-t border-gray-200 px-5 py-4 dark:border-slate-700">{{ $attendances->links() }}</div>@endif
            </div>
        </div>
    </div>
</x-app-layout>
