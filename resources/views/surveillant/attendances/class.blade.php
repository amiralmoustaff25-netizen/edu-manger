<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Présences') }} — {{ $classroom->name }}
            </h2>
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $date->translatedFormat('l d/m/Y') }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Navigation date -->
            <div class="flex items-center justify-between">
                <a href="{{ route('surveillant.attendances.class', ['classroom' => $classroom->id, 'date' => $date->copy()->subDay()->toDateString()]) }}" class="rounded-md bg-white dark:bg-slate-800 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 ring-1 ring-gray-300 dark:ring-slate-600 hover:bg-gray-50 dark:hover:bg-slate-700">
                    &larr; Jour précédent
                </a>
                <form method="GET" class="flex items-center gap-2">
                    <input type="date" name="date" value="{{ $dateString }}" onchange="this.form.submit()" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white text-sm">
                </form>
                <a href="{{ route('surveillant.attendances.class', ['classroom' => $classroom->id, 'date' => $date->copy()->addDay()->toDateString()]) }}" class="rounded-md bg-white dark:bg-slate-800 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 ring-1 ring-gray-300 dark:ring-slate-600 hover:bg-gray-50 dark:hover:bg-slate-700">
                    Jour suivant &rarr;
                </a>
            </div>

            <!-- Feuille de présence -->
            <div class="rounded-lg bg-white dark:bg-slate-800 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Feuille de présence</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $students->count() }} élèves inscrits.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-700/50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Élève</th>
                                <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Statut</th>
                                <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Observation</th>
                                <th class="px-5 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                            @forelse($students as $student)
                                @php($attendance = $attendances->get($student->id))
                                <tr class="text-sm text-gray-700 dark:text-gray-200">
                                    <td class="px-5 py-4 whitespace-nowrap font-medium">{{ $student->name }}</td>
                                    <td class="px-5 py-4">
                                        @if($attendance)
                                            <x-badge :color="$attendance->status === 'present' ? 'green' : ($attendance->status === 'absent' ? 'red' : 'amber')">{{ $attendance->status_label }}</x-badge>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">{{ $attendance?->notes ?: '—' }}</td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('surveillant.attendances.student', ['student' => $student->id]) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm">Historique</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Aucun élève inscrit dans cette classe.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Historique récent -->
            @if($recentHistory->isNotEmpty())
                <div class="rounded-lg bg-white dark:bg-slate-800 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200 dark:border-slate-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Historique sur les 7 derniers jours</h3>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-slate-700">
                        @foreach($recentHistory as $day => $dayAttendances)
                            <div class="px-6 py-4">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ $day }}</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($dayAttendances as $attendance)
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                            {{ $attendance->status === 'present' ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : ($attendance->status === 'absent' ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300') }}">
                                            {{ $attendance->student->name ?? 'Élève' }} ({{ $attendance->status_label }})
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
