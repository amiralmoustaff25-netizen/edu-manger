<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Pointage des enseignants') }}
            </h2>
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $date->translatedFormat('l d/m/Y') }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Navigation date -->
            <div class="flex items-center justify-between">
                <a href="{{ route('teacher-attendances.index', ['date' => $date->copy()->subDay()->toDateString()]) }}" class="rounded-md bg-white dark:bg-slate-800 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 ring-1 ring-gray-300 dark:ring-slate-600 hover:bg-gray-50 dark:hover:bg-slate-700">
                    &larr; Jour précédent
                </a>
                <form method="GET" class="flex items-center gap-2">
                    <input type="date" name="date" value="{{ $dateString }}" onchange="this.form.submit()" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white text-sm">
                </form>
                <a href="{{ route('teacher-attendances.index', ['date' => $date->copy()->addDay()->toDateString()]) }}" class="rounded-md bg-white dark:bg-slate-800 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 ring-1 ring-gray-300 dark:ring-slate-600 hover:bg-gray-50 dark:hover:bg-slate-700">
                    Jour suivant &rarr;
                </a>
            </div>

            <!-- Classes sans enseignant -->
            @if($classesWithoutTeacher->isNotEmpty())
                <div class="rounded-lg bg-red-50 dark:bg-red-900/20 shadow-sm ring-1 ring-red-200 dark:ring-red-800 overflow-hidden">
                    <div class="px-6 py-4 border-b border-red-200 dark:border-red-800">
                        <h3 class="text-base font-semibold text-red-800 dark:text-red-300">Classes sans enseignant présent</h3>
                    </div>
                    <div class="px-6 py-4">
                        <div class="flex flex-wrap gap-2">
                            @foreach($classesWithoutTeacher as $classroom)
                                <span class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                    {{ $classroom->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Pointage -->
            <div class="rounded-lg bg-white dark:bg-slate-800 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Feuille de pointage</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $teachers->count() }} enseignants.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-700/50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Enseignant</th>
                                <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Statut</th>
                                <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Arrivée</th>
                                <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Observation</th>
                                <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Cours pointés</th>
                                @can('enregistrer-pointage-enseignant')
                                    <th class="px-5 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Action</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                            @forelse($teachers as $teacher)
                                @php($attendance = $attendances->get($teacher->id))
                                <tr class="text-sm text-gray-700 dark:text-gray-200">
                                    <td class="px-5 py-4 whitespace-nowrap font-medium">{{ $teacher->user->name ?? '—' }}</td>
                                    <td class="px-5 py-4">
                                        @if($attendance)
                                            <x-badge :color="$attendance->status === 'present' ? 'green' : ($attendance->status === 'absent' ? 'red' : 'amber')">{{ $attendance->status_label }}</x-badge>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">{{ $attendance?->arrival_time?->format('H:i') ?? '—' }}</td>
                                    <td class="px-5 py-4">{{ $attendance?->notes ?: '—' }}</td>
                                    <td class="px-5 py-4">
                                        @php($sessions = $taughtSessions->get($teacher->id))
                                        @if($sessions && $sessions->isNotEmpty())
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($sessions as $session)
                                                    <span class="inline-flex items-center rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-700 dark:bg-slate-700 dark:text-gray-300">{{ $session->assignment->classroom->name }} ({{ $session->assignment->matiere->nom ?? '—' }})</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    @can('enregistrer-pointage-enseignant')
                                        <td class="px-5 py-4 text-right">
                                            <button type="button" x-data="" @click="document.getElementById('form-{{ $teacher->id }}').classList.toggle('hidden')" class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm">Pointer</button>
                                        </td>
                                    @endcan
                                </tr>
                                @can('enregistrer-pointage-enseignant')
                                    <tr id="form-{{ $teacher->id }}" class="hidden bg-gray-50 dark:bg-slate-700/30">
                                        <td colspan="6" class="px-5 py-4">
                                            <form method="POST" action="{{ route('teacher-attendances.store') }}" class="flex flex-wrap items-end gap-4">
                                                @csrf
                                                <input type="hidden" name="teacher_id" value="{{ $teacher->id }}">
                                                <input type="hidden" name="date" value="{{ $dateString }}">
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Statut</label>
                                                    <select name="status" class="mt-1 block w-32 rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white text-sm">
                                                        <option value="present" @selected($attendance?->status === 'present')>Présent</option>
                                                        <option value="late" @selected($attendance?->status === 'late')>Retard</option>
                                                        <option value="absent" @selected($attendance?->status === 'absent')>Absent</option>
                                                        <option value="excused" @selected($attendance?->status === 'excused')>Excusé</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Heure d'arrivée</label>
                                                    <input type="time" name="arrival_time" value="{{ $attendance?->arrival_time?->format('H:i') }}" class="mt-1 block rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white text-sm">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Observation</label>
                                                    <input type="text" name="notes" value="{{ $attendance?->notes }}" class="mt-1 block rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white text-sm">
                                                </div>
                                                <x-primary-button type="submit">Enregistrer</x-primary-button>
                                            </form>
                                        </td>
                                    </tr>
                                @endcan
                            @empty
                                <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Aucun enseignant enregistré.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
